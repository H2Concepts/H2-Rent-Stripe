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


    <?php
    global $wpdb;
    $kats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_product_categories ORDER BY name ASC");
    ?>

    <div class="shop-overview-layout">
        <aside class="shop-category-list">
            <h2>Produkte</h2>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/shop/')); ?>" class="<?php echo empty($category_slug) ? 'active' : ''; ?>">Alle Kategorien</a></li>
                <?php foreach ($kats as $kat): ?>
                    <li>
                        <a href="<?php echo esc_url(home_url('/shop/' . $kat->slug)); ?>" class="<?php echo ($category_slug === $kat->slug) ? 'active' : ''; ?>">
                            <?php echo esc_html($kat->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <div>
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
                <div class="shop-product-shortdesc"><?php echo esc_html($cat->short_description ?? ''); ?></div>
                <?php
                    $rating_value = floatval(str_replace(',', '.', $cat->rating_value));
                    $rating_display = number_format($rating_value, 1, ',', '');
                ?>
                <?php if ($cat->show_rating && $rating_value > 0): ?>
                    <div class="produkt-rating">
                        <span class="produkt-rating-number"><?php echo esc_html($rating_display); ?></span>
                        <span class="produkt-star-rating" style="--rating: <?php echo esc_attr($rating_value); ?>;"></span>
                    </div>
                <?php endif; ?>
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

</div>

</div><!-- .ast-container -->
</div><!-- #content -->

<?php get_footer(); ?>
