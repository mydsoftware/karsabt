<?php
/**
 * قالب بایگانی نوشته‌ها و دسته‌بندی‌ها
 */
get_header();
?>
<main id="main-content" class="page-content">
  <div class="container">
    <header class="archive-header">
      <div class="eyebrow">مجله کارثبت</div>
      <?php the_archive_title('<h1>', '</h1>'); ?>
      <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
    </header>
    <?php if (have_posts()) : ?>
      <div class="posts-grid archive-grid">
        <?php while (have_posts()) : the_post(); ?>
          <article <?php post_class('post-card'); ?>>
            <a class="post-thumb" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
              <?php if (has_post_thumbnail()) : the_post_thumbnail('large'); ?>
              <?php else : ?><span class="post-placeholder" aria-hidden="true">📚</span><?php endif; ?>
            </a>
            <div class="post-content">
              <div class="post-meta"><?php echo esc_html(get_the_date()); ?></div>
              <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
              <p><?php echo esc_html(get_the_excerpt()); ?></p>
              <a class="text-link" href="<?php the_permalink(); ?>">ادامه مطلب ←</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <nav class="pagination"><?php the_posts_pagination(array('mid_size' => 1, 'prev_text' => 'قبلی', 'next_text' => 'بعدی')); ?></nav>
    <?php else : ?>
      <div class="content-card"><h2>هنوز محتوایی منتشر نشده است.</h2><p>به‌زودی مطالب آموزشی کارثبت در این بخش قرار می‌گیرند.</p></div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
