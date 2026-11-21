<?php
/**
 * آزمون ایستای الگوی صفحه فرود.
 */
$root = dirname(__DIR__, 2);
$theme = $root . '/wordpress/wp-content/themes/karsabt';
$template = $theme . '/page-templates/landing-page.php';
$checks = array(
 'فایل الگوی صفحه فرود موجود است' => is_file($template),
 'الگوی صفحه فرود در فایل تعریف شده' => is_file($template) && strpos(file_get_contents($template), 'Template Name: صفحه فرود تبلیغاتی کارثبت') !== false,
 'فرم سفارش در الگو استفاده شده' => is_file($template) && strpos(file_get_contents($template), '[karsabt_order_form]') !== false,
 'وجود fallback برای افزونه فرم' => is_file($template) && strpos(file_get_contents($template), 'shortcode_exists') !== false,
 'وجود بخش درخواست با شناسه anchor' => is_file($template) && strpos(file_get_contents($template), 'ks-landing-request') !== false,
 'نمایش FAQ در الگو' => is_file($template) && strpos(file_get_contents($template), '_karsabt_service_faq') !== false,
 'راهنمای صفحه فرود' => is_file($theme . '/docs/LANDING-PAGES-FA.md'),
 'استایل صفحه فرود' => is_file($theme . '/assets/css/design-system.css') && strpos(file_get_contents($theme . '/assets/css/design-system.css'), 'ks-landing__hero') !== false,
);
$failed = array();
foreach ($checks as $label => $ok) {
 echo ($ok ? '[موفق] ' : '[خطا] ') . $label . PHP_EOL;
 if (!$ok) { $failed[] = $label; }
}
echo PHP_EOL . count($checks) . ' بررسی؛ ' . (count($checks)-count($failed)) . ' موفق؛ ' . count($failed) . ' ناموفق.' . PHP_EOL;
if ($failed) { exit(1); }
