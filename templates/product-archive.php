<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\Database;
use ProduktVerleih\StripeService;

function get_lowest_stripe_price_by_category($category_id) {
    global $wpdb;

    $variant_ids  = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
        $category_id
    ));
    $duration_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d",
        $category_id
    ));

    return StripeService::get_lowest_price_with_durations($variant_ids, $duration_ids);
}
?>
<div class="produkt-shop-archive produkt-container">
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php $url = home_url('/shop/' . sanitize_title($cat->product_title)); ?>
        <?php
            $price_data = get_lowest_stripe_price_by_category($cat->id);
        ?>
        <a class="produkt-shop-card-link" href="<?php echo esc_url($url); ?>">
            <div class="produkt-shop-card">
                <?php if (!empty($cat->default_image)): ?>
                    <img class="produkt-shop-image" src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
                <?php endif; ?>
                <h2><?php echo esc_html($cat->product_title); ?></h2>
                <?php if ($cat->show_rating && floatval($cat->rating_value) > 0): ?>
                    <?php $display = number_format(floatval($cat->rating_value), 1, ',', ''); ?>
                    <div class="produkt-rating">
                        <span class="produkt-rating-number"><?php echo esc_html($display); ?></span>
                        <span class="produkt-star-rating produkt-star-black" style="--rating: <?php echo esc_attr($cat->rating_value); ?>;"></span>
                    </div>
                <?php endif; ?>
                <?php if ($price_data && isset($price_data['amount'])): ?>
                    <div class="produkt-card-price">ab <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€</div>
                <?php else: ?>
                    <div class="produkt-card-price">Preis auf Anfrage</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
