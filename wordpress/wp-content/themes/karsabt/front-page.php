<?php get_header(); ?>
<?php if (have_posts()) : the_post(); endif; ?>
<main id="main-content" class="karsabt-homepage">
<?php if (get_post_meta(get_the_ID(), '_elementor_edit_mode', true) === 'builder') : ?>
    <div class="elementor-page-content"><?php the_content(); ?></div>
<?php else : ?>
<section class="hero"><div class="container hero-grid">
<div class="hero-art" role="img" aria-label="تصویر مفهومی خدمات آنلاین کارثبت">🏛️</div>
<div class="hero-copy"><div class="eyebrow">خدمات ثبتی و اداری، ساده‌تر از همیشه</div><h1>کارثبت</h1><h2>سامانه جامع خدمات ثبتی و اداری</h2>
<p>سریع، مطمئن، حرفه‌ای | همراه شما در مسیر قانونی کسب‌وکار</p>
<div class="check-grid"><span>ثبت شرکت و تغییرات</span><span>مجوز مشاغل خانگی</span><span>ثبت برند و علامت تجاری</span><span>کافی‌نت آنلاین</span><span>جواز کسب و پروانه صنفی</span><span>خدمات اداری و اینترنتی</span></div>
<a class="btn" href="#contact">همین حالا سفارش دهید ←</a></div></div></section>
<section class="section" id="services"><div class="container"><div class="section-head"><h2>خدمات ما</h2><p>خدمات موردنیاز کسب‌وکار خود را آنلاین ثبت و پیگیری کنید.</p></div><div class="services-grid">
<?php $services=array(array('🏢','ثبت شرکت و تغییرات','ثبت شرکت، تغییر اعضا و تغییرات قانونی'),array('🏅','ثبت برند و علامت تجاری','ثبت و پیگیری علامت تجاری'),array('🏬','جواز کسب و پروانه صنفی','راهنمایی و پیگیری مجوزهای صنفی'),array('🏠','مجوز مشاغل خانگی','دریافت مجوز فعالیت‌های خانگی'),array('🖥️','کافی‌نت آنلاین','انجام خدمات اینترنتی و سامانه‌ای'),array('📄','خدمات اداری و اینترنتی','انجام امور اداری و ثبت درخواست‌ها')); foreach($services as $s): ?>
<a class="service-card" href="#contact"><div class="service-icon"><?php echo esc_html($s[0]); ?></div><h3><?php echo esc_html($s[1]); ?></h3><p><?php echo esc_html($s[2]); ?></p></a><?php endforeach; ?>
</div></div></section>
<section class="benefits"><div class="container benefit-grid"><div class="benefit"><b>🛡️</b>امنیت اطلاعات و محرمانگی</div><div class="benefit"><b>⚡</b>پردازش سریع و بدون اتلاف وقت</div><div class="benefit"><b>🤝</b>پشتیبانی حرفه‌ای</div><div class="benefit"><b>✓</b>مشاوره پیش از سفارش</div></div></section>
<section class="section blog-section"><div class="container"><div class="section-head"><h2>آخرین مقالات و آموزش‌ها</h2><p>دانش کاربردی برای شروع و توسعه کسب‌وکار</p></div><div class="posts-grid">
<?php $q=new WP_Query(array('post_type'=>'post','posts_per_page'=>3,'post_status'=>'publish')); if($q->have_posts()):while($q->have_posts()):$q->the_post(); ?>
<article class="post-card"><a href="<?php the_permalink(); ?>"><?php if(has_post_thumbnail()){the_post_thumbnail('large');}else{?><div style="height:185px;background:#eaf3ff;display:grid;place-items:center;font-size:48px">📚</div><?php } ?></a><div class="post-content"><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(get_the_excerpt()); ?></p><a class="text-link" href="<?php the_permalink(); ?>">ادامه مطلب ←</a></div></article>
<?php endwhile;wp_reset_postdata();else: foreach(array('راهنمای ثبت شرکت','راهنمای ثبت برند','مجوزهای کسب‌وکار') as $title):?><article class="post-card"><div class="post-content"><h3><?php echo esc_html($title); ?></h3><p>مقالات آموزشی پس از انتشار نوشته‌ها در پیشخوان وردپرس نمایش داده می‌شوند.</p></div></article><?php endforeach;endif; ?>
</div></div></section>
<section class="contact-band" id="contact"><div class="container"><div class="contact-box"><div><h2>درخواست خدمات کارثبت</h2><p>فرم زیر را تکمیل کنید تا درخواست شما در سامانه ثبت و بررسی شود.</p></div></div><?php if (shortcode_exists("karsabt_order_form")) { echo do_shortcode("[karsabt_order_form]"); } else { ?><div class="form-unavailable" role="status"><strong>فرم ثبت سفارش موقتاً در دسترس نیست.</strong><p>برای فعال‌سازی ثبت سفارش آنلاین، افزونه خدمات کارثبت باید نصب و فعال باشد.</p></div><?php } ?></div></section>
<?php endif; ?>
</main><?php get_footer(); ?>
