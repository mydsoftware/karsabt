<?php
/**
 * آزمون رقابت هم‌زمان روی OTP در MariaDB واقعی.
 */
declare(strict_types=1);
$wpPath = getenv('KARSABT_WP_PATH');
if (!$wpPath || !is_file($wpPath . '/wp-load.php')) {
    fwrite(STDERR, "مسیر وردپرس معتبر نیست.\n");
    exit(1);
}
require $wpPath . '/wp-load.php';
if (!function_exists('proc_open')) {
    fwrite(STDERR, "تابع proc_open برای آزمون هم‌زمانی در دسترس نیست.\n");
    exit(1);
}
$mobile = '0912' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$code = '';
add_filter('karsabt_send_otp', static function ($accepted, $target, $generated) use (&$code, $mobile) {
    if ($target === $mobile) { $code = (string) $generated; }
    return true;
}, 10, 3);
$sent = Karsabt_OTP_Service::send($mobile);
if (true !== $sent || !preg_match('/^[0-9]{6}$/', $code)) {
    fwrite(STDERR, "آماده‌سازی کد OTP ناموفق بود.\n");
    exit(1);
}
$worker = __DIR__ . '/otp-verify-worker.php';
$processes = array();
for ($i = 0; $i < 2; $i++) {
    $pipes = array();
    $process = proc_open(array(PHP_BINARY, $worker, $mobile, $code), array(
        0=>array('pipe','r'), 1=>array('pipe','w'), 2=>array('pipe','w')
    ), $pipes, null, null);
    if (!is_resource($process)) {
        fwrite(STDERR, "راه‌اندازی worker شماره " . ($i + 1) . " ناموفق بود.\n");
        exit(1);
    }
    fclose($pipes[0]);
    $processes[] = array('process'=>$process,'stdout'=>$pipes[1],'stderr'=>$pipes[2]);
}
$successes = 0;
$exitCodes = array();
foreach ($processes as $item) {
    $stdout = stream_get_contents($item['stdout']);
    $stderr = stream_get_contents($item['stderr']);
    fclose($item['stdout']);
    fclose($item['stderr']);
    $exitCode = proc_close($item['process']);
    $exitCodes[] = $exitCode;
    if (0 === $exitCode) { $successes++; }
    if ('' !== $stderr) { fwrite(STDERR, $stderr); }
}
delete_transient('karsabt_otp_code_' . hash_hmac('sha256', $mobile, wp_salt('auth')));
delete_transient('karsabt_otp_cooldown_' . hash_hmac('sha256', $mobile, wp_salt('auth')));
if (1 !== $successes) {
    fwrite(STDERR, 'مصرف هم‌زمان OTP باید دقیقاً یک موفقیت داشته باشد؛ خروجی workerها: ' . implode(',', $exitCodes) . "\n");
    exit(1);
}
fwrite(STDOUT, "آزمون مصرف هم‌زمان OTP موفق بود؛ از دو درخواست هم‌زمان دقیقاً یکی پذیرفته شد.\n");
