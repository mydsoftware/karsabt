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
<div><h3>ارتباط با ما</h3><p>دزفول، خوزستان</p><p>ایمیل: info@karsabt.ir</p><p>شماره تماس: پس از تأیید مالک درج شود</p></div>
</div><div class="footer-bottom">© <?php echo esc_html(date_i18n('Y')); ?> کارثبت. تمامی حقوق محفوظ است.</div></div></footer>
<div class="mobile-cta"><a class="btn" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش آنلاین</a></div>
<?php wp_footer(); ?></body></html>
