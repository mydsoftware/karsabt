<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
<?php if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('header')) : ?>
<?php
$contact_location = karsabt_contact_value('karsabt_contact_location');
$contact_email = karsabt_contact_value('karsabt_contact_email');
$contact_phone = karsabt_contact_value('karsabt_contact_phone');
$cta_label = get_theme_mod('karsabt_cta_label', 'ثبت سفارش آنلاین');
$cta_url = get_theme_mod('karsabt_cta_url', home_url('/#contact'));
if (get_theme_mod('karsabt_show_topbar', true) && ($contact_location || $contact_email || $contact_phone)) :
?>
<div class="topbar"><div class="container topbar-inner">
<?php if ($contact_location) : ?><span>📍 <?php echo esc_html($contact_location); ?></span><?php endif; ?>
<?php if ($contact_email) : ?><span>✉ <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></span><?php endif; ?>
<?php if ($contact_phone) : ?><span>☎ <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></span><?php endif; ?>
</div></div>
<?php endif; ?>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="صفحه اصلی کارثبت">
            <?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
                <span class="brand-mark" aria-hidden="true">✓</span><span>کارثبت<small>Karsabt.ir</small></span>
            <?php } ?>
        </a>
        <button class="menu-toggle" type="button" aria-label="باز کردن منوی اصلی" aria-controls="primary-navigation" aria-expanded="false">☰</button>
        <nav id="primary-navigation" class="main-nav" aria-label="منوی اصلی">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container' => false,
                'fallback_cb' => 'wp_page_menu',
                'menu_class' => 'menu',
                'depth' => 2,
            ));
            ?>
        </nav>
        <?php if (get_theme_mod('karsabt_show_header_cta', true)) : ?>
        <a class="btn header-cta" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?> ←</a>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
