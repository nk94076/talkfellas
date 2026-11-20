<?php $p = $args['post'] ?? get_post(); ?>
<article class="card">
  <div class="card-img"><a href="<?php echo esc_url(get_permalink($p)); ?>" tabindex="-1" aria-hidden="true"><?php echo tf_thumb($p); ?></a><?php echo tf_cat_label($p, 'pill on-img'); ?></div>
  <div class="card-body">
    <h3><a href="<?php echo esc_url(get_permalink($p)); ?>"><?php echo esc_html(get_the_title($p)); ?></a></h3>
    <p><?php echo esc_html(get_the_excerpt($p)); ?></p>
    <?php echo tf_meta($p); ?>
  </div>
</article>
