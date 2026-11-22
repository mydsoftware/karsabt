<?php get_header(); ?>
<main id="main-content" class="page-content"><div class="container service-detail-wrap">
<?php while(have_posts()):the_post(); $icon=get_post_meta(get_the_ID(),'_karsabt_service_icon',true); ?>
<article <?php post_class('service-detail content-card'); ?>>
<header class="service-detail-header"><div class="service-detail-icon"><?php echo esc_html($icon ? $icon : '📄'); ?></div><div><div class="eyebrow">خدمات کارثبت</div><h1><?php the_title(); ?></h1><?php if(has_excerpt()): ?><p class="service-summary"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?></div></header>
<?php if(has_post_thumbnail()): ?><figure class="featured-image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
<div class="entry-content"><?php the_content(); ?></div>
<?php $price=absint(get_post_meta(get_the_ID(),'_karsabt_service_price',true)); $discount=absint(get_post_meta(get_the_ID(),'_karsabt_service_discount',true)); $featured=get_post_meta(get_the_ID(),'_karsabt_service_featured',true)==='1'; $popular=get_post_meta(get_the_ID(),'_karsabt_service_popular',true)==='1'; ?>
<div class="service-detail-pricing"><?php if($featured): ?><span class="service-badge featured">خدمت ویژه</span><?php endif; ?><?php if($popular): ?><span class="service-badge popular">پرفروش</span><?php endif; ?>
<div class="service-detail-price"><?php if($discount>0): ?><del><?php echo esc_html(number_format_i18n($price)); ?> تومان</del><strong><?php echo esc_html(number_format_i18n($discount)); ?> تومان</strong><?php elseif($price>0): ?><strong><?php echo esc_html(number_format_i18n($price)); ?> تومان</strong><?php else: ?><strong>قیمت: استعلام</strong><?php endif; ?></div></div>
<div class="service-order-box service-order-form"><h2>ثبت درخواست این خدمت</h2><p>اطلاعات خود را وارد کنید تا برای پیگیری با شما تماس بگیریم.</p>
<?php if(isset($_GET['order_submitted'])&&sanitize_text_field(wp_unslash($_GET['order_submitted']))==='1'): ?><div class="order-success" role="status">درخواست شما با موفقیت ثبت شد. به‌زودی با شما تماس می‌گیریم.</div><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="karsabt_order_submit"><input type="hidden" name="karsabt_service_id" value="<?php echo esc_attr(get_the_ID()); ?>"><?php wp_nonce_field('karsabt_order_submit','karsabt_order_nonce'); ?>
<div class="order-field"><label for="karsabt_customer_name">نام و نام خانوادگی</label><input id="karsabt_customer_name" name="karsabt_customer_name" type="text" required minlength="2" maxlength="120" autocomplete="name"></div>
<div class="order-field"><label for="karsabt_customer_phone">شماره تماس</label><input id="karsabt_customer_phone" name="karsabt_customer_phone" type="tel" required minlength="7" maxlength="20" autocomplete="tel" placeholder="مثلاً ۰۹۱۲۱۲۳۴۵۶۷"></div>
<div class="order-field"><label for="karsabt_customer_message">توضیحات درخواست (اختیاری)</label><textarea id="karsabt_customer_message" name="karsabt_customer_message" rows="4" maxlength="3000" placeholder="جزئیات موردنیاز خود را بنویسید"></textarea></div>
<div class="order-honeypot" aria-hidden="true"><label for="karsabt_website">این قسمت را خالی بگذارید</label><input id="karsabt_website" name="karsabt_website" type="text" tabindex="-1" autocomplete="off"></div>
<button class="btn" type="submit">ثبت درخواست</button></form></div>
</article>
<?php endwhile; ?>
</div></main>
<?php get_footer(); ?>