<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?><a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
<?php
$karsabt_phone = get_theme_mod('karsabt_phone', '');
$karsabt_email = get_theme_mod('karsabt_email', 'info@karsabt.ir');
$karsabt_address = get_theme_mod('karsabt_address', 'دزفول، خوزستان');
?>
<div class="topbar"><div class="container topbar-inner">
  <?php if ($karsabt_address) : ?><span>📍 <?php echo esc_html($karsabt_address); ?></span><?php endif; ?>
  <?php if ($karsabt_email) : ?><span>✉ <?php echo esc_html($karsabt_email); ?></span><?php endif; ?>
  <?php if ($karsabt_phone) : ?><span>☎ <a href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $karsabt_phone)); ?>"><?php echo esc_html($karsabt_phone); ?></a></span><?php endif; ?>
  <span>خدمات ثبتی و اداری آنلاین</span>
</div></div>
<header class="site-header"><div class="container nav-wrap">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="کارثبت">
    <?php if (has_custom_logo()) { the_custom_logo(); } else { ?>
      <span class="brand-mark">✓</span><span>کارثبت<small>Karsabt.ir</small></span>
    <?php } ?>
  </a>
  <button class="menu-toggle" aria-label="باز کردن منو" aria-expanded="false" aria-controls="primary-navigation">☰</button>
  <nav id="primary-navigation" class="main-nav" aria-label="منوی اصلی">
    <?php wp_nav_menu(array('theme_location'=>'primary','container'=>false,'fallback_cb'=>'wp_page_menu','menu_class'=>'menu')); ?>
  </nav>
  <a class="btn header-cta" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش آنلاین ←</a>
</div></header>
