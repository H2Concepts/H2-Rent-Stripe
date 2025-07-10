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
if (empty($category_slug)) {
    $category_slug = isset($_GET['kategorie']) ? sanitize_title($_GET['kategorie']) : '';
}
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
<div class="produkt-shop-archive shop-overview-container produkt-container">
    <?php if ($category_slug && !$category): ?>
        <h1>Kategorie: <?= esc_html(ucfirst($category_slug)) ?></h1>
        <p>Kategorie nicht gefunden.</p>
    <?php elseif (!empty($category_slug)): ?>
        <h1>Kategorie: <?= esc_html(ucfirst($category_slug)) ?></h1>
    <?php else: ?>
        <h1>Shop</h1>
    <?php endif; ?>

    <p class="shop-intro">
        Entdecken Sie unsere hochwertigen Produkte für Ihren Bedarf – Qualität, geprüft und sofort verfügbar.
    </p>

    <form method="get" class="produkt-filter-form">
        <select name="kategorie" onchange="this.form.submit()">
            <option value="">Alle Kategorien</option>
            <?php
            global $wpdb;
            $kats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_product_categories ORDER BY name ASC");
            foreach ($kats as $kat):
                $selected = ($category_slug === $kat->slug) ? 'selected' : '';
            ?>
                <option value="<?= esc_attr($kat->slug) ?>" <?= $selected ?>>
                    <?= esc_html($kat->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($categories)): ?>
        <p>Keine Produkte in dieser Kategorie gefunden.</p>
    <?php endif; ?>

    <div class="shop-product-grid">
        <?php foreach (($categories ?? []) as $cat): ?>
        <?php $url = home_url('/shop/produkt/' . sanitize_title($cat->product_title)); ?>
        <?php $price_data = get_lowest_stripe_price_by_category($cat->id); ?>
        <div class="shop-product-item">
            <a href="<?php echo esc_url($url); ?>">
                <div class="shop-product-image">
                    <?php if (!empty($cat->default_image)): ?>
                        <img src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
                    <?php endif; ?>
                </div>
                <div class="shop-product-title"><?php echo esc_html($cat->product_title); ?></div>
                <div class="shop-product-shortdesc"><?php echo esc_html(wp_trim_words($cat->product_description, 12)); ?></div>
                <div class="shop-product-price">
                    <?php if ($price_data && isset($price_data['amount'])): ?>
                        <?php if ($price_data['count'] > 1): ?>
                            ab <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€
                        <?php else: ?>
                            <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€
                        <?php endif; ?>
                    <?php else: ?>
                        Preis auf Anfrage
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div><!-- .ast-container -->
</div><!-- #content -->

<?php get_footer(); ?>
