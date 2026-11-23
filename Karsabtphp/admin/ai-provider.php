<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/storage.php';

function karsabt_ai_setting(PDO $pdo, string $key): string
{
    $q = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $q->execute([$key]);
    $raw = (string) ($q->fetchColumn() ?: '');
    if ($key === 'ai_api_key') {
        return karsabt_decrypt_secret($raw);
    }
    return $raw;
}

function karsabt_ai_setting_raw(PDO $pdo, string $key): string
{
    $q = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $q->execute([$key]);
    return (string) ($q->fetchColumn() ?: '');
}

function karsabt_ai_generate(PDO $pdo, string $keyword): array
{
    $endpoint = karsabt_ai_setting($pdo, 'ai_endpoint');
    $model = karsabt_ai_setting($pdo, 'ai_model');
    $key = karsabt_ai_setting($pdo, 'ai_api_key');

    if (!$endpoint || !$model || !$key) {
        throw new RuntimeException('اتصال AI تنظیم نشده است. از بخش تنظیمات هوش مصنوعی، آدرس، مدل و کلید API را ثبت کنید.');
    }
    if (!filter_var($endpoint, FILTER_VALIDATE_URL) || strtolower((string) parse_url($endpoint, PHP_URL_SCHEME)) !== 'https') {
        throw new RuntimeException('Endpoint باید یک نشانی HTTPS معتبر باشد.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('افزونه cURL در PHP فعال نیست.');
    }

    $system = 'شما نویسنده فارسی متخصص محتوای خدمات ثبت شرکت، ثبت برند و مجوزهای کسب‌وکار در ایران هستید. اطلاعات قانونی و عددی را جعل نکنید؛ موارد متغیر را به بررسی منبع رسمی ارجاع دهید. خروجی فقط JSON معتبر با کلیدهای seo_title, meta_description, outline, body باشد. متن کاملاً فارسی، کاربردی، غیرتکراری و بدون ادعاهای تضمینی باشد. مقاله را به صورت متن ساده بنویسید و از HTML استفاده نکنید.';

    $payload = [
        'model' => $model,
        'temperature' => 0.65,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => 'برای موضوع «' . $keyword . '» عنوان SEO، توضیحات متا، ساختار بخش‌ها و مقاله کامل را تولید کن. خروجی JSON با کلیدهای seo_title, meta_description, outline, body.'],
        ],
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_TIMEOUT => 70,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('خطا در اتصال به مدل: ' . $err);
    }
    if ($code < 200 || $code >= 300) {
        throw new RuntimeException('سرویس AI پاسخ موفق نداد (HTTP ' . $code . ').');
    }

    $outer = json_decode($raw, true);
    $content = $outer['choices'][0]['message']['content'] ?? '';
    if (!is_string($content) || $content === '') {
        throw new RuntimeException('پاسخ مدل قابل پردازش نیست.');
    }
    $content = preg_replace('/^```(?:json)?|```$/m', '', trim($content));
    $data = json_decode(trim($content), true);
    if (!is_array($data)) {
        throw new RuntimeException('خروجی مدل JSON معتبر نیست.');
    }
    foreach (['seo_title', 'meta_description', 'outline', 'body'] as $f) {
        if (!isset($data[$f]) || !is_string($data[$f])) {
            throw new RuntimeException('ساختار پاسخ مدل ناقص است.');
        }
    }
    return $data;
}
