<?php
/**
 * شمارنده ناشناس رویدادهای مجاز؛ بدون ذخیره IP یا کوکی.
 */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$allowed = ['phone', 'whatsapp', 'telegram', 'rubika', 'eitaa'];
$event = (string) ($_POST['event'] ?? $_GET['event'] ?? '');
if (!in_array($event, $allowed, true)) {
    http_response_code(400);
    exit('invalid event');
}

require_once __DIR__ . '/includes/storage.php';

try {
    $db = karsabt_database_connection();
    $q = $db->prepare(
        'INSERT INTO click_stats (event_key, count, updated_at) VALUES (?, 1, ?)
         ON DUPLICATE KEY UPDATE count = count + 1, updated_at = VALUES(updated_at)'
    );
    $q->execute([$event, date('c')]);
} catch (Throwable $e) {
    error_log('Karsabt click track: ' . $e->getMessage());
}

http_response_code(204);
