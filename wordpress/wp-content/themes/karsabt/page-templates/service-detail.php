<?php
/**
 * Template Name: جزئیات خدمت کارثبت
 * Template Post Type: page
 *
 * الگوی عمومی صفحات جزئیات خدمات؛ محتوای برگه در Elementor قابل طراحی است.
 *
 * @package Karsabt
 */
if (!defined('ABSPATH')) { exit; }
get_header();
$is_elementor = get_post_meta(get_queried_object_id(), '_elementor_edit_mode', true) === 'builder';
?>
<main id="main-content" class="ks-service-detail">
<?php while (have_posts()) : the_post(); ?>
    <section class="ks-service-detail__hero">
        <div class="ks-container">
            <span class="ks-badge">خدمات آنلاین کارثبت</span>
            <h1><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
                <p class="ks-service-detail__intro"><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php else : ?>
                <p class="ks-service-detail__intro">برای آشنایی با مراحل، شرایط و مدارک موردنیاز این خدمت، توضیحات را مطالعه کنید.</p>
            <?php endif; ?>
            <a class="ks-button" href="<?php echo esc_url(add_query_arg('service', get_the_title(), home_url('/#contact'))); ?>">ثبت درخواست این خدمت</a>
        </div>
    </section>

    <?php if ($is_elementor) : ?>
        <div class="elementor-page-content"><?php the_content(); ?></div>
    <?php else : ?>
        <section class="ks-section">
            <div class="ks-container ks-service-detail__content">
                <article <?php post_class('ks-service-detail__article'); ?>>
                    <?php if (has_post_thumbnail()) : ?>
                        <figure class="ks-service-detail__image"><?php the_post_thumbnail('large'); ?></figure>
                    <?php endif; ?>
                    <div class="entry-content"><?php the_content(); ?></div>
                </article>
                <aside class="ks-service-detail__aside">
                    <h2>برای شروع چه کار کنم؟</h2>
                    <p>درخواست خود را ثبت کنید تا شرایط و مدارک موردنیاز بررسی و مراحل بعدی به شما اعلام شود.</p>
                    <a class="ks-button" href="<?php echo esc_url(add_query_arg('service', get_the_title(), home_url('/#contact'))); ?>">شروع ثبت درخواست</a>
                </aside>
            </div>
        </section>
    <?php endif; ?>
    <section class="ks-section ks-services-cta">
        <div class="ks-container ks-services-cta__box">
            <div><h2>برای این خدمت به راهنمایی نیاز دارید؟</h2><p>درخواست خود را ثبت کنید تا برای مراحل بعدی راهنمایی شوید.</p></div>
            <a class="ks-button ks-button--secondary" href="<?php echo esc_url(home_url('/#contact')); ?>">ارتباط با کارثبت</a>
        </div>
    </section>
<?php endwhile; ?>
</main>
<?php get_footer(); ?>
