<?php
/** قالب صفحات ثابت */
get_header();
?>
<main class="page-content"><div class="container">
  <?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('content-card'); ?>>
      <header class="page-header"><h1><?php the_title(); ?></h1></header>
      <?php if (has_post_thumbnail()) : ?><figure class="featured-image"><?php the_post_thumbnail('large'); ?></figure><?php endif; ?>
      <div class="entry-content"><?php the_content(); ?></div>
      <?php wp_link_pages(array('before'=>'<nav class="page-links">صفحات: ','after'=>'</nav>')); ?>
    </article>
  <?php endwhile; ?>
</div></main>
<?php get_footer(); ?>
