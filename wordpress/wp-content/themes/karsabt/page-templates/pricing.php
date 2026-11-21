<?php
/**
 * Template Name: صفحه تعرفه و هزینه خدمات کارثبت
 * Template Post Type: page
 */
if (!defined('ABSPATH')) { exit; }
get_header();
$services = array('ثبت شرکت','ثبت برند','کارت بازرگانی','مجوز مشاغل خانگی','پروانه کسب','سایر خدمات ثبتی و اداری');
?>
<main id="main-content" class="ks-info-page ks-pricing">
<section class="ks-info-page__hero"><div class="ks-container"><span class="ks-badge">هزینه خدمات</span><h1><?php the_title(); ?></h1><p class="ks-info-page__lead">هزینه هر درخواست ممکن است با توجه به نوع خدمت، مدارک و شرایط پرونده متفاوت باشد. برای اعلام هزینه دقیق، درخواست خود را ارسال کنید.</p></div></section>
<section class="ks-section"><div class="ks-container"><div class="ks-grid ks-grid--3 ks-pricing__list">
<?php foreach ($services as $service) : ?><article class="ks-card ks-pricing__item"><h2><?php echo esc_html($service); ?></h2><p>هزینه و شرایط اجرا پس از بررسی جزئیات درخواست اعلام می‌شود.</p><a class="ks-button" href="<?php echo esc_url(add_query_arg('service', $service, home_url('/#contact'))); ?>">استعلام هزینه</a></article><?php endforeach; ?>
</div></div></section>
<section class="ks-section ks-landing__steps"><div class="ks-container ks-info-page__body"><article class="entry-content"><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></article></div></section>
</main>
<?php get_footer(); ?>