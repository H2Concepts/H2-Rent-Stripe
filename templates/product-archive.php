<?php
if (!defined('ABSPATH')) { exit; }
?>
<div class="produkt-shop-archive produkt-container">
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php $url = home_url('/shop/' . sanitize_title($cat->product_title)); ?>
        <?php
            $variants = $wpdb->get_results($wpdb->prepare(
                "SELECT stripe_price_id, base_price FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
                $cat->id
            ));

            $price_ids = [];
            $fallback  = null;
            foreach ($variants as $variant) {
                if (!empty($variant->stripe_price_id)) {
                    $price_ids[] = $variant->stripe_price_id;
                }
                $base = floatval($variant->base_price);
                if ($fallback === null || $base < $fallback) {
                    $fallback = $base;
                }
            }

            $price = null;
            if (!empty($price_ids)) {
                $amount = \ProduktVerleih\StripeService::get_lowest_price_cached($price_ids);
                if (!is_wp_error($amount)) {
                    $price = $amount;
                }
            }

            if ($price === null && $fallback !== null) {
                $price = $fallback;
            }
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
                <?php if ($price !== null): ?>
                    <div class="produkt-card-price"><?php echo number_format((float)$price, 2, ',', '.'); ?>€</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
