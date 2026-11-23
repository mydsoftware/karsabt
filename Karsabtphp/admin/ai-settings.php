<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/ai-provider.php';
admin_require_login();

$msg = '';
$keys = [
    'ai_endpoint' => 'نشانی API (سازگار با OpenAI Chat Completions)',
    'ai_model' => 'نام مدل',
    'ai_api_key' => 'کلید API',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    foreach ($keys as $k => $label) {
        $v = trim($_POST[$k] ?? '');

        if ($k === 'ai_endpoint' && $v !== '' && (!filter_var($v, FILTER_VALIDATE_URL) || parse_url($v, PHP_URL_SCHEME) !== 'https')) {
            $msg = 'Endpoint باید HTTPS باشد.';
            break;
        }

        if ($k === 'ai_api_key' && $v === '' && isset($_POST['keep_key'])) {
            continue;
        }

        if (strlen($v) > 2000) {
            $msg = 'مقدار ورودی بیش از حد طولانی است.';
            break;
        }

        $toStore = ($k === 'ai_api_key' && $v !== '') ? karsabt_encrypt_secret($v) : $v;

        $q = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $q->execute([$k, $toStore]);
    }
    if ($msg === '') {
        $msg = 'تنظیمات AI ذخیره شد.';
    }
}

$hasKey = karsabt_ai_setting_raw($pdo, 'ai_api_key') !== '';

admin_header('تنظیمات هوش مصنوعی');
?>
<section class="card">
  <p>اتصال از طریق API سازگار با OpenAI Chat Completions انجام می‌شود. کلید API با salt امنیتی سایت رمزنگاری و در دیتابیس ذخیره می‌شود. مسیر storage و بکاپ‌ها باید خارج از public_html باشد.</p>
  <?php if ($msg): ?><p><?= admin_e($msg) ?></p><?php endif ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= admin_e(admin_csrf()) ?>">
    <label><?= admin_e($keys['ai_endpoint']) ?></label>
    <input name="ai_endpoint" value="<?= admin_e(karsabt_ai_setting($pdo, 'ai_endpoint')) ?>" placeholder="https://provider.example/v1/chat/completions">
    <label><?= admin_e($keys['ai_model']) ?></label>
    <input name="ai_model" value="<?= admin_e(karsabt_ai_setting($pdo, 'ai_model')) ?>" placeholder="model-name">
    <label><?= admin_e($keys['ai_api_key']) ?></label>
    <input type="password" name="ai_api_key" autocomplete="new-password" placeholder="<?= $hasKey ? 'کلید ذخیره شده؛ برای جایگزینی وارد کنید' : 'کلید API' ?>">
    <label><input type="checkbox" name="keep_key" value="1" checked> در صورت خالی گذاشتن، کلید قبلی حفظ شود</label>
    <button>ذخیره تنظیمات</button>
  </form>
</section>
</main></html>
