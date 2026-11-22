<?php
/**
 * Template Name: لندینگ تک‌صفحه‌ای ثبت شرکت کارثبت
 * Template Post Type: page
 * Description: صفحه فرود مستقل و موبایل‌محور برای جذب متقاضیان ثبت شرکت.
 * @package Karsabt
 */
if (!defined('ABSPATH')) { exit; }
add_filter('body_class', function ($classes) { $classes[] = 'ks-one-page-landing'; return $classes; });
$ks_post_id = get_queried_object_id();
$ks_phone = trim((string) get_theme_mod('karsabt_contact_phone', ''));
$ks_phone_link = preg_replace('/[^0-9+]/', '', $ks_phone);
$ks_whatsapp = trim((string) get_theme_mod('karsabt_whatsapp_url', ''));
$ks_telegram = trim((string) get_theme_mod('karsabt_telegram_url', ''));
$ks_rubika = trim((string) get_theme_mod('karsabt_rubika_url', ''));
$ks_eitaa = trim((string) get_theme_mod('karsabt_eitaa_url', ''));
$ks_faq_items = get_post_meta($ks_post_id, '_karsabt_service_faq', true);
$ks_faq_items = is_array($ks_faq_items) ? $ks_faq_items : array();
$ks_description = has_excerpt($ks_post_id) ? get_the_excerpt($ks_post_id) : 'خدمات ثبت شرکت در ایران با راهنمایی مرحله‌به‌مرحله کارثبت؛ بررسی شرایط، انتخاب نوع شرکت، آماده‌سازی مدارک و پیگیری فرایند ثبت. برای دریافت مشاوره از طریق تماس یا پیام‌رسان‌ها ارتباط بگیرید.';
get_header();
?>
<main id="main-content" class="ks-onepage" dir="rtl">
<header class="ks-onepage__header"><div class="ks-onepage__container ks-onepage__header-inner">
<a class="ks-onepage__brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="کارثبت، صفحه اصلی"><span class="ks-onepage__brand-mark" aria-hidden="true">ک</span><span><strong>کارثبت</strong><small>KARSABT.IR</small></span></a>
<a class="ks-onepage__header-link" href="#ks-contact">مشاوره ثبت شرکت <span aria-hidden="true">←</span></a>
</div></header>
<section class="ks-onepage__hero" aria-labelledby="ks-hero-title"><div class="ks-onepage__container ks-onepage__hero-grid">
<div class="ks-onepage__hero-copy"><span class="ks-onepage__eyebrow">شروع کسب‌وکار رسمی، با مسیر روشن‌تر</span>
<h1 id="ks-hero-title"><?php echo esc_html(get_the_title($ks_post_id) ?: 'ثبت شرکت در ایران'); ?></h1>
<p class="ks-onepage__lead"><?php echo esc_html($ks_description); ?></p>
<ul class="ks-onepage__ticks"><li>راهنمایی برای انتخاب نوع مناسب شرکت</li><li>بررسی مدارک و اطلاعات موردنیاز</li><li>همراهی در مراحل ثبت و پیگیری پرونده</li></ul>
<div class="ks-onepage__actions">
<?php if ($ks_phone_link) : ?><a class="ks-onepage__button ks-onepage__button--primary" href="tel:<?php echo esc_attr($ks_phone_link); ?>">تماس و دریافت مشاوره</a><?php endif; ?>
<?php if ($ks_whatsapp) : ?><a class="ks-onepage__button ks-onepage__button--whatsapp" href="<?php echo esc_url($ks_whatsapp); ?>" target="_blank" rel="noopener noreferrer">پیام در واتساپ</a><?php endif; ?>
<?php if (!$ks_phone_link && !$ks_whatsapp && !$ks_telegram && !$ks_rubika && !$ks_eitaa) : ?><a class="ks-onepage__button ks-onepage__button--primary" href="#ks-contact">راه‌های ارتباطی</a><?php endif; ?>
</div><p class="ks-onepage__microcopy">بدون فرم اینترنتی؛ ارتباط مستقیم از طریق تماس و پیام‌رسان‌ها</p></div>
<div class="ks-onepage__hero-visual" aria-hidden="true"><div class="ks-onepage__visual-orbit"></div><div class="ks-onepage__document"><div class="ks-onepage__doc-top"><span></span><span></span><span></span></div><div class="ks-onepage__doc-seal">✓</div><b>مسیر ثبت شرکت</b><i></i><i></i><i></i><div class="ks-onepage__doc-stamp">KARSABT</div></div><div class="ks-onepage__floating ks-onepage__floating--one">مدارک و مراحل</div><div class="ks-onepage__floating ks-onepage__floating--two">همراهی تا ثبت</div></div>
</div><div class="ks-onepage__hero-bottom"><span>کارثبت؛ همراه شروع رسمی کسب‌وکار شما</span><a href="#ks-services">آشنایی با خدمات <span aria-hidden="true">↓</span></a></div></section>
<section class="ks-onepage__trust-strip" aria-label="ویژگی‌های خدمات"><div class="ks-onepage__container ks-onepage__trust-grid"><div><b>۱</b><span>بررسی نیاز و شرایط شما</span></div><div><b>۲</b><span>شفاف‌سازی مدارک و مراحل</span></div><div><b>۳</b><span>پیگیری فرایند ثبت</span></div></div></section>
<section id="ks-services" class="ks-onepage__section"><div class="ks-onepage__container"><div class="ks-onepage__section-heading"><span class="ks-onepage__eyebrow">خدمات کارثبت</span><h2>برای شروع، چه خدماتی نیاز دارید؟</h2><p>با توجه به هدف و شرایط فعالیتتان، مسیر مناسب ثبت و راه‌اندازی کسب‌وکار را بررسی می‌کنیم.</p></div>
<div class="ks-onepage__service-grid">
<article class="ks-onepage__service-card"><span>🏢</span><h3>ثبت شرکت</h3><p>راهنمایی برای انتخاب قالب حقوقی، تعیین موضوع فعالیت، آماده‌سازی اطلاعات و طی مراحل ثبت شرکت.</p></article>
<article class="ks-onepage__service-card"><span>®️</span><h3>ثبت برند و علامت تجاری</h3><p>بررسی اولیه نام و طبقه فعالیت و راهنمایی درباره مدارک و مراحل ثبت علامت تجاری.</p></article>
<article class="ks-onepage__service-card"><span>📄</span><h3>مجوزهای کسب‌وکار</h3><p>راهنمایی برای شناسایی مجوزهای مرتبط با فعالیت و پیگیری مراحل درخواست آن‌ها.</p></article>
</div></div></section>
<section class="ks-onepage__section ks-onepage__section--soft"><div class="ks-onepage__container ks-onepage__two-col"><div><span class="ks-onepage__eyebrow">انتخاب آگاهانه</span><h2>کدام نوع شرکت برای فعالیت شما مناسب‌تر است؟</h2><p>انتخاب نوع شرکت به تعداد شرکا، موضوع فعالیت، میزان سرمایه، مسئولیت اعضا و برنامه آینده کسب‌وکار بستگی دارد. پیش از شروع ثبت، بهتر است این موارد بررسی شوند تا ساختار انتخابی با نیاز واقعی شما هماهنگ باشد.</p><p>شرکت با مسئولیت محدود و شرکت سهامی خاص از قالب‌های رایج هستند؛ اما مناسب‌بودن هرکدام باید با توجه به شرایط متقاضی و مقررات جاری سنجیده شود.</p></div>
<div class="ks-onepage__choice-list"><div><b>شرکت با مسئولیت محدود</b><span>برای بررسی کسب‌وکارهایی با ساختار شرکای محدود</span></div><div><b>شرکت سهامی خاص</b><span>برای بررسی کسب‌وکارهایی با ساختار سهام‌داری</span></div><div><b>سایر قالب‌های ثبتی</b><span>متناسب با موضوع و هدف فعالیت شما</span></div></div></div></section>
<section class="ks-onepage__section"><div class="ks-onepage__container"><div class="ks-onepage__section-heading"><span class="ks-onepage__eyebrow">فرایند همکاری</span><h2>مراحل ثبت شرکت چگونه پیش می‌رود؟</h2><p>جزئیات و زمان انجام کار به نوع شرکت، کامل‌بودن مدارک و وضعیت پرونده بستگی دارد.</p></div>
<div class="ks-onepage__steps"><article><b>۰۱</b><h3>مشاوره و بررسی اولیه</h3><p>نوع فعالیت، شرکا و هدف شما بررسی می‌شود.</p></article><article><b>۰۲</b><h3>آماده‌سازی مدارک</h3><p>اطلاعات و مدارک لازم متناسب با پرونده اعلام می‌شود.</p></article><article><b>۰۳</b><h3>تنظیم و ارسال درخواست</h3><p>اطلاعات ثبتی برای طی مراحل قانونی آماده و پیگیری می‌شود.</p></article><article><b>۰۴</b><h3>پیگیری تا نتیجه</h3><p>وضعیت درخواست و موارد اصلاحی احتمالی دنبال می‌شود.</p></article></div></div></section>
<section class="ks-onepage__section ks-onepage__section--soft"><div class="ks-onepage__container ks-onepage__two-col ks-onepage__documents"><div><span class="ks-onepage__eyebrow">آمادگی پیش از ثبت</span><h2>برای ثبت شرکت چه اطلاعاتی لازم است؟</h2><p>فهرست دقیق مدارک پس از مشخص‌شدن نوع شرکت و شرایط متقاضی اعلام می‌شود. معمولاً اطلاعات هویتی اعضا، نشانی و کدپستی، موضوع فعالیت، میزان سرمایه و سمت اعضا از مواردی هستند که باید بررسی شوند.</p><p>اگر هنوز درباره نام شرکت، موضوع فعالیت یا نوع شرکت تصمیم نگرفته‌اید، پیش از تهیه مدارک با ما مشورت کنید.</p></div>
<ul class="ks-onepage__checklist"><li>اطلاعات هویتی شرکا و مدیران</li><li>نشانی و کدپستی محل شرکت</li><li>نام‌های پیشنهادی شرکت</li><li>موضوع و حدود فعالیت</li><li>سرمایه و سهم‌الشرکه یا سهام</li><li>سمت‌ها و حق امضای اعضا</li></ul></div></section>
<?php if ($ks_faq_items) : ?><section class="ks-onepage__section ks-onepage__faq" aria-labelledby="ks-faq-title"><div class="ks-onepage__container"><div class="ks-onepage__section-heading"><span class="ks-onepage__eyebrow">پاسخ پرسش‌ها</span><h2 id="ks-faq-title">سؤالات متداول ثبت شرکت</h2></div><div class="ks-onepage__faq-list">
<?php foreach ($ks_faq_items as $ks_faq) : if (empty($ks_faq['question']) || empty($ks_faq['answer'])) { continue; } ?><details><summary><?php echo esc_html($ks_faq['question']); ?></summary><div><?php echo wpautop(esc_html($ks_faq['answer'])); ?></div></details><?php endforeach; ?>
</div></div></section><?php endif; ?>
<section id="ks-contact" class="ks-onepage__contact"><div class="ks-onepage__container ks-onepage__contact-inner"><span class="ks-onepage__eyebrow">قدم اول را بردارید</span><h2>برای ثبت شرکت از کجا شروع کنیم؟</h2><p>نوع فعالیت و شرایطتان را از طریق تماس یا پیام‌رسان‌ها مطرح کنید تا برای انتخاب مسیر و مدارک موردنیاز راهنمایی شوید.</p>
<div class="ks-onepage__contact-actions">
<?php if ($ks_phone_link) : ?><a class="ks-onepage__button ks-onepage__button--light" href="tel:<?php echo esc_attr($ks_phone_link); ?>">☎ تماس تلفنی · <?php echo esc_html($ks_phone); ?></a><?php endif; ?>
<?php if ($ks_whatsapp) : ?><a class="ks-onepage__button ks-onepage__button--whatsapp" href="<?php echo esc_url($ks_whatsapp); ?>" target="_blank" rel="noopener noreferrer">واتساپ</a><?php endif; ?>
<?php if ($ks_telegram) : ?><a class="ks-onepage__button ks-onepage__button--light" href="<?php echo esc_url($ks_telegram); ?>" target="_blank" rel="noopener noreferrer">تلگرام</a><?php endif; ?>
<?php if ($ks_rubika) : ?><a class="ks-onepage__button ks-onepage__button--light" href="<?php echo esc_url($ks_rubika); ?>" target="_blank" rel="noopener noreferrer">روبیکا</a><?php endif; ?>
<?php if ($ks_eitaa) : ?><a class="ks-onepage__button ks-onepage__button--light" href="<?php echo esc_url($ks_eitaa); ?>" target="_blank" rel="noopener noreferrer">ایتا</a><?php endif; ?>
</div>
<?php if (!$ks_phone_link && !$ks_whatsapp && !$ks_telegram && !$ks_rubika && !$ks_eitaa) : ?><p class="ks-onepage__contact-warning">برای نمایش دکمه‌های تماس و پیام‌رسان، اطلاعات ارتباطی را در نمایش ← سفارشی‌سازی ← اطلاعات تماس کارثبت وارد کنید.</p><?php endif; ?>
<small>کارثبت؛ خدمات ثبتی و راهنمایی امور کسب‌وکار</small></div></section>

<?php if ($ks_phone_link || $ks_whatsapp || $ks_telegram || $ks_rubika || $ks_eitaa) : ?>
<nav class="ks-onepage__mobile-bar" aria-label="ارتباط سریع با کارثبت">
<?php if ($ks_phone_link) : ?><a class="ks-mobile-call" href="tel:<?php echo esc_attr($ks_phone_link); ?>">تماس با کارثبت</a><?php endif; ?>
<?php if ($ks_whatsapp) : ?><a class="ks-mobile-message" href="<?php echo esc_url($ks_whatsapp); ?>" target="_blank" rel="noopener noreferrer">پیام در واتساپ</a>
<?php elseif ($ks_telegram) : ?><a class="ks-mobile-message" href="<?php echo esc_url($ks_telegram); ?>" target="_blank" rel="noopener noreferrer">پیام در تلگرام</a>
<?php elseif ($ks_rubika) : ?><a class="ks-mobile-message" href="<?php echo esc_url($ks_rubika); ?>" target="_blank" rel="noopener noreferrer">پیام در روبیکا</a>
<?php elseif ($ks_eitaa) : ?><a class="ks-mobile-message" href="<?php echo esc_url($ks_eitaa); ?>" target="_blank" rel="noopener noreferrer">پیام در ایتا</a><?php endif; ?>
</nav>
<?php endif; ?>
</main>
<?php
$ks_graph = array('@context' => 'https://schema.org', '@graph' => array(
array('@type' => 'Service', '@id' => get_permalink($ks_post_id) . '#company-registration-service', 'name' => 'خدمات ثبت شرکت', 'serviceType' => 'ثبت شرکت در ایران', 'url' => get_permalink($ks_post_id), 'description' => wp_strip_all_tags($ks_description), 'provider' => array('@id' => home_url('/#organization')), 'areaServed' => array('@type' => 'Country', 'name' => 'ایران'), 'availableLanguage' => 'Persian'),
);
$ks_entities = array();
foreach ($ks_faq_items as $ks_faq) { if (empty($ks_faq['question']) || empty($ks_faq['answer'])) { continue; } $ks_entities[] = array('@type' => 'Question', 'name' => wp_strip_all_tags($ks_faq['question']), 'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($ks_faq['answer']))); }
if ($ks_entities) { $ks_graph['@graph'][] = array('@type' => 'FAQPage', '@id' => get_permalink($ks_post_id) . '#faq', 'mainEntity' => $ks_entities); }
echo '<script type="application/ld+json">' . wp_json_encode($ks_graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
?>
<?php get_footer(); ?>
