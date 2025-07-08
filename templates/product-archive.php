<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\Database;
use Stripe\Stripe;
use Stripe\Price;

function get_lowest_stripe_price_by_category($category_id) {
    $price_ids = Database::getAllStripePriceIdsByCategory($category_id);
    if (empty($price_ids)) return null;

    $cache_key = 'lowest_price_category_' . md5($category_id . '_' . implode('_', $price_ids));
    $cached = get_transient($cache_key);
    if ($cached !== false) return $cached;

    Stripe::setApiKey(get_option('produkt_stripe_secret_key', ''));

    $lowest = null;
    foreach ($price_ids as $price_id) {
        try {
            $price = Price::retrieve($price_id);
            if (!isset($price->unit_amount)) continue;
            $amount = $price->unit_amount / 100;
            if ($lowest === null || $amount < $lowest) {
                $lowest = $amount;
            }
        } catch (\Exception $e) {
            continue;
        }
    }

    if ($lowest !== null) {
        $formatted = number_format($lowest, 2, ',', '.');
        set_transient($cache_key, $formatted, 12 * HOUR_IN_SECONDS);
        return $formatted;
    }

    return null;
}
?>
<div class="produkt-shop-archive produkt-container">
    <div class="produkt-shop-grid">
        <?php foreach ($categories as $cat): ?>
        <?php $url = home_url('/shop/' . sanitize_title($cat->product_title)); ?>
        <?php
            $price = get_lowest_stripe_price_by_category($cat->id);
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
                <?php if ($price): ?>
                    <div class="produkt-card-price">ab <?php echo esc_html($price); ?>€</div>
                <?php else: ?>
                    <div class="produkt-card-price">Preis auf Anfrage</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
