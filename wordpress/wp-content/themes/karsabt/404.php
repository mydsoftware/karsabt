<?php
/**
 * صفحه خطای ۴۰۴
 */
get_header();
?>
<main id="main-content" class="page-content">
  <div class="container">
    <section class="error-page content-card">
      <div class="error-code">۴۰۴</div>
      <h1>صفحه موردنظر پیدا نشد</h1>
      <p>ممکن است نشانی صفحه تغییر کرده باشد یا صفحه دیگر در دسترس نباشد.</p>
      <div class="error-actions">
        <a class="btn" href="<?php echo esc_url(home_url('/')); ?>">بازگشت به صفحه اصلی</a>
        <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/#services')); ?>">مشاهده خدمات</a>
      </div>
      <?php get_search_form(); ?>
    </section>
  </div>
</main>
<?php get_footer(); ?>
