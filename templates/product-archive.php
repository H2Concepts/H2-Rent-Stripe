<?php
/**
 * Template Name: Produkt-Archiv
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();

use ProduktVerleih\Database;
use ProduktVerleih\StripeService;

$categories = Database::get_all_categories(true);
if (!is_array($categories)) {
    $categories = [];
}

// retrieve the requested category and sanitize the slug immediately
$category_slug = sanitize_title(get_query_var('produkt_category_slug'));
$filtered_product_ids = [];
$category = null;

if (!empty($category_slug)) {
    global $wpdb;

    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_product_categories WHERE slug = %s",
        $category_slug
    ));

    if (!empty($category)) {
        // Gefundene Kategorie → filtern
        $filtered_product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT produkt_id FROM {$wpdb->prefix}produkt_product_to_category WHERE category_id = %d",
            $category->id
        ));
        $categories = array_filter($categories ?? [], function ($product) use ($filtered_product_ids) {
            return in_array($product->id, $filtered_product_ids);
        });
    } elseif (!empty($category_slug)) {
        // Slug war angegeben, aber ungültig
        $categories = [];
    }
}

if (!function_exists('get_lowest_stripe_price_by_category')) {
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

        $price_data = StripeService::get_lowest_price_with_durations($variant_ids, $duration_ids);

        // Zähle alle gültigen Preis-Kombinationen (für Anzeige von "ab")
        $price_count = 0;
        if (!empty($variant_ids) && !empty($duration_ids)) {
            $placeholders_variant  = implode(',', array_fill(0, count($variant_ids), '%d'));
            $placeholders_duration = implode(',', array_fill(0, count($duration_ids), '%d'));
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_duration_prices
                 WHERE variant_id IN ($placeholders_variant)
                   AND duration_id IN ($placeholders_duration)",
                array_merge($variant_ids, $duration_ids)
            );
            $price_count = (int) $wpdb->get_var($count_query);
        }

        return [
            'amount'     => $price_data['amount'] ?? null,
            'price_id'   => $price_data['price_id'] ?? null,
            'count'      => $price_count
        ];
    }
}

?>
<div class="produkt-shop-archive produkt-container">
    <?php if (!empty($category_slug)): ?>
      <h2>Produkte in Kategorie: <?= esc_html(ucfirst($category_slug)) ?></h2>
    <?php else: ?>
      <h2>Alle Produkte</h2>
    <?php endif; ?>
    <?php if ($category_slug && !$category): ?>
        <p>Kategorie nicht gefunden.</p>
    <?php elseif (empty($categories)): ?>
        <p>Keine Produkte in dieser Kategorie gefunden.</p>
    <?php endif; ?>
    <div class="produkt-shop-grid">
        <?php foreach (($categories ?? []) as $cat): ?>
        <?php $url = home_url('/shop/produkt/' . sanitize_title($cat->product_title)); ?>
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
                    <div class="produkt-card-price">
                        <?php if ($price_data['count'] > 1): ?>
                            ab <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€
                        <?php else: ?>
                            <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="produkt-card-price">Preis auf Anfrage</div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php get_footer(); ?>
