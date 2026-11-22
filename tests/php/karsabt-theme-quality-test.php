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
karsabt_test('داده ساختاریافته WebSite و WebPage', strpos($functions, "'@type' => 'WebSite'") !== false && strpos($functions, "'@type' => 'WebPage'") !== false);
karsabt_test('FAQ Schema فقط برای صفحات جزئیات خدمت', strpos($functions, "get_page_template_slug(get_queried_object_id()) !== 'page-templates/service-detail.php'") !== false);
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

// کنترل‌های امنیتی مشترک قالب و افزونه Karsabt Core.
$core_path = $root . '/wordpress/wp-content/plugins/karsabt-core/karsabt-core.php';
$docs_path = $root . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-documents.php';
$otp_path = $root . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-otp.php';
$core = is_file($core_path) ? (string) file_get_contents($core_path) : '';
$docs = is_file($docs_path) ? (string) file_get_contents($docs_path) : '';
$otp = is_file($otp_path) ? (string) file_get_contents($otp_path) : '';

karsabt_test('وجود افزونه مستقل Karsabt Core', is_file($root . '/wordpress/wp-content/plugins/karsabt-core/karsabt-core.php'));
karsabt_test('محدودسازی ارسال سفارش عمومی', strpos($core, 'function karsabt_core_order_rate_limited()') !== false && strpos($core, 'if (karsabt_core_order_rate_limited())') !== false);
karsabt_test('عدم افشای وجود حساب در درخواست OTP', strpos($core, 'حسابی با این شماره پیدا نشد') === false && strpos($core, 'با این شماره قبلاً حساب ساخته شده است. از بخش ورود استفاده کنید') === false && strpos($core, 'درخواست ارسال کد ثبت شد') === false);
karsabt_test('پاسخ یکسان برای خطاهای تأیید OTP', strpos($core, 'کد تأیید معتبر نیست یا منقضی شده است. دوباره درخواست کد بدهید.') !== false);
karsabt_test('محدودسازی ارسال OTP بر اساس IP', strpos($otp, "ip_rate_limited('send'") !== false);
karsabt_test('محدودسازی بررسی OTP بر اساس IP', strpos($otp, "ip_rate_limited('verify'") !== false);
karsabt_test('محدودیت OTP برای هر شماره حفظ شده است', strpos($otp, 'MAX_SENDS_PER_HOUR = 5') !== false && strpos($otp, 'MAX_VERIFY_ATTEMPTS = 5') !== false);
karsabt_test('دریافت مدارک فقط از مسیر کاربران واردشده', strpos($docs, "add_action('admin_post_nopriv_karsabt_document_download'") === false && strpos($docs, "add_action('admin_post_karsabt_document_download'") !== false);
karsabt_test('کنترل مالکیت مشتری برای بارگذاری مدارک', strpos($docs, 'get_post_meta($order_id, \'_karsabt_customer_user_id\', true) !== get_current_user_id()') !== false);
karsabt_test('ذخیره مدارک خارج از web root', strpos($docs, 'KARSABT_PRIVATE_UPLOAD_DIR') !== false && strpos($docs, 'realpath(ABSPATH)') !== false);
karsabt_test('محدودیت نوع و اندازه مدارک', strpos($docs, 'FILEINFO_MIME_TYPE') !== false && strpos($docs, '5 * 1024 * 1024') !== false);
karsabt_test('کنترل مجوز اختصاصی پرونده کارشناس', strpos($core, "add_filter('map_meta_cap', 'karsabt_core_staff_order_caps'") !== false && strpos($core, "return array('do_not_allow')") !== false);
karsabt_test('محدودیت ویرایش کارشناس به پرونده تخصیص‌یافته', strpos($core, 'if ($assigned &&') !== false && strpos($core, "'edit_karsabt_orders'") !== false);
karsabt_test('عدم اجازه حذف پرونده توسط کارشناس', strpos($core, "delete_karsabt_order' ===") !== false && strpos($core, "array('do_not_allow')") !== false);
karsabt_test('جلوگیری از ساخت پرونده توسط نقش کارشناس', strpos($core, "'create_posts'           => 'do_not_allow'") !== false);
karsabt_test('شناسه ورود قطعی و یکتا بر اساس موبایل', strpos($core, "krs_") !== false && strpos($core, "sha256") !== false && strpos($core, "user_login") !== false);
karsabt_test('ثبت‌نام فقط با نقش مشتری عادی', strpos($core, "'role' => 'subscriber'") !== false);
$customer_order_path = $root . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-customer-order.php';
$messages_path = $root . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-messages.php';
$finance_path = $root . '/wordpress/wp-content/plugins/karsabt-core/includes/admin-finance.php';
$customer_order = is_file($customer_order_path) ? (string) file_get_contents($customer_order_path) : '';
$messages = is_file($messages_path) ? (string) file_get_contents($messages_path) : '';
$finance = is_file($finance_path) ? (string) file_get_contents($finance_path) : '';

karsabt_test('جلوگیری از مشاهده پرونده مشتری دیگر', strpos($customer_order, '_karsabt_customer_user_id') !== false && strpos($customer_order, '!== $user_id') !== false);
karsabt_test('جلوگیری از ارسال پیام به پرونده مشتری دیگر', strpos($messages, '!== get_current_user_id()') !== false);
karsabt_test('محدودیت نرخ ارسال پیام مشتری', strpos($messages, 'karsabt_msg_rate_') !== false && strpos($messages, '$sent_count >= 20') !== false);
karsabt_test('محدودیت تعداد پیام هر پرونده', strpos($messages, 'count($messages) >= 200') !== false);
karsabt_test('پنل پیام‌های مدیریتی فقط برای مدیر', strpos($messages, "current_user_can('manage_options')") !== false);
karsabt_test('امور مالی فقط برای مدیر قابل دسترسی است', substr_count($finance, "current_user_can('manage_options')") >= 3);
karsabt_test('ممانعت از پرداخت بیشتر از مانده فاکتور', strpos($finance, '$amount > ($total - $paid)') !== false);
karsabt_test('قفل تراکنشی ثبت پرداخت هم‌زمان', strpos($finance, "FOR UPDATE") !== false && strpos($finance, "START TRANSACTION") !== false && strpos($finance, "COMMIT") !== false && strpos($finance, "ROLLBACK") !== false);
karsabt_test('جدول پرداخت‌ها با موتور تراکنشی InnoDB ساخته می‌شود', strpos($finance, 'ENGINE=InnoDB') !== false);
karsabt_test('بازخوانی مبلغ فاکتور پس از قفل تراکنشی', strpos($finance, '_karsabt_invoice_amount') !== false && strpos($finance, '$fresh_paid = karsabt_finance_invoice_paid($invoice_id)') !== false);
karsabt_test('نمایش فاکتور فقط به مالک حساب', strpos($finance, '_karsabt_invoice_customer_user_id') !== false && strpos($finance, '!== $user_id') !== false);



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
$services_template = (string) file_get_contents($theme . '/page-templates/services.php');
$detail_template = (string) file_get_contents($theme . '/page-templates/service-detail.php');
$theme_js = (string) file_get_contents($theme . '/assets/js/theme.js');

karsabt_test('عنوان صفحه فرود با محتوای وردپرس نمایش داده می‌شود', strpos($landing, 'the_title()') !== false);
karsabt_test('محتوای صفحه فرود در قالب entry-content', strpos($landing, 'the_content()') !== false && strpos($landing, 'entry-content') !== false);
karsabt_test('FAQ صفحه فرود با خروجی امن', strpos($landing, 'esc_html($faq[\'question\'])') !== false && strpos($landing, 'wpautop(esc_html($faq[\'answer\']))') !== false);
karsabt_test('صفحه درباره شامل محتوای ویرایش‌پذیر است', strpos($about, 'the_content()') !== false);
karsabt_test('لینک تلفن در صفحه تماس امن‌سازی شده', strpos($contact, 'esc_url(\'tel:\' . preg_replace') !== false && strpos($contact, 'esc_html($phone)') !== false);
karsabt_test('صفحه تعرفه استعلام خدمت را در لینک منتقل می‌کند', strpos($pricing, 'add_query_arg') !== false && strpos($pricing, "'service'") !== false);
karsabt_test('بسته‌سازی قالب بدون پوشه گیت و تست', strpos($package_workflow, 'karsabt/.git/*') !== false && strpos($package_workflow, 'karsabt/tests/*') !== false);

karsabt_test('لینک خدمات از کلیدهای معتبر فرم سفارش استفاده می‌کند', strpos($services_template, "'company'") !== false && strpos($services_template, "'trademark'") !== false && strpos($services_template, "'home_business'") !== false && strpos($services_template, "'business'") !== false);
karsabt_test('پیش‌انتخاب خدمت از پارامتر URL', strpos($theme_js, 'URLSearchParams(window.location.search)') !== false && strpos($theme_js, 'serviceSelect.value = serviceParam') !== false);
karsabt_test('صفحه جزئیات خدمت از کلید استاندارد فرم استفاده می‌کند', strpos($detail_template, '$karsabt_service_key') !== false && strpos($detail_template, "'ثبت شرکت' => 'company'") !== false && strpos($detail_template, "add_query_arg('service', $karsabt_service_key") !== false);
karsabt_test('لینک داخلی دایرکتوری به صفحه جزئیات منتشرشده', strpos($services_template, 'get_page_by_path') !== false && strpos($services_template, 'مشاهده جزئیات') !== false);

$failed = array();
foreach ($checks as $check) {
    echo ($check['ok'] ? '[موفق] ' : '[خطا] ') . $check['label'] . PHP_EOL;
    if (!$check['ok']) { $failed[] = $check['label']; }
}
echo PHP_EOL . count($checks) . ' بررسی؛ ' . (count($checks) - count($failed)) . ' موفق؛ ' . count($failed) . ' ناموفق.' . PHP_EOL;
if ($failed) { exit(1); }
