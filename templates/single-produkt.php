<?php
get_header();

$cat_id = get_post_meta(get_the_ID(), 'produkt_category_id', true);
$shortcode = '';
if ($cat_id) {
    global $wpdb;
    $shortcode = $wpdb->get_var($wpdb->prepare(
        "SELECT shortcode FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
        intval($cat_id)
    ));
}
$attr = $shortcode ? ' category="' . esc_attr($shortcode) . '"' : '';
echo do_shortcode('[produkt_product' . $attr . ']');

get_footer();

