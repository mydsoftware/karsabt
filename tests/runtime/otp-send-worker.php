<?php
/**
 * Worker مستقل برای آزمون اتمیک محدودیت نرخ ارسال OTP بر اساس IP.
 */
$wpPath = getenv('KARSABT_WP_PATH');
if (!$wpPath || !is_file($wpPath . '/wp-load.php') || empty($argv[1])) {
    exit(2);
}
$_SERVER['REMOTE_ADDR'] = '198.51.100.23';
require $wpPath . '/wp-load.php';
add_filter('karsabt_send_otp', static function () { return true; });
$result = Karsabt_OTP_Service::send((string) $argv[1]);
exit(true === $result ? 0 : 1);
