<?php
/**
 * Template Name: صفحه خدمات کارثبت
 * Template Post Type: page
 *
 * صفحه معرفی خدمات؛ در صورت ساخت صفحه با Elementor، محتوای Elementor اولویت دارد.
 *
 * @package Karsabt
 */
if (!defined('ABSPATH')) { exit; }
get_header();

$is_elementor = get_post_meta(get_queried_object_id(), '_elementor_edit_mode', true) === 'builder';
?>
<main id="main-content" class="ks-services-page">
<?php if ($is_elementor) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <div class="elementor-page-content"><?php the_content(); ?></div>
    <?php endwhile; ?>
<?php else : ?>
    <?php
    $services = array(
        array('🏢', 'ثبت شرکت', 'ثبت انواع شرکت، تنظیم مدارک و پیگیری مراحل قانونی.', 'ثبت شرکت', 'ثبت شرکت'),
        array('™️', 'ثبت برند', 'انتخاب مسیر مناسب برای ثبت و حفاظت از نام و نشان تجاری.', 'ثبت برند', 'ثبت برند'),
        array('🪪', 'کارت بازرگانی', 'راهنمایی و پیگیری درخواست کارت بازرگانی.', 'کارت بازرگانی', 'کارت بازرگانی'),
        array('🏠', 'مجوز مشاغل خانگی', 'راهنمای دریافت مجوز برای راه‌اندازی کسب‌وکار خانگی.', 'مجوز مشاغل خانگی', 'مجوز مشاغل خانگی'),
        array('🛍️', 'پروانه کسب', 'راهنمایی مراحل دریافت پروانه کسب و مجوزهای صنفی.', 'پروانه کسب', 'پروانه کسب'),
        array('📋', 'سایر خدمات اداری', 'بررسی درخواست شما و معرفی مسیر انجام امور اداری.', 'سایر خدمات', 'مشاوره خدمات'),
    );
    ?>
    <section class="ks-services-hero">
        <div class="ks-container ks-services-hero__inner">
            <span class="ks-badge">خدمات آنلاین کارثبت</span>
            <h1><?php the_title(); ?></h1>
            <p>خدمات ثبتی و مجوزهای کسب‌وکار را با راهنمایی و پیگیری مرحله‌به‌مرحله، ساده‌تر انجام دهید.</p>
            <a class="ks-button" href="#ks-service-list">مشاهده خدمات</a>
        </div>
    </section>
    <section id="ks-service-list" class="ks-section">
        <div class="ks-container">
            <header class="ks-section-heading">
                <span class="ks-section-heading__eyebrow">شروع کسب‌وکار</span>
                <h2 class="ks-section-heading__title">چه خدمتی نیاز دارید؟</h2>
                <p class="ks-section-heading__description">خدمت موردنظر را انتخاب کنید تا درخواست خود را ثبت کنید.</p>
            </header>
            <div class="ks-grid ks-grid--3 ks-service-directory">
                <?php foreach ($services as $service) : ?>
                    <article class="ks-card ks-service-item">
                        <span class="ks-service-item__icon" aria-hidden="true"><?php echo esc_html($service[0]); ?></span>
                        <h2><?php echo esc_html($service[1]); ?></h2>
                        <p><?php echo esc_html($service[2]); ?></p>
                        <a class="ks-service-item__link" href="<?php echo esc_url(add_query_arg('service', rawurlencode($service[3]), home_url('/#contact'))); ?>">
                            ثبت درخواست <span aria-hidden="true">←</span>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <section class="ks-services-process ks-section">
        <div class="ks-container">
            <header class="ks-section-heading">
                <span class="ks-section-heading__eyebrow">مسیر انجام درخواست</span>
                <h2 class="ks-section-heading__title">در سه مرحله همراه شما هستیم</h2>
            </header>
            <div class="ks-grid ks-grid--3">
                <div class="ks-process-step"><b>۱</b><h3>ثبت درخواست</h3><p>خدمت موردنیاز و اطلاعات اولیه را ارسال کنید.</p></div>
                <div class="ks-process-step"><b>۲</b><h3>بررسی و هماهنگی</h3><p>مدارک و شرایط درخواست شما بررسی می‌شود.</p></div>
                <div class="ks-process-step"><b>۳</b><h3>پیگیری مراحل</h3><p>مراحل بعدی و وضعیت درخواست به شما اطلاع داده می‌شود.</p></div>
            </div>
        </div>
    </section>
    <section class="ks-section ks-services-cta">
        <div class="ks-container ks-services-cta__box">
            <div><h2>برای انتخاب خدمت مناسب راهنمایی می‌خواهید؟</h2><p>درخواست خود را ارسال کنید تا درباره مراحل و مدارک موردنیاز راهنمایی شوید.</p></div>
            <a class="ks-button" href="<?php echo esc_url(home_url('/#contact')); ?>">دریافت مشاوره</a>
        </div>
    </section>
<?php endif; ?>
</main>
<?php get_footer(); ?>
