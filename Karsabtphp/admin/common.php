<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/storage.php';

$GLOBALS['config'] = $config;
$storage = karsabt_storage_dir();

try {
    $pdo = karsabt_database_connection($config);
} catch (Throwable $e) {
    http_response_code(503);
    error_log('Karsabt MySQL: ' . $e->getMessage());
    exit('اتصال پایگاه داده برقرار نشد؛ اطلاعات MySQL و فعال بودن PDO_MYSQL را بررسی کنید.');
}

$GLOBALS['pdo'] = $pdo;

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('karsabt_admin');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();
}

if (!empty($_SESSION['admin_id']) && !empty($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
    header('Location: /admin/login.php?expired=1');
    exit;
}
if (!empty($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
}

function admin_e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function admin_verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('درخواست نامعتبر است. صفحه را تازه‌سازی کنید.');
    }
}

function admin_require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

function admin_ip_hash(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash_hmac('sha256', $ip, karsabt_security_salt());
}

function admin_login_locked(): bool
{
    global $pdo;
    $q = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_hash = ? AND attempted_at >= ?');
    $q->execute([admin_ip_hash(), time() - 900]);
    return (int) $q->fetchColumn() >= 8;
}

function admin_login_record(bool $success): void
{
    global $pdo;
    $ip = admin_ip_hash();
    if ($success) {
        $q = $pdo->prepare('DELETE FROM login_attempts WHERE ip_hash = ?');
        $q->execute([$ip]);
        return;
    }
    $q = $pdo->prepare('INSERT INTO login_attempts (ip_hash, attempted_at) VALUES (?, ?)');
    $q->execute([$ip, time()]);
    $cutoff = time() - 86400;
    $q = $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < ?');
    $q->execute([$cutoff]);
}

function admin_header(string $title): void
{
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
    }
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= admin_e($title) ?> | مدیریت کارثبت</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<main>
<nav class="admin-nav" aria-label="منوی مدیریت">
  <a href="/">← مشاهده سایت</a>
  <a href="/admin/">داشبورد</a>
  <a href="/admin/pages.php">صفحات و تعرفه</a>
  <a href="/admin/articles.php">مقالات</a>
  <a href="/admin/security.php">امنیت</a>
  <a href="/admin/backup.php">بکاپ</a>
  <a href="/admin/content-planner.php">برنامه محتوا</a>
  <a href="/admin/ai-drafts.php">دستیار مقاله</a>
  <a href="/admin/ai-settings.php">تنظیمات AI</a>
  <a href="/admin/logout.php">خروج</a>
</nav>
<h1><?= admin_e($title) ?></h1>
<?php
}
