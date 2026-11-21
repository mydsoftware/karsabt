<?php
/**
 * Template Name: صفحه تماس با کارثبت
 * Template Post Type: page
 */
if (!defined('ABSPATH')) { exit; }
get_header();
$phone = karsabt_contact_value('karsabt_contact_phone');
$email = karsabt_contact_value('karsabt_contact_email');
$location = karsabt_contact_value('karsabt_contact_location');
?>
<main id="main-content" class="ks-info-page ks-contact">
<section class="ks-info-page__hero"><div class="ks-container"><span class="ks-badge">ارتباط با ما</span><h1><?php the_title(); ?></h1><p class="ks-info-page__lead">برای دریافت راهنمایی یا پیگیری درخواست، از اطلاعات تماس موجود استفاده کنید.</p></div></section>
<section class="ks-section"><div class="ks-container ks-contact__grid">
<div class="ks-card"><h2>اطلاعات تماس</h2>
<?php if ($phone) : ?><p><strong>تلفن:</strong> <a href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></p><?php endif; ?>
<?php if ($email) : ?><p><strong>ایمیل:</strong> <a href="<?php echo esc_url('mailto:' . sanitize_email($email)); ?>"><?php echo esc_html($email); ?></a></p><?php endif; ?>
<?php if ($location) : ?><p><strong>محدوده فعالیت:</strong> <?php echo esc_html($location); ?></p><?php endif; ?>
<?php if (!$phone && !$email && !$location) : ?><p>اطلاعات تماس هنوز از سفارشی‌ساز وارد نشده است.</p><?php endif; ?>
</div><div class="ks-card"><h2>ارسال درخواست</h2><p>برای ارسال درخواست آنلاین، فرم سفارش را تکمیل کنید.</p><?php if (shortcode_exists('karsabt_order_form')) : ?><?php echo do_shortcode('[karsabt_order_form]'); ?><?php else : ?><p class="ks-landing__notice">فرم سفارش نیازمند فعال‌بودن افزونه Karsabt Core است.</p><?php endif; ?></div>
</div></section>
<section class="ks-section"><div class="ks-container ks-info-page__body"><article class="entry-content"><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></article></div></section>
</main>
<?php get_footer(); ?>