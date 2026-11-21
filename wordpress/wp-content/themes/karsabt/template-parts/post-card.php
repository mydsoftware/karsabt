<?php
/**
 * کارت نوشته برای فهرست مقالات صفحه اصلی.
 *
 * @package Karsabt
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<article <?php post_class('post-card'); ?>>
    <a class="post-card-image" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(sprintf('مطالعه %s', get_the_title())); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('karsabt-card', array(
                'loading' => 'lazy',
                'decoding' => 'async',
                'sizes' => '(max-width: 760px) 100vw, 33vw',
            )); ?>
        <?php else : ?>
            <div class="post-card-placeholder" aria-hidden="true">📚</div>
        <?php endif; ?>
    </a>
    <div class="post-content">
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(get_the_excerpt()); ?></p>
        <a class="text-link" href="<?php the_permalink(); ?>">ادامه مطلب ←</a>
    </div>
</article>
