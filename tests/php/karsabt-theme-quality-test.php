<?php
/**
 * آزمون‌های ایستا برای کنترل کیفیت قالب کارثبت.
 * اجرا: php tests/php/karsabt-theme-quality-test.php
 */
$root = dirname(__DIR__, 2);
$theme = $root . '/wordpress/wp-content/themes/karsabt';
$checks = array();

function karsabt_test($label, $condition) {
    global $checks;
    $checks[] = array('label' => $label, 'ok' => (bool) $condition);
}

$required = array(
    'style.css',
    'functions.php',
    'header.php',
    'footer.php',
    'front-page.php',
    'page.php',
    'single.php',
    'archive.php',
    'search.php',
    '404.php',
    'theme.json',
    'assets/js/theme.js',
    'assets/css/design-system.css',
    'page-templates/services.php',
    'page-templates/service-detail.php',
);
foreach ($required as $file) {
    karsabt_test('وجود فایل ' . $file, is_file($theme . '/' . $file));
}

$theme_json = json_decode((string) file_get_contents($theme . '/theme.json'), true);
karsabt_test('ساختار معتبر theme.json', is_array($theme_json) && isset($theme_json['version']));

$functions = (string) file_get_contents($theme . '/functions.php');
$header = (string) file_get_contents($theme . '/header.php');
$footer = (string) file_get_contents($theme . '/footer.php');
$js = (string) file_get_contents($theme . '/assets/js/theme.js');
$css = (string) file_get_contents($theme . '/style.css');

karsabt_test('پاک‌سازی ورودی تنظیمات سفارشی‌ساز', strpos($functions, "'sanitize_callback' => 'sanitize_text_field'") !== false && strpos($functions, "'sanitize_callback' => 'esc_url_raw'") !== false);
karsabt_test('وجود محافظ nonce برای ذخیره FAQ', strpos($functions, 'wp_verify_nonce') !== false);
karsabt_test('وجود کنترل سطح دسترسی برای FAQ', strpos($functions, "current_user_can('edit_page'") !== false);
karsabt_test('خروجی امن URL در سربرگ', strpos($header, 'esc_url($cta_url)') !== false);
karsabt_test('خروجی امن متن دکمه در سربرگ', strpos($header, 'esc_html($cta_label)') !== false);
karsabt_test('خروجی امن توضیحات پابرگ', strpos($footer, 'esc_html(get_theme_mod') !== false);
karsabt_test('شناسه و وضعیت دسترس‌پذیر منو', strpos($header, 'aria-controls="primary-navigation"') !== false && strpos($header, 'aria-expanded="false"') !== false);
karsabt_test('پشتیبانی Escape در منوی موبایل', strpos($js, "event.key === 'Escape'") !== false);
karsabt_test('پشتیبانی کاهش حرکت', strpos($css, 'prefers-reduced-motion: reduce') !== false);
karsabt_test('نشانگر تمرکز صفحه‌کلید', strpos($css, 'focus-visible') !== false);
karsabt_test('داده ساختاریافته Service', strpos($functions, "'@type' => 'Service'") !== false);
karsabt_test('عدم نمایش اطلاعات تماس خالی در سربرگ', strpos($header, 'if ($contact_location || $contact_email || $contact_phone)') !== false);

$failed = array();
foreach ($checks as $check) {
    echo ($check['ok'] ? '[موفق] ' : '[خطا] ') . $check['label'] . PHP_EOL;
    if (!$check['ok']) { $failed[] = $check['label']; }
}
echo PHP_EOL . count($checks) . ' بررسی؛ ' . (count($checks) - count($failed)) . ' موفق؛ ' . count($failed) . ' ناموفق.' . PHP_EOL;
if ($failed) { exit(1); }
