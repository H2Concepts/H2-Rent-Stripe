<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\Database;
use ProduktVerleih\StripeService;

function get_lowest_stripe_price_by_category($category_id) {
    return StripeService::getLowestPriceWithDurations($category_id);
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
                <?php if (!empty($price_data['amount'])): ?>
                    <div class="produkt-card-price">ab <?php echo esc_html(number_format((float) $price_data['amount'], 2, ',', '.')); ?> €</div>
                <?php else: ?>
                    <div class="produkt-card-price">Preis auf Anfrage</div>
                    <?php
                        error_log("[Frontend] Kein Preis verfügbar für Kategorie ID: {$cat->id}");
                        if (!empty($price_data['reason'])) {
                            error_log('[Frontend] Grund: ' . $price_data['reason']);
                        }
                    ?>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
