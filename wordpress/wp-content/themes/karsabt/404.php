<?php get_header(); ?>
<main id="main-content" class="page-content">
    <div class="container" style="text-align:center;padding-top:50px;padding-bottom:50px">
        <p class="eyebrow">خطای ۴۰۴</p>
        <h1>صفحه موردنظر پیدا نشد</h1>
        <p>ممکن است نشانی تغییر کرده یا صفحه حذف شده باشد.</p>
        <p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>">بازگشت به صفحه اصلی</a></p>
        <?php get_search_form(); ?>
    </div>
</main>
<?php get_footer(); ?>
