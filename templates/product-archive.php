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

// Selected filters from query string
$selected_filters = [];
if (!empty($_GET['filter'])) {
    $selected_filters = array_map('intval', array_filter(explode(',', $_GET['filter'])));
}

// retrieve the requested category and sanitize the slug immediately
$category_slug = sanitize_title(get_query_var('produkt_category_slug'));
if (empty($category_slug)) {
    $category_slug = isset($_GET['kategorie']) ? sanitize_title($_GET['kategorie']) : '';
}
$filtered_product_ids = [];
$filtered_filter_ids = [];
$category = null;

if (!empty($category_slug)) {
    global $wpdb;

    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_product_categories WHERE slug = %s",
        $category_slug
    ));

    if (!empty($category)) {
        // Gefundene Kategorie → filtern inkl. Unterkategorien
        $cat_ids = array_merge([$category->id], Database::get_descendant_category_ids($category->id));
        $placeholders = implode(',', array_fill(0, count($cat_ids), '%d'));
        $filtered_product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT produkt_id FROM {$wpdb->prefix}produkt_product_to_category WHERE category_id IN ($placeholders)",
            $cat_ids
        ));
        $categories = array_filter($categories ?? [], function ($product) use ($filtered_product_ids) {
            return in_array($product->id, $filtered_product_ids);
        });
    } elseif (!empty($category_slug)) {
        // Slug war angegeben, aber ungültig
        $categories = [];
    }
}

if (!empty($selected_filters)) {
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($selected_filters), '%d'));
    $query = $wpdb->prepare(
        "SELECT category_id FROM {$wpdb->prefix}produkt_category_filters WHERE filter_id IN ($placeholders) GROUP BY category_id HAVING COUNT(DISTINCT filter_id) = %d",
        array_merge($selected_filters, [count($selected_filters)])
    );
    $filtered_filter_ids = $wpdb->get_col($query);
    $categories = array_filter($categories ?? [], function ($product) use ($filtered_filter_ids) {
        return in_array($product->id, $filtered_filter_ids);
    });
}

$content_category_id = $category->id ?? 0;
$content_blocks = Database::get_content_blocks_for_category($content_category_id);
$blocks_by_position_desktop = [];
$blocks_by_position_mobile  = [];
foreach ($content_blocks as $b) {
    $blocks_by_position_desktop[$b->position][] = $b;
    $blocks_by_position_mobile[$b->position_mobile][] = $b;
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

        return [
            'amount'   => $price_data['amount'] ?? null,
            'price_id' => $price_data['price_id'] ?? null,
        ];
    }
}

?>
<div class="produkt-shop-archive shop-overview-container produkt-container">
    <?php if ($category_slug && !$category): ?>
        <h1><?= esc_html(ucfirst($category_slug)) ?></h1>
        <p>Kategorie nicht gefunden.</p>
    <?php elseif (!empty($category_slug)): ?>
        <h1><?= esc_html(ucfirst($category_slug)) ?></h1>
    <?php else: ?>
        <h1>Shop</h1>
    <?php endif; ?>


    <?php
    global $wpdb;
    $kats_raw = $wpdb->get_results(
        "SELECT pc.id, pc.parent_id, pc.name, pc.slug, COUNT(ptc.produkt_id) AS product_count
         FROM {$wpdb->prefix}produkt_product_categories pc
         LEFT JOIN {$wpdb->prefix}produkt_product_to_category ptc ON pc.id = ptc.category_id
         GROUP BY pc.id"
    );
    $kats = Database::get_product_categories_tree();
    $cnt_map = [];
    foreach ($kats_raw as $r) { $cnt_map[$r->id] = $r->product_count; }
    foreach ($kats as $c) { $c->product_count = $cnt_map[$c->id] ?? 0; }
    $kats = array_filter($kats, function($c){ return $c->product_count > 0; });

    // Retrieve filter groups with at least one assigned product
    $filter_groups = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}produkt_filter_groups ORDER BY name");
    $filters_by_group = [];
    foreach ($filter_groups as $fg) {
        $filters = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.id, f.name, COUNT(cf.category_id) AS cnt
                 FROM {$wpdb->prefix}produkt_filters f
                 LEFT JOIN {$wpdb->prefix}produkt_category_filters cf ON cf.filter_id = f.id
                 WHERE f.group_id = %d
                 GROUP BY f.id
                 HAVING cnt > 0
                 ORDER BY f.name",
                $fg->id
            )
        );
        if (!empty($filters)) {
            $filters_by_group[$fg->id] = $filters;
        }
    }
    ?>

    <div class="shop-overview-layout">
        <aside class="shop-category-list">
            <h2>Produkte</h2>
            <ul>
                <li><a href="<?php echo esc_url(home_url('/shop/')); ?>" class="<?php echo empty($category_slug) ? 'active' : ''; ?>">Alle Kategorien</a></li>
                <?php foreach ($kats as $kat): ?>
                    <?php $cls = $kat->depth ? 'sub-category' : 'main-category'; ?>
                    <li class="<?php echo esc_attr($cls); ?>">
                        <a href="<?php echo esc_url(home_url('/shop/' . $kat->slug)); ?>" class="<?php echo ($category_slug === $kat->slug) ? 'active' : ''; ?>">
                            <?php echo esc_html($kat->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!empty($filters_by_group)): ?>
                <div class="shop-filter-checkboxes">
                    <?php foreach ($filter_groups as $fg): if (empty($filters_by_group[$fg->id])) continue; ?>
                        <strong><?php echo esc_html($fg->name); ?></strong>
                        <ul>
                            <?php foreach ($filters_by_group[$fg->id] as $f): ?>
                                <li>
                                    <label>
                                        <input type="checkbox" class="shop-filter-checkbox" value="<?php echo $f->id; ?>" <?php checked(in_array($f->id, $selected_filters)); ?>>
                                        <?php echo esc_html($f->name); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>
        <div>
            <?php if (empty($categories)): ?>
                <p>Keine Produkte in dieser Kategorie gefunden.</p>
            <?php endif; ?>

            <div class="shop-product-grid">
        <?php $produkt_index = 0; foreach (($categories ?? []) as $cat): $produkt_index++; ?>
        <?php $url = home_url('/shop/produkt/' . sanitize_title($cat->product_title)); ?>
        <?php $price_data = get_lowest_stripe_price_by_category($cat->id); ?>
        <div class="shop-product-item">
            <a href="<?php echo esc_url($url); ?>">
                <div class="shop-product-image">
                    <?php if (!empty($cat->default_image)): ?>
                        <img src="<?php echo esc_url($cat->default_image); ?>" alt="<?php echo esc_attr($cat->product_title); ?>">
                    <?php endif; ?>
                </div>
                <h3 class="shop-product-title"><?php echo esc_html($cat->product_title); ?></h3>
                <div class="shop-product-shortdesc"><?php echo esc_html($cat->short_description ?? ''); ?></div>
                <div class="shop-product-footer">
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
                        ab <?php echo esc_html(number_format((float)$price_data['amount'], 2, ',', '.')); ?>€
                    <?php else: ?>
                        Preis auf Anfrage
                    <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php
            $next_index = $produkt_index + 1;
            if (isset($blocks_by_position_desktop[$next_index])) {
                foreach ($blocks_by_position_desktop[$next_index] as $block) {
                    ?>
                    <div class="content-block desktop-only"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                        <div class="content-block-text">
                            <?php if (!empty($block->badge_text)): ?>
                                <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                            <?php endif; ?>
                            <h3><?php echo esc_html($block->title); ?></h3>
                            <div class="content-block-description">
                                <?php echo wpautop($block->content); ?>
                            </div>
                            <?php if (!empty($block->button_text) && !empty($block->button_url)): ?>
                                <a class="content-block-button" href="<?php echo esc_url($block->button_url); ?>"><?php echo esc_html($block->button_text); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="content-block-image"<?php if (!empty($block->image_url)): ?> style="background-image:url('<?php echo esc_url($block->image_url); ?>')"<?php endif; ?>>
                        </div>
                    </div>
                    <?php
                }
            }
            if (isset($blocks_by_position_mobile[$next_index])) {
                foreach ($blocks_by_position_mobile[$next_index] as $block) {
                    ?>
                    <div class="content-block mobile-only"<?php if (!empty($block->background_color)): ?> style="background-color: <?php echo esc_attr($block->background_color); ?>"<?php endif; ?>>
                        <div class="content-block-text">
                            <?php if (!empty($block->badge_text)): ?>
                                <span class="content-block-badge"><?php echo esc_html($block->badge_text); ?></span>
                            <?php endif; ?>
                            <h3><?php echo esc_html($block->title); ?></h3>
                            <div class="content-block-description">
                                <?php echo wpautop($block->content); ?>
                            </div>
                            <?php if (!empty($block->button_text) && !empty($block->button_url)): ?>
                                <a class="content-block-button" href="<?php echo esc_url($block->button_url); ?>"><?php echo esc_html($block->button_text); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="content-block-image"<?php if (!empty($block->image_url)): ?> style="background-image:url('<?php echo esc_url($block->image_url); ?>')"<?php endif; ?>>
                        </div>
                    </div>
                    <?php
                }
            }
        ?>
        <?php endforeach; ?>

        </div>
    </div>
</div> <!-- .shop-overview-layout -->

<button id="shop-filter-toggle" class="shop-filter-button" aria-label="Filter">
    <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 67.5 52.7">
      <defs>
        <style>
          .st0 { fill: #fff; }
        </style>
      </defs>
      <path class="st0" d="M64.7,40.3h-5.8c-.7-4.9-4.9-8.6-10-8.6s-9.3,3.8-10,8.6H2.7c-.8,0-1.5.7-1.5,1.5s.7,1.5,1.5,1.5h36.2c.7,4.9,4.9,8.6,10,8.6s9.3-3.8,10-8.6h5.8c.8,0,1.5-.7,1.5-1.5s-.7-1.5-1.5-1.5ZM48.9,48.9c-3.9,0-7.1-3.2-7.1-7.1s3.2-7.1,7.1-7.1,7.1,3.2,7.1,7.1-3.2,7.1-7.1,7.1Z"/>
      <path class="st0" d="M64.7,10.3H28.5c-.7-4.9-4.9-8.6-10-8.6s-9.3,3.8-10,8.6H2.7c-.8,0-1.5.7-1.5,1.5s.7,1.5,1.5,1.5h5.8c.7,4.9,4.9,8.6,10,8.6s9.3-3.8,10-8.6h36.2c.8,0,1.5-.7,1.5-1.5s-.7-1.5-1.5-1.5ZM18.5,18.9c-3.9,0-7.1-3.2-7.1-7.1s3.2-7.1,7.1-7.1,7.1,3.2,7.1,7.1-3.2,7.1-7.1,7.1Z"/>
    </svg>
</button>
<div id="shop-filter-overlay" class="shop-filter-overlay">
    <div class="shop-filter-content">
        <button id="shop-filter-close" class="shop-filter-close" aria-label="Close">&times;</button>
        <h2>Produkte</h2>
        <ul>
            <li><a href="<?php echo esc_url(home_url('/shop/')); ?>" class="<?php echo empty($category_slug) ? 'active' : ''; ?>">Alle Kategorien</a></li>
            <?php foreach ($kats as $kat): ?>
                <?php $cls = $kat->depth ? 'sub-category' : 'main-category'; ?>
                <li class="<?php echo esc_attr($cls); ?>">
                    <a href="<?php echo esc_url(home_url('/shop/' . $kat->slug)); ?>" class="<?php echo ($category_slug === $kat->slug) ? 'active' : ''; ?>">
                        <?php echo esc_html($kat->name); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (!empty($filters_by_group)): ?>
            <div class="shop-filter-checkboxes">
                <?php foreach ($filter_groups as $fg): if (empty($filters_by_group[$fg->id])) continue; ?>
                    <strong><?php echo esc_html($fg->name); ?></strong>
                    <ul>
                        <?php foreach ($filters_by_group[$fg->id] as $f): ?>
                            <li>
                                <label>
                                    <input type="checkbox" class="shop-filter-checkbox" value="<?php echo $f->id; ?>" <?php checked(in_array($f->id, $selected_filters)); ?>>
                                    <?php echo esc_html($f->name); ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div> <!-- .entry-content -->
</article></main></div> <!-- .content-area und .ast-container -->
<?php get_footer(); ?>
