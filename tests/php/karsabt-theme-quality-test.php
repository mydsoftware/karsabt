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
    'page-templates/landing-page.php',
    'page-templates/about.php',
    'page-templates/contact.php',
    'page-templates/pricing.php',
);
foreach ($required as $file) {
    karsabt_test('وجود فایل ' . $file, is_file($theme . '/' . $file));
}

$theme_json = json_decode((string) file_get_contents($theme . '/theme.json'), true);
karsabt_test('ساختار معتبر theme.json', is_array($theme_json) && isset($theme_json['version']));

$functions = (string) file_get_contents($theme . '/functions.php');
$readme = (string) file_get_contents($theme . '/README.txt');
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
karsabt_test('محافظت JSON-LD در برابر شکستن تگ script', strpos($functions, 'JSON_HEX_TAG') !== false && strpos($functions, 'JSON_HEX_AMP') !== false && strpos($functions, 'JSON_HEX_APOS') !== false && strpos($functions, 'JSON_HEX_QUOT') !== false);
karsabt_test('تشخیص صحیح Rank Math', strpos($functions, "class_exists('\\RankMath\\\\Helper')") !== false);
karsabt_test('نادیده‌گرفتن revision هنگام ذخیره FAQ', strpos($functions, 'wp_is_post_revision($post_id)') !== false);
karsabt_test('نمایش مسیر راهنما در برگه و نوشته', strpos($functions, "is_singular(array('page', 'post'))") !== false);
karsabt_test('مستندات تنظیمات سفارشی‌ساز', is_file($theme . '/docs/CUSTOMIZER-FA.md') && strpos($readme, 'سفارشی‌سازی') !== false);
karsabt_test('کنترل نمایش نوار تماس از سفارشی‌ساز', strpos($header, "karsabt_show_topbar") !== false);
karsabt_test('کنترل نمایش دکمه سربرگ', strpos($header, "karsabt_show_header_cta") !== false);
karsabt_test('کنترل نمایش دکمه موبایل', strpos($footer, "karsabt_show_mobile_cta") !== false);
karsabt_test('تنظیم متن حقوق پابرگ', strpos($footer, "karsabt_footer_copyright") !== false);
karsabt_test('تنظیم رنگ‌های برند با اعتبارسنجی رنگ', strpos($functions, "sanitize_hex_color") !== false && strpos($functions, "wp_add_inline_style") !== false && strpos($functions, "--ks-color-primary:") !== false && strpos($functions, "--ks-color-navy:") !== false);
karsabt_test('عدم نمایش اطلاعات تماس خالی در سربرگ', strpos($header, '$contact_location || $contact_email || $contact_phone') !== false);


karsabt_test('الگوی صفحه فرود کمپین', is_file($theme . '/page-templates/landing-page.php') && strpos((string) file_get_contents($theme . '/page-templates/landing-page.php'), 'ks-landing-request') !== false);
karsabt_test('فرم سفارش در صفحه فرود', is_file($theme . '/page-templates/landing-page.php') && strpos((string) file_get_contents($theme . '/page-templates/landing-page.php'), '[karsabt_order_form]') !== false);
karsabt_test('راهنمای صفحات فرود', is_file($theme . '/docs/LANDING-PAGES-FA.md'));


karsabt_test('قالب درباره ما', is_file($theme . '/page-templates/about.php'));
karsabt_test('قالب تماس با ما', is_file($theme . '/page-templates/contact.php') && strpos((string) file_get_contents($theme . '/page-templates/contact.php'), 'karsabt_contact_phone') !== false);
karsabt_test('قالب تعرفه بدون قیمت ساختگی', is_file($theme . '/page-templates/pricing.php') && strpos((string) file_get_contents($theme . '/page-templates/pricing.php'), 'هزینه و شرایط اجرا') !== false);


$landing = (string) file_get_contents($theme . '/page-templates/landing-page.php');
$about = (string) file_get_contents($theme . '/page-templates/about.php');
$contact = (string) file_get_contents($theme . '/page-templates/contact.php');
$pricing = (string) file_get_contents($theme . '/page-templates/pricing.php');
$package_workflow = (string) file_get_contents($root . '/.github/workflows/package-theme.yml');

karsabt_test('عنوان صفحه فرود با محتوای وردپرس نمایش داده می‌شود', strpos($landing, 'the_title()') !== false);
karsabt_test('محتوای صفحه فرود در قالب entry-content', strpos($landing, 'the_content()') !== false && strpos($landing, 'entry-content') !== false);
karsabt_test('FAQ صفحه فرود با خروجی امن', strpos($landing, 'esc_html($faq[\'question\'])') !== false && strpos($landing, 'wpautop(esc_html($faq[\'answer\']))') !== false);
karsabt_test('صفحه درباره شامل محتوای ویرایش‌پذیر است', strpos($about, 'the_content()') !== false);
karsabt_test('لینک تلفن در صفحه تماس امن‌سازی شده', strpos($contact, 'esc_url(\'tel:\' . preg_replace') !== false && strpos($contact, 'esc_html($phone)') !== false);
karsabt_test('صفحه تعرفه استعلام خدمت را در لینک منتقل می‌کند', strpos($pricing, 'add_query_arg') !== false && strpos($pricing, "'service'") !== false);
karsabt_test('بسته‌سازی قالب بدون پوشه گیت و تست', strpos($package_workflow, 'karsabt/.git/*') !== false && strpos($package_workflow, 'karsabt/tests/*') !== false);

$failed = array();
foreach ($checks as $check) {
    echo ($check['ok'] ? '[موفق] ' : '[خطا] ') . $check['label'] . PHP_EOL;
    if (!$check['ok']) { $failed[] = $check['label']; }
}
echo PHP_EOL . count($checks) . ' بررسی؛ ' . (count($checks) - count($failed)) . ' موفق؛ ' . count($failed) . ' ناموفق.' . PHP_EOL;
if ($failed) { exit(1); }
