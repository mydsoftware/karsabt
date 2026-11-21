<?php
/**
 * آزمون‌های مستقل سرویس OTP کارثبت؛ بدون نیاز به نصب وردپرس.
 */
define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['karsabt_test_transients'] = array();
$GLOBALS['karsabt_test_otp_code'] = '';

class WP_Error {
    private $code;
    public function __construct($code, $message = '') { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}

function wp_salt($scheme = 'auth') { return 'karsabt-test-secret-' . $scheme; }
function karsabt_core_normalize_mobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', (string) $mobile);
    return preg_match('/^09[0-9]{9}$/', $mobile) ? $mobile : '';
}
function get_transient($key) {
    return $GLOBALS['karsabt_test_transients'][$key] ?? false;
}
function set_transient($key, $value, $expiration = 0) {
    $GLOBALS['karsabt_test_transients'][$key] = $value;
    return true;
}
function delete_transient($key) {
    unset($GLOBALS['karsabt_test_transients'][$key]);
    return true;
}
function apply_filters($hook, $value, ...$args) {
    if ('karsabt_send_otp' === $hook) {
        $GLOBALS['karsabt_test_otp_code'] = $args[1] ?? '';
        return true;
    }
    return $value;
}

require __DIR__ . '/../wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-otp.php';

function test_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

$mobile = '09123456789';
test_assert(true === Karsabt_OTP_Service::send($mobile), 'ارسال OTP با درگاه آزمایشی');
$code = $GLOBALS['karsabt_test_otp_code'];
test_assert((bool) preg_match('/^[0-9]{6}$/', $code), 'تولید کد شش‌رقمی');

$wrong = Karsabt_OTP_Service::verify($mobile, '000000');
test_assert($wrong instanceof WP_Error && 'invalid_otp' === $wrong->get_error_code(), 'رد کد اشتباه');
$payload = false;
foreach ($GLOBALS['karsabt_test_transients'] as $key => $value) {
    if (strpos($key, 'karsabt_otp_code_') === 0) { $payload = $value; break; }
}
test_assert(is_array($payload) && 1 === (int) $payload['attempts'], 'ثبت تلاش ناموفق');
test_assert(true === Karsabt_OTP_Service::verify($mobile, $code), 'پذیرش کد صحیح');
test_assert(false === Karsabt_OTP_Service::verify($mobile, $code) instanceof WP_Error, 'کد پس از استفاده دوباره قابل استفاده نیست');

test_assert(true === Karsabt_OTP_Service::send($mobile), 'ارسال کد دوم پس از تأیید قبلی');
$code_key = '';
foreach (array_keys($GLOBALS['karsabt_test_transients']) as $key) {
    if (strpos($key, 'karsabt_otp_code_') === 0) { $code_key = $key; }
}
$expired = $GLOBALS['karsabt_test_transients'][$code_key];
$expired['expires_at'] = time() - 1;
$GLOBALS['karsabt_test_transients'][$code_key] = $expired;
$expired_result = Karsabt_OTP_Service::verify($mobile, $GLOBALS['karsabt_test_otp_code']);
test_assert($expired_result instanceof WP_Error && 'otp_expired' === $expired_result->get_error_code(), 'رد کد منقضی‌شده');

echo "تمام آزمون‌های OTP با موفقیت اجرا شدند.\n";
