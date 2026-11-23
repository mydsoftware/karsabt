<?php
declare(strict_types=1);

/**
 * مسیر خصوصی ذخیره‌سازی (بکاپ و داده‌های خارج از document root).
 */
function karsabt_storage_dir(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $config = $GLOBALS['config'] ?? (require dirname(__DIR__) . '/config.php');
    $environmentPath = getenv('KARSABT_STORAGE_DIR');
    $configured = trim((string) (
        ($environmentPath !== false && $environmentPath !== '')
            ? $environmentPath
            : ($config['storage_dir'] ?? '')
    ));

    if ($configured === '') {
        $configured = dirname(dirname(__DIR__)) . '/karsabt-private';
    }

    if ($configured[0] !== DIRECTORY_SEPARATOR && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $configured)) {
        $configured = dirname(__DIR__) . '/' . $configured;
    }

    return $resolved = rtrim($configured, DIRECTORY_SEPARATOR);
}

/**
 * اتصال PDO به MySQL.
 */
function karsabt_database_connection(?array $config = null): PDO
{
    $config = $config ?? ($GLOBALS['config'] ?? require dirname(__DIR__) . '/config.php');
    $db = $config['database'] ?? [];

    foreach (['host', 'name', 'username', 'password'] as $key) {
        if (!isset($db[$key]) || ($key !== 'password' && trim((string) $db[$key]) === '')) {
            throw new RuntimeException('اطلاعات اتصال MySQL در config.php کامل نیست.');
        }
    }

    $dangerous = ['CHANGE_THIS_PASSWORD', 'CPANELPREFIX_karsabt', 'CPANELPREFIX_user'];
    if (in_array((string) $db['password'], $dangerous, true)
        || in_array((string) $db['name'], $dangerous, true)
        || in_array((string) $db['username'], $dangerous, true)
    ) {
        throw new RuntimeException('مقادیر پیش‌فرض config.php هنوز تغییر نکرده‌اند. ابتدا اطلاعات واقعی دیتابیس را وارد کنید.');
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('افزونه PDO_MYSQL برای PHP فعال نیست.');
    }

    $charset = preg_match('/^[a-zA-Z0-9_]+$/', (string) ($db['charset'] ?? 'utf8mb4'))
        ? $db['charset']
        : 'utf8mb4';

    $dsn = 'mysql:host=' . $db['host'] . ';dbname=' . $db['name'] . ';charset=' . $charset;

    return new PDO(
        $dsn,
        (string) $db['username'],
        (string) $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ]
    );
}

function karsabt_security_salt(): string
{
    $config = $GLOBALS['config'] ?? (require dirname(__DIR__) . '/config.php');
    $salt = trim((string) ($config['security_salt'] ?? ''));
    if ($salt === '' || $salt === 'karsabt-local-change-me') {
        $salt = 'karsabt-fallback-change-immediately-' . md5(__DIR__);
        error_log('Karsabt: security_salt در config.php تنظیم نشده یا پیش‌فرض است.');
    }
    return $salt;
}

function karsabt_encrypt_secret(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    $salt = karsabt_security_salt();
    $key = hash('sha256', 'karsabt-ai-key|' . $salt, true);

    if (function_exists('sodium_crypto_secretbox')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $key);
        return 'enc:v1:' . base64_encode($nonce . $cipher);
    }

    if (function_exists('openssl_encrypt')) {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            return $plain;
        }
        return 'enc:v1:' . base64_encode($iv . $cipher);
    }

    return $plain;
}

function karsabt_decrypt_secret(string $stored): string
{
    if ($stored === '' || strpos($stored, 'enc:v1:') !== 0) {
        return $stored;
    }

    $raw = base64_decode(substr($stored, 7), true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }

    $salt = karsabt_security_salt();
    $key = hash('sha256', 'karsabt-ai-key|' . $salt, true);

    if (function_exists('sodium_crypto_secretbox_open')) {
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        return $plain === false ? '' : $plain;
    }

    if (function_exists('openssl_decrypt')) {
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    return '';
}
