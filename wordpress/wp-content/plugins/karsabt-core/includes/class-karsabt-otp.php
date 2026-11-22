<?php
/**
 * سرویس مستقل رمز یک‌بارمصرف کارثبت.
 * اتصال‌دهنده پیامک از طریق فیلتر karsabt_send_otp به این سرویس متصل می‌شود.
 */
if (!defined('ABSPATH')) { exit; }

final class Karsabt_OTP_Service {
    const CODE_TTL = 300;
    const RESEND_DELAY = 60;
    const MAX_VERIFY_ATTEMPTS = 5;
    const MAX_SENDS_PER_HOUR = 5;

    private static function mobile_key($mobile) {
        return hash_hmac('sha256', (string) $mobile, wp_salt('auth'));
    }

    private static function transient_key($prefix, $mobile) {
        return 'karsabt_otp_' . $prefix . '_' . self::mobile_key($mobile);
    }

    /** محدودسازی مستقل بر اساس IP برای جلوگیری از SMS pumping و brute force چندشماره‌ای. */
    private static function ip_rate_limited($action, $limit, $ttl) {
        // در اجرای مستقل تست (خارج از HTTP) محدودیت IP اعمال نمی‌شود؛ در درخواست واقعی REMOTE_ADDR الزامی است.
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return false;
        }
        $ip = trim((string) $_SERVER['REMOTE_ADDR']);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        $action = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $action));
        $key = 'karsabt_otp_ip_' . $action . '_' . hash('sha256', $ip);
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            return true;
        }
        set_transient($key, $count + 1, $ttl);
        return false;
    }

    /** @return true|WP_Error */
    public static function send($mobile) {
        $mobile = function_exists('karsabt_core_normalize_mobile') ? karsabt_core_normalize_mobile($mobile) : '';
        if (!$mobile) { return new WP_Error('invalid_mobile', 'شماره موبایل معتبر نیست.'); }
        if (self::ip_rate_limited('send', 10, HOUR_IN_SECONDS)) {
            return new WP_Error('otp_ip_rate_limited', 'تعداد درخواست‌ها بیش از حد مجاز است. بعداً دوباره تلاش کنید.');
        }

        $cooldown_key = self::transient_key('cooldown', $mobile);
        $hour_key = self::transient_key('hour', $mobile);
        if (get_transient($cooldown_key)) {
            return new WP_Error('otp_wait', 'برای ارسال مجدد کد، کمی صبر کنید.');
        }

        $sent = (int) get_transient($hour_key);
        if ($sent >= self::MAX_SENDS_PER_HOUR) {
            return new WP_Error('otp_rate_limited', 'سقف ارسال کد برای این شماره به پایان رسیده است.');
        }

        $code = (string) random_int(100000, 999999);
        $message = sprintf('کد ورود کارثبت: %s', $code);

        // تا زمانی که اتصال‌دهنده واقعی پیامک true برنگرداند، کد فعال نمی‌شود.
        $result = apply_filters('karsabt_send_otp', false, $mobile, $code, $message);
        if (true !== $result) {
            return new WP_Error('otp_provider_unavailable', 'درگاه ارسال پیامک هنوز پیکربندی نشده است.');
        }

        $payload = array(
            'hash' => hash_hmac('sha256', self::mobile_key($mobile) . ':' . $code, wp_salt('secure_auth')),
            'attempts' => 0,
            'expires_at' => time() + self::CODE_TTL,
        );
        set_transient(self::transient_key('code', $mobile), $payload, self::CODE_TTL);
        set_transient($cooldown_key, 1, self::RESEND_DELAY);
        set_transient($hour_key, $sent + 1, HOUR_IN_SECONDS);
        return true;
    }

    /** @return true|WP_Error */
    public static function verify($mobile, $code) {
        $mobile = function_exists('karsabt_core_normalize_mobile') ? karsabt_core_normalize_mobile($mobile) : '';
        $code = strtr((string) $code, array(
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ));
        $code = preg_replace('/[^0-9]/', '', $code);
        if (!$mobile || !preg_match('/^[0-9]{6}$/', $code)) {
            return new WP_Error('invalid_otp', 'کد تأیید معتبر نیست یا منقضی شده است.');
        }
        if (self::ip_rate_limited('verify', 30, 15 * MINUTE_IN_SECONDS)) {
            return new WP_Error('otp_ip_rate_limited', 'تعداد تلاش‌ها بیش از حد مجاز است. بعداً دوباره تلاش کنید.');
        }

        $transient_key = self::transient_key('code', $mobile);
        $payload = get_transient($transient_key);
        if (!is_array($payload) || empty($payload['hash']) || empty($payload['expires_at']) || (int) $payload['expires_at'] <= time()) {
            delete_transient($transient_key);
            return new WP_Error('otp_expired', 'کد تأیید معتبر نیست یا منقضی شده است.');
        }
        if ((int) ($payload['attempts'] ?? 0) >= self::MAX_VERIFY_ATTEMPTS) {
            delete_transient($transient_key);
            return new WP_Error('otp_attempts_exceeded', 'تعداد تلاش‌های مجاز به پایان رسیده است.');
        }

        $expected = hash_hmac('sha256', self::mobile_key($mobile) . ':' . $code, wp_salt('secure_auth'));
        if (!hash_equals($payload['hash'], $expected)) {
            $payload['attempts'] = isset($payload['attempts']) ? (int) $payload['attempts'] + 1 : 1;
            if ($payload['attempts'] >= self::MAX_VERIFY_ATTEMPTS) {
                delete_transient($transient_key);
                return new WP_Error('otp_attempts_exceeded', 'تعداد تلاش‌های مجاز به پایان رسیده است.');
            } else {
                $remaining_ttl = max(1, (int) $payload['expires_at'] - time());
                set_transient($transient_key, $payload, $remaining_ttl);
            }
            return new WP_Error('invalid_otp', 'کد تأیید اشتباه است.');
        }

        delete_transient($transient_key);
        delete_transient(self::transient_key('cooldown', $mobile));
        return true;
    }
}
