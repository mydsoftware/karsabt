<?php
/** قالب نوشته تکی */
get_header();
?>
<main class="page-content"><div class="container content-layout">
  <div class="primary-content">
    <?php while (have_posts()) : the_post(); ?>
      <article <?php post_class('content-card single-post'); ?>>
        <header class="page-header">
          <div class="post-meta"><span><?php echo esc_html(get_the_date()); ?></span><?php if (has_category()) : ?><span><?php the_category('، '); ?></span><?php endif; ?></div>
          <h1><?php the_title(); ?></h1>
        </header>
        <?php if (has_post_thumbnail()) : ?><figure class="featured-image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php wp_link_pages(array('before'=>'<nav class="page-links">صفحات: ','after'=>'</nav>')); ?>
        <?php the_tags('<div class="post-tags">برچسب‌ها: ','، ','</div>'); ?>
      </article>
      <nav class="post-navigation" aria-label="پیمایش نوشته‌ها"><div><?php previous_post_link('%link','← نوشته قبلی'); ?></div><div><?php next_post_link('%link','نوشته بعدی →'); ?></div></nav>
      <?php if (comments_open() || get_comments_number()) : comments_template(); endif; ?>
    <?php endwhile; ?>
  </div>
  <aside class="content-sidebar"><div class="sidebar-card"><h2>نیاز به راهنمایی دارید؟</h2><p>برای بررسی خدمات ثبتی و اداری، درخواست خود را برای کارثبت ارسال کنید.</p><a class="btn" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت درخواست</a></div></aside>
</div></main>
<?php get_footer(); ?>
