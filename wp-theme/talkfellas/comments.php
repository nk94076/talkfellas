<?php if (post_password_required()) return; ?>
<section class="comments" id="comments">
  <?php if (have_comments()) : ?><h2><?php comments_number('No comments', '1 comment', '% comments'); ?></h2><ol class="comment-list"><?php wp_list_comments(['style' => 'ol', 'avatar_size' => 40]); ?></ol><?php the_comments_navigation(); endif; ?>
  <?php comment_form(); ?>
</section>
