<?php
/**
 * Template Name: صفحه فرود تبلیغاتی کارثبت
 * Template Post Type: page
 * Description: صفحه فرود مناسب کمپین‌های تبلیغاتی و معرفی یک خدمت.
 * @package Karsabt
 */
if (!defined('ABSPATH')) { exit; }
get_header();
$is_elementor = get_post_meta(get_queried_object_id(), '_elementor_edit_mode', true) === 'builder';
?>
<main id="main-content" class="ks-landing">
<?php while (have_posts()) : the_post(); ?>
<section class="ks-landing__hero">
 <div class="ks-container ks-landing__hero-inner">
  <span class="ks-badge">کارثبت | خدمات ثبتی و کسب‌وکار</span>
  <h1><?php the_title(); ?></h1>
  <?php if (has_excerpt()) : ?><p class="ks-landing__lead"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
  <a class="ks-button" href="#ks-landing-request">ثبت درخواست و دریافت راهنمایی</a>
 </div>
</section>
<?php if ($is_elementor) : ?>
 <section class="ks-landing__builder-content"><?php the_content(); ?></section>
<?php else : ?>
 <section class="ks-section">
  <div class="ks-container ks-landing__content">
   <article class="ks-landing__article">
    <?php if (has_post_thumbnail()) : ?><figure class="ks-landing__image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
    <div class="entry-content"><?php the_content(); ?></div>
   </article>
   <aside class="ks-landing__side">
    <h2>شروع درخواست</h2>
    <p>اطلاعات اولیه را ارسال کنید تا درباره شرایط و مراحل این خدمت راهنمایی شوید.</p>
    <a class="ks-button" href="#ks-landing-request">ثبت درخواست</a>
   </aside>
  </div>
 </section>
<?php endif; ?>
<section class="ks-landing__steps ks-section">
 <div class="ks-container">
  <header class="ks-section-heading"><span class="ks-section-heading__eyebrow">فرآیند ساده</span><h2 class="ks-section-heading__title">از درخواست تا پیگیری</h2></header>
  <div class="ks-grid ks-grid--3">
   <div class="ks-process-step"><b>۱</b><h3>ثبت درخواست</h3><p>خدمت موردنیاز و اطلاعات اولیه را ارسال کنید.</p></div>
   <div class="ks-process-step"><b>۲</b><h3>بررسی شرایط</h3><p>شرایط و مدارک موردنیاز با شما هماهنگ می‌شود.</p></div>
   <div class="ks-process-step"><b>۳</b><h3>پیگیری</h3><p>برای ادامه مسیر و مراحل بعدی راهنمایی دریافت کنید.</p></div>
  </div>
 </div>
</section>
<section id="ks-landing-request" class="ks-section ks-landing__request">
 <div class="ks-container">
  <div class="ks-landing__request-box">
   <h2>درخواست مشاوره یا ثبت سفارش</h2>
   <p>فرم زیر را تکمیل کنید؛ درخواست شما از طریق فرم سفارش سایت ثبت می‌شود.</p>
   <?php if (shortcode_exists('karsabt_order_form')) : ?>
    <?php echo do_shortcode('[karsabt_order_form]'); ?>
   <?php else : ?>
    <p class="ks-landing__notice">فرم سفارش در دسترس نیست. افزونه Karsabt Core باید نصب و فعال باشد.</p>
   <?php endif; ?>
  </div>
 </div>
</section>
<?php
$faq_items = get_post_meta(get_the_ID(), '_karsabt_service_faq', true);
if (is_array($faq_items) && $faq_items) :
?>
<section class="ks-section ks-service-faq">
 <div class="ks-container">
  <header class="ks-section-heading"><span class="ks-section-heading__eyebrow">پاسخ پرسش‌های شما</span><h2 class="ks-section-heading__title">سؤالات متداول</h2></header>
  <div class="ks-service-faq__list">
  <?php foreach ($faq_items as $faq) : if (empty($faq['question']) || empty($faq['answer'])) { continue; } ?>
   <details class="ks-service-faq__item"><summary><?php echo esc_html($faq['question']); ?></summary><div class="ks-service-faq__answer"><?php echo wpautop(esc_html($faq['answer'])); ?></div></details>
  <?php endforeach; ?>
  </div>
 </div>
</section>
<?php endif; ?>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
