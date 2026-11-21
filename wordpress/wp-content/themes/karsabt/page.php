<?php get_header(); ?>
<?php if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('single')) : ?>
<?php $karsabt_elementor_page = get_post_meta(get_queried_object_id(), '_elementor_edit_mode', true) === 'builder'; ?>
<main id="main-content" class="page-content">
<div class="<?php echo $karsabt_elementor_page ? 'elementor-page-content' : 'container'; ?>">
<?php while (have_posts()) : the_post(); ?>
<article <?php post_class(); ?>>
<?php if (!$karsabt_elementor_page) : ?><h1><?php the_title(); ?></h1><?php endif; ?>
<div class="entry-content"><?php the_content(); ?></div>
</article>
<?php endwhile; ?>
</div>
</main>
<?php endif; ?>
<?php get_footer(); ?>
