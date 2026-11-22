<?php get_header(); ?>
<main id="main-content" class="page-content"><div class="container">
<header class="archive-header"><div class="eyebrow">کارثبت</div><h1>خدمات ثبتی و اداری</h1><p class="archive-description">خدمات موردنیاز خود را انتخاب کنید و برای دریافت راهنمایی یا ثبت درخواست اقدام نمایید.</p></header>
<?php if(have_posts()): ?><div class="services-grid services-archive-grid"><?php while(have_posts()):the_post();$icon=get_post_meta(get_the_ID(),'_karsabt_service_icon',true); ?>
<a class="service-card" href="<?php the_permalink(); ?>"><div class="service-icon"><?php echo esc_html($icon ? $icon : '📄'); ?></div><h2><?php the_title(); ?></h2><p><?php echo esc_html(get_the_excerpt()); ?></p><span class="text-link">جزئیات خدمت ←</span></a>
<?php endwhile; ?></div><nav class="pagination"><?php the_posts_pagination(array('prev_text'=>'قبلی','next_text'=>'بعدی')); ?></nav>
<?php else: ?><div class="content-card"><h2>خدماتی درج نشده است</h2><p>خدمات به‌زودی در این بخش نمایش داده می‌شوند.</p></div><?php endif; ?>
</div></main><?php get_footer(); ?>