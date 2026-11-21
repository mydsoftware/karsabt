<?php
/**
 * Template Name: صفحه درباره و اعتمادسازی کارثبت
 * Template Post Type: page
 */
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<main id="main-content" class="ks-info-page ks-about">
<?php while (have_posts()) : the_post(); ?>
<section class="ks-info-page__hero"><div class="ks-container"><span class="ks-badge">آشنایی با کارثبت</span><h1><?php the_title(); ?></h1><?php if (has_excerpt()) : ?><p class="ks-info-page__lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?></div></section>
<section class="ks-section"><div class="ks-container ks-info-page__body"><article class="entry-content"><?php the_content(); ?></article></div></section>
<section class="ks-section ks-info-page__trust"><div class="ks-container"><h2>شفافیت در مسیر انجام خدمات</h2><div class="ks-grid ks-grid--3"><div class="ks-card"><h3>اطلاع‌رسانی مرحله‌ای</h3><p>شرایط و مدارک موردنیاز، پیش از ادامه مسیر با شما هماهنگ می‌شود.</p></div><div class="ks-card"><h3>پاسخ‌گویی</h3><p>از راه‌های ارتباطی درج‌شده در سایت برای پیگیری درخواست استفاده کنید.</p></div><div class="ks-card"><h3>اطلاعات روشن</h3><p>جزئیات خدمات و موارد وابسته به شرایط پرونده را در محتوا بررسی کنید.</p></div></div></div></section>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>