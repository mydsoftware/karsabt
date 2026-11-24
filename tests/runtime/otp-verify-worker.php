<?php
/**
 * Worker مستقل برای رقابت واقعی روی مصرف یک‌باره OTP.
 */
$wpPath = getenv('KARSABT_WP_PATH');
if (!$wpPath || !is_file($wpPath . '/wp-load.php') || empty($argv[1]) || empty($argv[2])) {
    fwrite(STDERR, "پارامترهای worker معتبر نیستند.\n");
    exit(2);
}
require $wpPath . '/wp-load.php';
$result = Karsabt_OTP_Service::verify((string) $argv[1], (string) $argv[2]);
exit(true === $result ? 0 : 1);
