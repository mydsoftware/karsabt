<?php
/**
 * آزمون رقابت هم‌زمان روی MariaDB/InnoDB.
 * دو worker مستقل با دو اتصال دیتابیس، قفل ردیف فاکتور و GET_LOCK را رقابت می‌دهند.
 */
declare(strict_types=1);

$wpPath = getenv('KARSABT_WP_PATH');
if (!$wpPath || !is_file($wpPath . '/wp-load.php') || !function_exists('pcntl_fork')) {
    fwrite(STDERR, "وردپرس یا افزونه pcntl در دسترس نیست.\n");
    exit(1);
}
require $wpPath . '/wp-load.php';
global $wpdb;

if (!class_exists('mysqli')) {
    fwrite(STDERR, "افزونه mysqli در دسترس نیست.\n");
    exit(1);
}

function karsabt_test_db(): mysqli {
    $host = DB_HOST;
    $port = 3306;
    if (strpos($host, ':') !== false) {
        list($host, $portText) = explode(':', $host, 2);
        $port = (int) $portText;
    }
    $db = new mysqli($host, DB_USER, DB_PASSWORD, DB_NAME, $port);
    $db->set_charset('utf8mb4');
    return $db;
}

function karsabt_test_assert(bool $ok, string $label): void {
    fwrite($ok ? STDOUT : STDERR, ($ok ? '[موفق] ' : '[ناموفق] ') . $label . "\n");
    if (!$ok) {
        exit(1);
    }
}

$table = karsabt_finance_table();
$engine = $wpdb->get_var($wpdb->prepare(
    'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
    $table
));
karsabt_test_assert(strtolower((string) $engine) === 'innodb', 'جدول پرداخت‌ها InnoDB است');

$invoiceId = wp_insert_post(array(
    'post_type' => 'karsabt_invoice',
    'post_status' => 'private',
    'post_title' => 'فاکتور آزمون هم‌زمانی ' . wp_generate_uuid4(),
), true);
if (is_wp_error($invoiceId)) {
    fwrite(STDERR, $invoiceId->get_error_message() . "\n");
    exit(1);
}
update_post_meta($invoiceId, '_karsabt_invoice_amount', 100);
$wpdb->delete($table, array('invoice_id' => $invoiceId));

$dir = sys_get_temp_dir() . '/karsabt-concurrency-' . bin2hex(random_bytes(6));
mkdir($dir, 0700, true);
$start = $dir . '/start';
$outcomes = array($dir . '/worker-1', $dir . '/worker-2');
$pids = array();

for ($i = 0; $i < 2; $i++) {
    $pid = pcntl_fork();
    if (-1 === $pid) {
        throw new RuntimeException('fork failed');
    }
    if (0 === $pid) {
        while (!file_exists($start)) { usleep(10000); }
        try {
            $db = karsabt_test_db();
            $db->begin_transaction();
            $stmt = $db->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID = ? FOR UPDATE");
            $stmt->bind_param('i', $invoiceId);
            $stmt->execute();
            $stmt->store_result();
            if (0 === $stmt->num_rows) {
                throw new RuntimeException('invoice row missing');
            }
            $sum = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE invoice_id = ?");
            $sum->bind_param('i', $invoiceId);
            $sum->execute();
            $sum->bind_result($paid);
            $sum->fetch();
            $sum->close();
            $amount = 70;
            if ($amount <= max(0, 100 - (int) $paid)) {
                $insert = $db->prepare("INSERT INTO {$table} (invoice_id, amount, method, reference, note, created_by, created_at) VALUES (?, ?, 'cash', '', 'concurrency test', 1, NOW())");
                $insert->bind_param('ii', $invoiceId, $amount);
                $insert->execute();
                file_put_contents($outcomes[$i], 'accepted');
                $insert->close();
            } else {
                file_put_contents($outcomes[$i], 'rejected');
            }
            $stmt->close();
            $db->commit();
            $db->close();
            exit(0);
        } catch (Throwable $e) {
            file_put_contents($outcomes[$i], 'error:' . $e->getMessage());
            exit(2);
        }
    }
    $pids[] = $pid;
}
touch($start);
$exitCodes = array();
foreach ($pids as $pid) {
    pcntl_waitpid($pid, $status);
    $exitCodes[] = pcntl_wifexited($status) ? pcntl_wexitstatus($status) : 99;
}
$results = array_map(static function ($path) {
    return is_file($path) ? trim((string) file_get_contents($path)) : 'missing';
}, $outcomes);
$paid = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE invoice_id = %d", $invoiceId));
$accepted = count(array_filter($results, static function ($v) { return 'accepted' === $v; }));
karsabt_test_assert(array(0, 0) === $exitCodes, 'هر دو worker مستقل بدون خطا پایان یافتند');
karsabt_test_assert(1 === $accepted && 70 === $paid, 'از دو پرداخت هم‌زمان ۷۰ از ۱۰۰، فقط یکی پذیرفته شد و مانده منفی نشد');

$lockName = 'karsabt_ci_' . bin2hex(random_bytes(8));
$firstReady = $dir . '/first-ready';
$secondResult = $dir . '/second-result';
$first = pcntl_fork();
if (0 === $first) {
    $db = karsabt_test_db();
    $stmt = $db->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->bind_param('s', $lockName);
    $stmt->execute();
    $stmt->bind_result($got);
    $stmt->fetch();
    $stmt->close();
    if (1 !== (int) $got) { exit(3); }
    touch($firstReady);
    usleep(1200000);
    $stmt = $db->prepare('SELECT RELEASE_LOCK(?)');
    $stmt->bind_param('s', $lockName);
    $stmt->execute();
    $stmt->close();
    $db->close();
    exit(0);
}
$second = pcntl_fork();
if (0 === $second) {
    while (!file_exists($firstReady)) { usleep(10000); }
    $db = karsabt_test_db();
    $began = microtime(true);
    $stmt = $db->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->bind_param('s', $lockName);
    $stmt->execute();
    $stmt->bind_result($got);
    $stmt->fetch();
    $stmt->close();
    file_put_contents($secondResult, (string) (microtime(true) - $began) . ':' . (int) $got);
    if (1 === (int) $got) {
        $stmt = $db->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->bind_param('s', $lockName);
        $stmt->execute();
        $stmt->close();
    }
    $db->close();
    exit(0);
}
pcntl_waitpid($first, $s1);
pcntl_waitpid($second, $s2);
$lockData = is_file($secondResult) ? explode(':', trim((string) file_get_contents($secondResult))) : array(0, 0);
$waited = (float) ($lockData[0] ?? 0);
$gotLock = (int) ($lockData[1] ?? 0);
karsabt_test_assert(1 === $gotLock && $waited >= 1.0, 'اتصال دوم تا آزاد شدن قفل named lock منتظر ماند');

$wpdb->delete($table, array('invoice_id' => $invoiceId));
wp_delete_post($invoiceId, true);
foreach (glob($dir . '/*') as $file) { @unlink($file); }
@rmdir($dir);
fwrite(STDOUT, "آزمون رقابت دیتابیس پایان یافت.\n");
