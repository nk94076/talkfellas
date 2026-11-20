<?php
/** Defaults for every editable homepage text. */
function tf_defaults() {
  return [
    'logo_tagline' => 'IDEAS • GUIDES • REVIEWS',
    'hero_eyebrow' => 'DISCOVER • LEARN • DECIDE',
    'hero_title' => 'Honest Reviews, Practical Guides &',
    'hero_highlight' => 'Real-Life Ideas',
    'hero_text' => 'In-depth articles, unbiased reviews and easy guides on tech, travel, lifestyle, wellness and more.',
    'hero_btn1' => 'Explore Articles', 'hero_btn1_url' => '/blog/',
    'hero_btn2' => 'Read Reviews', 'hero_btn2_url' => '/category/reviews/',
    'hero_trust' => 'Trusted by <b>50K+</b> readers',
    'hero_slides' => 3,
    'show_catbar' => 1, 'show_trending' => 1, 'trending_title' => 'Trending Topics:', 'trending_link' => '', 'trending_link_text' => 'View All Trends',
    'show_latest' => 1, 'latest_eyebrow' => 'THE LATEST', 'latest_title' => 'Latest Stories', 'latest_link' => '',
    'popular_title' => 'Popular This Week',
    'show_promo' => 1, 'promo_title' => 'In-Depth Reviews You Can Trust', 'promo_text' => 'Real testing, honest opinions and clear recommendations.',
    'promo_btn' => 'Read Reviews', 'promo_btn_url' => '/category/reviews/', 'promo_image' => '',
    'promo_point1' => 'Unbiased & Honest', 'promo_point2' => 'Expert Insights', 'promo_point3' => 'Real-World Testing',
    'show_cats' => 1, 'cats_title' => 'Explore by Category', 'cats_link' => '',
    'show_picks' => 1, 'picks_title' => "Editor's Picks", 'picks_text' => 'Handpicked articles, guides and recommendations from our team.', 'picks_btn' => 'Explore Picks', 'picks_btn_url' => '',
    'show_howto' => 1, 'howto_title' => 'How-To Guides', 'howto_text' => 'Step-by-step tutorials to solve everyday problems and get things done easily.', 'howto_cat' => 0,
    'show_news' => 1, 'news_title' => 'Join the Talk Fellas Community', 'news_text' => 'Get the latest articles, reviews and practical guides delivered to your inbox every week.', 'news_btn' => 'Subscribe Now', 'news_action' => '',
    'show_stats' => 1,
    'stat1_num' => '50K+', 'stat1_label' => 'Happy Readers', 'stat2_num' => '1,000+', 'stat2_label' => 'Published Articles',
    'stat3_num' => '4.8/5', 'stat3_label' => 'Reader Rating', 'stat4_num' => '25+', 'stat4_label' => 'Topic Categories',
    'footer_about' => 'A modern blog for practical guides, honest reviews and insightful articles on technology, travel, lifestyle and more.',
    'social_x' => '', 'social_facebook' => '', 'social_instagram' => '', 'social_youtube' => '', 'social_linkedin' => '',
    'footer_copy' => '© {year} Talk Fellas. All rights reserved. As an Amazon Associate we earn from qualifying purchases.',
    'brand_color' => '#f26522', 'dark_color' => '#13213c',
  ];
}

add_action('customize_register', function (WP_Customize_Manager $c) {
  $d = tf_defaults();
  $c->add_panel('tf_home', ['title' => 'TalkFellas Homepage', 'priority' => 25]);
  $add = function ($section, $key, $label, $type = 'text', $extra = []) use ($c, $d) {
    $san = ['checkbox' => fn($v) => $v ? 1 : 0, 'url' => 'esc_url_raw', 'number' => 'absint', 'textarea' => 'wp_kses_post', 'color' => 'sanitize_hex_color', 'dropdown-categories' => 'absint'][$type] ?? 'wp_kses_post';
    $c->add_setting($key, ['default' => $d[$key] ?? '', 'sanitize_callback' => $san, 'transport' => 'refresh']);
    if ($type === 'color') { $c->add_control(new WP_Customize_Color_Control($c, $key, ['label' => $label, 'section' => $section])); return; }
    if ($type === 'image') { $c->add_control(new WP_Customize_Image_Control($c, $key, ['label' => $label, 'section' => $section])); return; }
    if ($type === 'dropdown-categories') {
      $choices = [0 => '— Choose —']; foreach (get_categories(['hide_empty' => false]) as $t) $choices[$t->term_id] = $t->name;
      $c->add_control($key, ['label' => $label, 'section' => $section, 'type' => 'select', 'choices' => $choices]); return;
    }
    $c->add_control($key, array_merge(['label' => $label, 'section' => $section, 'type' => $type], $extra));
  };
  $sections = [
    'tf_brand' => ['Brand, Colours & Footer', [
      ['logo_tagline', 'Logo tagline (under site name)'], ['brand_color', 'Brand colour', 'color'], ['dark_color', 'Dark colour (banners, footer)', 'color'],
      ['footer_about', 'Footer about text', 'textarea'], ['footer_copy', 'Copyright line ({year} = current year)'],
      ['social_x', 'X / Twitter URL', 'url'], ['social_facebook', 'Facebook URL', 'url'], ['social_instagram', 'Instagram URL', 'url'], ['social_youtube', 'YouTube URL', 'url'], ['social_linkedin', 'LinkedIn URL', 'url']]],
    'tf_hero' => ['1. Hero', [
      ['hero_eyebrow', 'Small heading'], ['hero_title', 'Title'], ['hero_highlight', 'Highlighted words (orange, italic)'], ['hero_text', 'Description', 'textarea'],
      ['hero_btn1', 'Button 1 text'], ['hero_btn1_url', 'Button 1 link'], ['hero_btn2', 'Button 2 text'], ['hero_btn2_url', 'Button 2 link'],
      ['hero_trust', 'Trust line (HTML allowed)'], ['hero_slides', 'Number of slider posts (tick "Show in hero slider" on posts)', 'number']]],
    'tf_catbar' => ['2. Category bar & Trending', [
      ['show_catbar', 'Show category icon bar', 'checkbox'], ['show_trending', 'Show trending topics (Appearance → Menus → "Trending Topics bar")', 'checkbox'],
      ['trending_title', 'Trending label'], ['trending_link', 'View all link', 'url'], ['trending_link_text', 'View all text']]],
    'tf_latest' => ['3. Latest Stories & Popular', [
      ['show_latest', 'Show section', 'checkbox'], ['latest_eyebrow', 'Small heading'], ['latest_title', 'Title'], ['latest_link', '"View All" link', 'url'], ['popular_title', 'Popular box title']]],
    'tf_promo' => ['4. Reviews Banner', [
      ['show_promo', 'Show section', 'checkbox'], ['promo_title', 'Title'], ['promo_text', 'Text', 'textarea'], ['promo_btn', 'Button text'], ['promo_btn_url', 'Button link'],
      ['promo_image', 'Banner image', 'image'], ['promo_point1', 'Point 1'], ['promo_point2', 'Point 2'], ['promo_point3', 'Point 3']]],
    'tf_cats' => ['5. Explore by Category', [['show_cats', 'Show section', 'checkbox'], ['cats_title', 'Title'], ['cats_link', '"View All" link', 'url']]],
    'tf_picks' => ["6. Editor's Picks", [
      ['show_picks', 'Show section (tick "Editor\'s Pick" on posts)', 'checkbox'], ['picks_title', 'Title'], ['picks_text', 'Text', 'textarea'], ['picks_btn', 'Button text'], ['picks_btn_url', 'Button link']]],
    'tf_howto' => ['7. How-To Guides', [['show_howto', 'Show section', 'checkbox'], ['howto_title', 'Title'], ['howto_text', 'Text', 'textarea'], ['howto_cat', 'Category to show', 'dropdown-categories']]],
    'tf_news' => ['8. Newsletter', [
      ['show_news', 'Show section', 'checkbox'], ['news_title', 'Title'], ['news_text', 'Text', 'textarea'], ['news_btn', 'Button text'],
      ['news_action', 'External form URL (Mailchimp etc.). Leave blank to save emails in Tools → Subscribers', 'url']]],
    'tf_stats' => ['9. Stats', [['show_stats', 'Show section', 'checkbox'],
      ['stat1_num', 'Stat 1 number'], ['stat1_label', 'Stat 1 label'], ['stat2_num', 'Stat 2 number'], ['stat2_label', 'Stat 2 label'],
      ['stat3_num', 'Stat 3 number'], ['stat3_label', 'Stat 3 label'], ['stat4_num', 'Stat 4 number'], ['stat4_label', 'Stat 4 label']]],
  ];
  $i = 0;
  foreach ($sections as $id => [$title, $fields]) {
    $c->add_section($id, ['title' => $title, 'panel' => 'tf_home', 'priority' => ++$i]);
    foreach ($fields as $f) $add($id, $f[0], $f[1], $f[2] ?? 'text');
  }
});

// Output brand colours chosen in the Customizer.
add_action('wp_head', function () {
  $b = sanitize_hex_color(tf_mod('brand_color')) ?: '#f26522'; $k = sanitize_hex_color(tf_mod('dark_color')) ?: '#13213c';
  echo "<style>:root{--brand:$b;--navy:$k}</style>\n";
}, 20);
