<?php
/* ---------- Post options box: Hero slider / Editor's pick ---------- */
add_action('add_meta_boxes', function () {
  add_meta_box('tf_opts', 'TalkFellas Options', function ($post) {
    wp_nonce_field('tf_opts', 'tf_opts_n');
    foreach (['tf_hero' => 'Show in homepage hero slider', 'tf_pick' => "Editor's Pick (homepage)"] as $k => $l)
      printf('<p><label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label></p>', $k, checked(get_post_meta($post->ID, $k, true), '1', false), esc_html($l));
  }, 'post', 'side', 'high');
});
add_action('save_post_post', function ($id) {
  if (!isset($_POST['tf_opts_n']) || !wp_verify_nonce($_POST['tf_opts_n'], 'tf_opts') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $id)) return;
  foreach (['tf_hero', 'tf_pick'] as $k) empty($_POST[$k]) ? delete_post_meta($id, $k) : update_post_meta($id, $k, '1');
});

/* ---------- Category fields: icon, image, show on homepage, order ---------- */
function tf_cat_fields($term = null) {
  $id = $term ? $term->term_id : 0;
  $icon = $id ? get_term_meta($id, 'tf_icon', true) : 'grid';
  $img = $id ? (int)get_term_meta($id, 'tf_image', true) : 0;
  $show = $id ? get_term_meta($id, 'tf_show', true) : '1';
  $order = $id ? (int)get_term_meta($id, 'tf_order', true) : 0;
  $icons = ['laptop' => 'Technology', 'plane' => 'Travel', 'heart' => 'Health', 'leaf' => 'Lifestyle', 'star' => 'Reviews', 'briefcase' => 'Business', 'cap' => 'Education',
    'grid' => 'Grid', 'home' => 'Home', 'cart' => 'Shopping', 'phone' => 'Mobile', 'tv' => 'TV', 'camera' => 'Camera', 'coffee' => 'Food & Drink', 'book' => 'Books',
    'dumbbell' => 'Fitness', 'baby' => 'Baby', 'paw' => 'Pets', 'gear' => 'Tools / How-to', 'bulb' => 'Ideas', 'globe' => 'World', 'chart' => 'Finance'];
  wp_nonce_field('tf_cat', 'tf_cat_n');
  $rows = [
    'Icon' => '<select name="tf_icon">' . implode('', array_map(fn($k, $v) => '<option value="' . $k . '"' . selected($icon, $k, false) . '>' . $v . '</option>', array_keys($icons), $icons)) . '</select>',
    'Image (Explore by Category)' => '<input type="hidden" name="tf_image" id="tf_image" value="' . $img . '"><img id="tf_image_prev" src="' . esc_url($img ? wp_get_attachment_image_url($img, 'thumbnail') : '') . '" style="max-width:120px;display:' . ($img ? 'block' : 'none') . ';margin-bottom:6px"><button type="button" class="button" id="tf_image_btn">Choose image</button> <button type="button" class="button-link" id="tf_image_rm">Remove</button>',
    'Show on homepage' => '<label><input type="checkbox" name="tf_show" value="1" ' . checked($show, '1', false) . '> Category bar & Explore section</label>',
    'Order' => '<input type="number" name="tf_order" value="' . $order . '" style="width:80px">',
  ];
  foreach ($rows as $label => $field) echo $term ? "<tr class='form-field'><th>$label</th><td>$field</td></tr>" : "<div class='form-field'><label>$label</label>$field</div>";
  ?><script>jQuery(function($){var f;$('#tf_image_btn').on('click',function(e){e.preventDefault();f=f||wp.media({title:'Category image',multiple:false,library:{type:'image'}});f.off('select').on('select',function(){var a=f.state().get('selection').first().toJSON();$('#tf_image').val(a.id);$('#tf_image_prev').attr('src',(a.sizes&&a.sizes.thumbnail?a.sizes.thumbnail:a).url).show();});f.open();});$('#tf_image_rm').on('click',function(){$('#tf_image').val('');$('#tf_image_prev').hide();});});</script><?php
}
add_action('category_add_form_fields', fn() => tf_cat_fields());
add_action('category_edit_form_fields', fn($t) => tf_cat_fields($t));
add_action('admin_enqueue_scripts', function ($hook) { if (in_array($hook, ['edit-tags.php', 'term.php'], true)) wp_enqueue_media(); });
foreach (['created_category', 'edited_category'] as $h) add_action($h, function ($id) {
  if (!isset($_POST['tf_cat_n']) || !wp_verify_nonce($_POST['tf_cat_n'], 'tf_cat') || !current_user_can('manage_categories')) return;
  update_term_meta($id, 'tf_icon', sanitize_key($_POST['tf_icon'] ?? 'grid'));
  update_term_meta($id, 'tf_image', absint($_POST['tf_image'] ?? 0));
  update_term_meta($id, 'tf_show', empty($_POST['tf_show']) ? '0' : '1');
  update_term_meta($id, 'tf_order', (int)($_POST['tf_order'] ?? 0));
});
