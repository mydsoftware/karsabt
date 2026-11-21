<?php get_header(); ?>
<main id="main-content" class="page-content">
    <div class="container">
        <header class="section-head">
            <h1><?php the_archive_title(); ?></h1>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        </header>
        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article <?php post_class('post-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('karsabt-card'); ?></a>
                        <?php endif; ?>
                        <div class="post-content">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                            <a class="text-link" href="<?php the_permalink(); ?>">ادامه مطلب ←</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <p>محتوایی برای نمایش وجود ندارد.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
