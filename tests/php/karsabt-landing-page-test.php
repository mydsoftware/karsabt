<?php
/** آزمون ایستای الگوی لندینگ تک‌صفحه‌ای ثبت شرکت. */
$root = dirname(__DIR__, 2);
$theme = $root . '/wordpress/wp-content/themes/karsabt';
$template = $theme . '/page-templates/landing-page.php';
$source = is_file($template) ? file_get_contents($template) : '';
$css_path = $theme . '/assets/css/design-system.css';
$css = is_file($css_path) ? file_get_contents($css_path) : '';
$checks = array(
 'فایل الگوی صفحه فرود موجود است' => is_file($template),
 'الگوی مستقل تک‌صفحه‌ای تعریف شده' => strpos($source, 'Template Name: لندینگ تک‌صفحه‌ای ثبت شرکت کارثبت') !== false,
 'فرم سفارش در لندینگ وجود ندارد' => strpos($source, 'karsabt_order_form') === false && strpos($source, '<form') === false,
 'ارتباط تلفنی مستقیم وجود دارد' => strpos($source, 'tel:') !== false,
 'ارتباط پیام‌رسان‌ها پشتیبانی می‌شود' => strpos($source, 'karsabt_whatsapp_url') !== false && strpos($source, 'karsabt_telegram_url') !== false && strpos($source, 'karsabt_rubika_url') !== false,
 'لندینگ مستقل از هدر و فوتر عمومی است' => strpos($source, 'ks-one-page-landing') !== false && strpos($css, 'ks-one-page-landing') !== false,
 'بخش‌های محتوایی سئو محور وجود دارند' => strpos($source, 'ثبت شرکت در ایران') !== false && strpos($source, 'مراحل ثبت شرکت') !== false && strpos($source, 'چه اطلاعاتی لازم است') !== false,
 'داده ساختاریافته خدمت تولید می‌شود' => strpos($source, "'@type' => 'Service'") !== false && strpos($source, 'application/ld+json') !== false,
 'پرسش‌های متداول و اسکیما پشتیبانی می‌شوند' => strpos($source, '_karsabt_service_faq') !== false && strpos($source, "'@type' => 'FAQPage'") !== false,
 'طراحی موبایل‌محور و نوار تماس ثابت وجود دارد' => strpos($css, '@media(max-width:680px)') !== false && strpos($css, 'ks-onepage__mobile-bar') !== false && strpos($source, 'ks-onepage__mobile-bar') !== false,
 'راهنمای فارسی لندینگ موجود است' => is_file($theme . '/docs/LANDING-PAGES-FA.md'),
);
$failed = array();
foreach ($checks as $label => $ok) { echo ($ok ? '[موفق] ' : '[خطا] ') . $label . PHP_EOL; if (!$ok) { $failed[] = $label; } }
echo PHP_EOL . count($checks) . ' بررسی؛ ' . (count($checks) - count($failed)) . ' موفق؛ ' . count($failed) . ' ناموفق.' . PHP_EOL;
if ($failed) { exit(1); }
