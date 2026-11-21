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
<div class="topbar"><div class="container topbar-inner"><span>📍 دزفول، خوزستان</span><span>✉ info@karsabt.ir</span><span>خدمات ثبتی و اداری آنلاین</span></div></div>
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
        <a class="btn header-cta" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش آنلاین ←</a>
    </div>
</header>
