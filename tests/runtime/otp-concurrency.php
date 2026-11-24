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
$mobileKey = hash_hmac('sha256', $mobile, wp_salt('auth'));
delete_transient('karsabt_otp_code_' . $mobileKey);
delete_transient('karsabt_otp_cooldown_' . $mobileKey);
delete_transient('karsabt_otp_hour_' . $mobileKey);
if (1 !== $successes) {
    fwrite(STDERR, 'مصرف هم‌زمان OTP باید دقیقاً یک موفقیت داشته باشد؛ خروجی workerها: ' . implode(',', $exitCodes) . "\n");
    exit(1);
}
fwrite(STDOUT, "آزمون مصرف هم‌زمان OTP موفق بود؛ از دو درخواست هم‌زمان دقیقاً یکی پذیرفته شد.\n");

// دوازده پردازش با IP یکسان و شماره‌های متفاوت؛ فقط ده درخواست باید مجاز باشند.
$testIp = '198.51.100.23';
$ipKey = 'karsabt_otp_ip_send_' . hash('sha256', $testIp);
delete_transient($ipKey);
$sendWorker = __DIR__ . '/otp-send-worker.php';
$sendProcesses = array();
$sendMobiles = array();
for ($i = 0; $i < 12; $i++) {
    $targetMobile = '0913' . str_pad((string) (100000 + $i), 6, '0', STR_PAD_LEFT);
    $sendMobiles[] = $targetMobile;
    $pipes = array();
    $process = proc_open(array(PHP_BINARY, $sendWorker, $targetMobile), array(
        0=>array('pipe','r'), 1=>array('pipe','w'), 2=>array('pipe','w')
    ), $pipes, null, null);
    if (!is_resource($process)) {
        fwrite(STDERR, "راه‌اندازی worker محدودیت IP ناموفق بود.\n");
        exit(1);
    }
    fclose($pipes[0]);
    $sendProcesses[] = array('process'=>$process,'stdout'=>$pipes[1],'stderr'=>$pipes[2]);
}
$allowedSends = 0;
$sendCodes = array();
foreach ($sendProcesses as $item) {
    stream_get_contents($item['stdout']);
    $stderr = stream_get_contents($item['stderr']);
    fclose($item['stdout']);
    fclose($item['stderr']);
    $exitCode = proc_close($item['process']);
    $sendCodes[] = $exitCode;
    if (0 === $exitCode) { $allowedSends++; }
    if ('' !== $stderr) { fwrite(STDERR, $stderr); }
}
delete_transient($ipKey);
foreach ($sendMobiles as $targetMobile) {
    $key = hash_hmac('sha256', $targetMobile, wp_salt('auth'));
    delete_transient('karsabt_otp_code_' . $key);
    delete_transient('karsabt_otp_cooldown_' . $key);
    delete_transient('karsabt_otp_hour_' . $key);
}
if (10 !== $allowedSends) {
    fwrite(STDERR, 'محدودیت IP باید دقیقاً ده درخواست از دوازده درخواست هم‌زمان را بپذیرد؛ خروجی workerها: ' . implode(',', $sendCodes) . "\n");
    exit(1);
}
fwrite(STDOUT, "آزمون محدودیت نرخ IP موفق بود؛ از دوازده درخواست هم‌زمان دقیقاً ده درخواست پذیرفته شد.\n");
