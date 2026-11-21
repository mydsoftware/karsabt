<?php
/**
 * قالب نتایج جست‌وجو
 */
get_header();
?>
<main class="page-content">
  <div class="container">
    <header class="archive-header">
      <div class="eyebrow">جست‌وجو در کارثبت</div>
      <h1>نتایج جست‌وجو برای: <?php echo esc_html(get_search_query()); ?></h1>
      <?php get_search_form(); ?>
    </header>
    <?php if (have_posts()) : ?>
      <div class="posts-grid archive-grid">
        <?php while (have_posts()) : the_post(); ?>
          <article <?php post_class('post-card'); ?>>
            <div class="post-content">
              <div class="post-meta"><?php echo esc_html(get_the_date()); ?></div>
              <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <p><?php echo esc_html(get_the_excerpt()); ?></p>
              <a class="text-link" href="<?php the_permalink(); ?>">مشاهده مطلب ←</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <nav class="pagination"><?php the_posts_pagination(array('prev_text' => 'قبلی', 'next_text' => 'بعدی')); ?></nav>
    <?php else : ?>
      <div class="content-card"><h2>نتیجه‌ای پیدا نشد.</h2><p>عبارت دیگری جست‌وجو کنید یا از صفحه خدمات دیدن کنید.</p><?php get_search_form(); ?></div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
