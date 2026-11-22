<?php get_header(); ?>
<main id="main-content" class="page-content"><div class="container service-detail-wrap">
<?php while(have_posts()):the_post(); $icon=get_post_meta(get_the_ID(),'_karsabt_service_icon',true); ?>
<article <?php post_class('service-detail content-card'); ?>>
<header class="service-detail-header"><div class="service-detail-icon"><?php echo esc_html($icon ? $icon : '📄'); ?></div><div><div class="eyebrow">خدمات کارثبت</div><h1><?php the_title(); ?></h1><?php if(has_excerpt()): ?><p class="service-summary"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?></div></header>
<?php if(has_post_thumbnail()): ?><figure class="featured-image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
<div class="entry-content"><?php the_content(); ?></div>
<div class="service-order-box"><h2>برای دریافت این خدمت آماده‌اید؟</h2><p>برای ثبت درخواست یا دریافت مشاوره، با کارثبت در ارتباط باشید.</p><a class="btn" href="<?php echo esc_url(home_url('/#contact')); ?>">درخواست این خدمت</a></div>
</article>
<?php endwhile; ?>
</div></main>
<?php get_footer(); ?>