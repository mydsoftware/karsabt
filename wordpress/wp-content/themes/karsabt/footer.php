<?php
$karsabt_phone = get_theme_mod('karsabt_phone', '');
$karsabt_email = get_theme_mod('karsabt_email', 'info@karsabt.ir');
$karsabt_address = get_theme_mod('karsabt_address', 'دزفول، خوزستان');
?>
<footer class="site-footer"><div class="container"><div class="footer-grid">
  <div><h3>کارثبت | Karsabt.ir</h3><p>سامانه خدمات ثبتی و اداری؛ همراه شما برای انجام امور کسب‌وکار.</p></div>
  <div><h3>دسترسی سریع</h3><p><a href="<?php echo esc_url(home_url('/')); ?>">صفحه اصلی</a></p><p><a href="<?php echo esc_url(home_url('/#services')); ?>">خدمات ما</a></p><p><a href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش</a></p></div>
  <div><h3>ارتباط با ما</h3>
    <?php if ($karsabt_address) : ?><p><?php echo esc_html($karsabt_address); ?></p><?php endif; ?>
    <?php if ($karsabt_email) : ?><p>ایمیل: <a href="<?php echo esc_attr('mailto:' . antispambot($karsabt_email)); ?>"><?php echo esc_html(antispambot($karsabt_email)); ?></a></p><?php endif; ?>
    <?php if ($karsabt_phone) : ?><p>تلفن: <a href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $karsabt_phone)); ?>"><?php echo esc_html($karsabt_phone); ?></a></p><?php endif; ?>
  </div>
</div><div class="footer-bottom">© <?php echo esc_html(date_i18n('Y')); ?> کارثبت. تمامی حقوق محفوظ است.</div></div></footer>
<div class="mobile-cta"><a class="btn" href="<?php echo esc_url(home_url('/#contact')); ?>">ثبت سفارش آنلاین</a></div>
<?php wp_footer(); ?></body></html>
