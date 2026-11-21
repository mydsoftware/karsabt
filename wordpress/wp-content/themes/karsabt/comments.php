<?php
if (!defined('ABSPATH')) { exit; }
if (post_password_required()) { return; }
?>
<section id="comments" class="comments-area">
  <?php if (have_comments()) : ?>
    <h2 class="comments-title"><?php echo esc_html(number_format_i18n(get_comments_number())); ?> دیدگاه</h2>
    <ol class="comment-list">
      <?php wp_list_comments(array('style'=>'ol','short_ping'=>true,'avatar_size'=>44)); ?>
    </ol>
    <?php the_comments_pagination(array('prev_text'=>'دیدگاه‌های قدیمی‌تر','next_text'=>'دیدگاه‌های جدیدتر')); ?>
  <?php endif; ?>
  <?php if (!comments_open() && get_comments_number()) : ?>
    <p class="no-comments">ثبت دیدگاه برای این نوشته بسته شده است.</p>
  <?php endif; ?>
  <?php comment_form(array(
    'title_reply' => 'دیدگاه خود را بنویسید',
    'label_submit' => 'ارسال دیدگاه',
    'comment_notes_before' => '<p class="comment-notes">نشانی ایمیل شما منتشر نخواهد شد.</p>',
    'comment_field' => '<p class="comment-form-comment"><label for="comment">دیدگاه شما</label><textarea id="comment" name="comment" rows="6" required></textarea></p>',
  )); ?>
</section>
