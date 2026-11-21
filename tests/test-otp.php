<?php
/**
 * آزمون مستقل سرویس OTP با شبیه‌سازی حداقلی توابع وردپرس.
 * اجرا: php tests/test-otp.php
 */
define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['karsabt_test_transients'] = array();
$GLOBALS['karsabt_test_filters'] = array();

class WP_Error {
    private $code;
    public function __construct($code, $message = '') { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}

function wp_salt($scheme = 'auth') { return 'test-secret-' . $scheme; }
function karsabt_core_normalize_mobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', (string) $mobile);
    return preg_match('/^09[0-9]{9}$/', $mobile) ? $mobile : '';
}
function get_transient($key) {
    $item = $GLOBALS['karsabt_test_transients'][$key] ?? false;
    if (is_array($item) && isset($item['expires']) && $item['expires'] <= time()) {
        unset($GLOBALS['karsabt_test_transients'][$key]);
        return false;
    }
    return is_array($item) ? $item['value'] : false;
}
function set_transient($key, $value, $ttl = 0) {
    $GLOBALS['karsabt_test_transients'][$key] = array('value' => $value, 'expires' => $ttl ? time() + $ttl : 0);
    return true;
}
function delete_transient($key) {
    unset($GLOBALS['karsabt_test_transients'][$key]);
    return true;
}
function apply_filters($tag, $value) {
    $args = func_get_args();
    if (isset($GLOBALS['karsabt_test_filters'][$tag])) {
        return call_user_func_array($GLOBALS['karsabt_test_filters'][$tag], array_slice($args, 1));
    }
    return $value;
}
function expect_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}
function expect_error_code($result, $code, $message) {
    expect_true($result instanceof WP_Error && $result->get_error_code() === $code, $message);
}

require __DIR__ . '/../wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-otp.php';

$mobile = '09123456789';
$sent_code = '';
$GLOBALS['karsabt_test_filters']['karsabt_send_otp'] = function($default, $target, $code) use (&$sent_code, $mobile) {
    expect_true($target === $mobile, 'شماره نرمال‌شده به درگاه ارسال شود');
    $sent_code = $code;
    return true;
};

expect_true(Karsabt_OTP_Service::send($mobile) === true, 'ارسال OTP باید با درگاه فعال موفق شود');
expect_true((bool) preg_match('/^[0-9]{6}$/', $sent_code), 'کد باید شش‌رقمی باشد');
expect_error_code(Karsabt_OTP_Service::send($mobile), 'otp_wait', 'ارسال مجدد در بازه cooldown باید مسدود شود');

expect_error_code(Karsabt_OTP_Service::verify($mobile, '000000'), 'invalid_otp', 'کد اشتباه باید رد شود');
expect_true(Karsabt_OTP_Service::verify($mobile, $sent_code) === true, 'کد صحیح باید پذیرفته شود');
expect_error_code(Karsabt_OTP_Service::verify($mobile, $sent_code), 'otp_expired', 'کد مصرف‌شده نباید دوباره پذیرفته شود');

// پنج تلاش اشتباه باید کد را باطل کند.
$GLOBALS['karsabt_test_transients'] = array();
expect_true(Karsabt_OTP_Service::send($mobile) === true, 'ارسال مجدد پس از پاک‌سازی تست باید موفق شود');
for ($i = 0; $i < 5; $i++) {
    expect_error_code(Karsabt_OTP_Service::verify($mobile, '000000'), 'invalid_otp', 'تلاش اشتباه باید رد شود');
}
expect_error_code(Karsabt_OTP_Service::verify($mobile, $sent_code), 'otp_expired', 'پس از پنج تلاش کد باید باطل شده باشد');

// تاریخ انقضا باید مستقل از TTL transient کنترل شود.
$GLOBALS['karsabt_test_transients'] = array();
expect_true(Karsabt_OTP_Service::send($mobile) === true, 'ارسال برای آزمون انقضا باید موفق شود');
$code_key = 'karsabt_otp_code_' . hash_hmac('sha256', $mobile, wp_salt('auth'));
$payload = get_transient($code_key);
$payload['expires_at'] = time() - 1;
set_transient($code_key, $payload, 300);
expect_error_code(Karsabt_OTP_Service::verify($mobile, $sent_code), 'otp_expired', 'کد منقضی‌شده باید رد شود');

// بدون اتصال‌دهنده پیامک، کد نباید فعال شود.
$GLOBALS['karsabt_test_filters'] = array();
$GLOBALS['karsabt_test_transients'] = array();
expect_error_code(Karsabt_OTP_Service::send($mobile), 'otp_provider_unavailable', 'ارسال بدون درگاه باید رد شود');

echo "تمام آزمون‌های OTP با موفقیت گذشتند.\n";
