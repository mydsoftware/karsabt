<?php if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('footer')) : ?>
<footer class="site-footer"><div class="container"><div class="footer-grid">
<div><h3>کارثبت | Karsabt.ir</h3><p>سامانه خدمات ثبتی و اداری؛ همراه شما برای انجام امور کسب‌وکار.</p></div>
<div><h3>دسترسی سریع</h3>
<?php
wp_nav_menu(array(
    'theme_location' => 'footer',
    'container' => 'nav',
    'container_aria_label' => 'منوی پایین صفحه',
    'fallback_cb' => false,
    'depth' => 1,
));
?>
<?php if (!has_nav_menu('footer')) : ?>
<p><a href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی</a></p>
<p><a href="<?php echo esc_url(home_url('/#services')); ?>">خدمات ما</a></p>
<p><a href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش</a></p>
<?php endif; ?>
</div>
<?php
$contact_location = karsabt_contact_value('karsabt_contact_location');
$contact_email = karsabt_contact_value('karsabt_contact_email');
$contact_phone = karsabt_contact_value('karsabt_contact_phone');
if ($contact_location || $contact_email || $contact_phone) :
?>
<div><h3>ارتباط با ما</h3>
<?php if ($contact_location) : ?><p><?php echo esc_html($contact_location); ?></p><?php endif; ?>
<?php if ($contact_email) : ?><p>ایمیل: <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a></p><?php endif; ?>
<?php if ($contact_phone) : ?><p>تلفن: <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>"><?php echo esc_html($contact_phone); ?></a></p><?php endif; ?>
</div>
<?php endif; ?>
</div><div class="footer-bottom">© <?php echo esc_html(date_i18n('Y')); ?> کارثبت. تمامی حقوق محفوظ است.</div></div></footer>
<?php endif; ?>
<div class="mobile-cta"><a class="btn" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش آنلاین</a></div>
<?php wp_footer(); ?></body></html>
