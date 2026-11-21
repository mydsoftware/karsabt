<?php get_header(); ?>
<main id="main-content" class="page-content">
    <div class="container">
        <header class="section-head">
            <h1>نتایج جست‌وجو</h1>
            <p>عبارت جست‌وجو: <?php echo esc_html(get_search_query()); ?></p>
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
            <p>مطلبی برای عبارت جست‌وجوشده پیدا نشد. عبارت دیگری را امتحان کنید.</p>
            <?php get_search_form(); ?>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
