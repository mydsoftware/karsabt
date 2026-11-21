<?php
/**
 * آزمون مستقل سرویس OTP بدون نیاز به نصب وردپرس.
 * اجرا: php tests/php/otp-service-test.php
 */
define('ABSPATH', __DIR__);
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['karsabt_test_transients'] = array();
$GLOBALS['karsabt_test_otp_code'] = '';
$GLOBALS['karsabt_test_time'] = time();

class WP_Error {
    private $code;
    public function __construct($code, $message = '') { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
function wp_salt($scheme = '') { return 'karsabt-test-salt-' . $scheme; }
function karsabt_core_normalize_mobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', (string) $mobile);
    return preg_match('/^09[0-9]{9}$/', $mobile) ? $mobile : '';
}
function get_transient($key) {
    $item = $GLOBALS['karsabt_test_transients'][$key] ?? false;
    if (is_array($item) && $item['expires'] <= $GLOBALS['karsabt_test_time']) {
        unset($GLOBALS['karsabt_test_transients'][$key]);
        return false;
    }
    return is_array($item) ? $item['value'] : false;
}
function set_transient($key, $value, $ttl = 0) {
    $GLOBALS['karsabt_test_transients'][$key] = array(
        'value' => $value,
        'expires' => $GLOBALS['karsabt_test_time'] + (int) $ttl,
    );
    return true;
}
function delete_transient($key) {
    unset($GLOBALS['karsabt_test_transients'][$key]);
    return true;
}
function apply_filters($hook, $value, ...$args) {
    if ('karsabt_send_otp' === $hook && true === $GLOBALS['karsabt_test_provider']) {
        $GLOBALS['karsabt_test_otp_code'] = $args[1];
        return true;
    }
    return $value;
}
$GLOBALS['karsabt_test_provider'] = true;

require dirname(__DIR__, 2) . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-otp.php';

function karsabt_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}
function karsabt_error_code($result) {
    return $result instanceof WP_Error ? $result->get_error_code() : '';
}

$mobile = '09123456789';
karsabt_assert(true === Karsabt_OTP_Service::send($mobile), 'ارسال OTP با درگاه فعال');
$code = $GLOBALS['karsabt_test_otp_code'];
karsabt_assert(6 === strlen($code) && ctype_digit($code), 'تولید کد شش‌رقمی عددی');
karsabt_assert('otp_wait' === karsabt_error_code(Karsabt_OTP_Service::send($mobile)), 'جلوگیری از ارسال مجدد در بازه انتظار');
karsabt_assert(true === Karsabt_OTP_Service::verify($mobile, $code), 'تأیید کد صحیح');

$GLOBALS['karsabt_test_transients'] = array();
karsabt_assert(true === Karsabt_OTP_Service::send($mobile), 'ارسال کد برای آزمون تلاش ناموفق');
for ($i = 0; $i < 4; $i++) {
    karsabt_assert('invalid_otp' === karsabt_error_code(Karsabt_OTP_Service::verify($mobile, '000000')), 'رد کد اشتباه شماره ' . ($i + 1));
}
karsabt_assert('otp_attempts_exceeded' === karsabt_error_code(Karsabt_OTP_Service::verify($mobile, '000000')), 'قفل پس از پنج تلاش ناموفق');

$GLOBALS['karsabt_test_transients'] = array();
karsabt_assert(true === Karsabt_OTP_Service::send($mobile), 'ارسال کد برای آزمون انقضا');
$code_key = '';
foreach (array_keys($GLOBALS['karsabt_test_transients']) as $key) {
    if (strpos($key, 'karsabt_otp_code_') === 0) { $code_key = $key; break; }
}
$expired = get_transient($code_key);
$expired['expires_at'] = $GLOBALS['karsabt_test_time'] - 1;
set_transient($code_key, $expired, 300);
karsabt_assert('otp_expired' === karsabt_error_code(Karsabt_OTP_Service::verify($mobile, $code)), 'رد کد منقضی‌شده');

$GLOBALS['karsabt_test_transients'] = array();
$GLOBALS['karsabt_test_provider'] = false;
karsabt_assert('otp_provider_unavailable' === karsabt_error_code(Karsabt_OTP_Service::send($mobile)), 'عدم فعال‌سازی کد بدون درگاه پیامک');

echo "تمام آزمون‌های OTP با موفقیت اجرا شدند.\n";
