<?php
/* Template Name: Produkt-Seite */
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/shop-helpers.php';

get_header();

$slug = sanitize_title(get_query_var('produkt_slug'));

$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
$category = null;
foreach ($categories as $cat) {
    if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
        $category = $cat;
        break;
    }
}

if (!$category) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    include get_query_template('404');
    get_footer();
    return;
}

add_filter('pre_get_document_title', function () use ($category) {
    return $category->page_title ?: $category->product_title;
});

// Get category data
$category_id = isset($category) ? $category->id : 1;

// Get all data for this category
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$extras = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_extras WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

// Filter extras based on Betriebsmodus
$extras = array_values(array_filter($extras, function ($e) {
    $modus = get_option('produkt_betriebsmodus', 'miete');
    $pid = $modus === 'kauf'
        ? ($e->stripe_price_id_sale ?: ($e->stripe_price_id ?? ''))
        : ($e->stripe_price_id_rent ?: ($e->stripe_price_id ?? ''));
    return !empty($pid);
}));

$durations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d ORDER BY sort_order",
    $category_id
));
$duration_count = count($durations);

// Get blocked days for booking calendar
$blocked_days = $wpdb->get_col("SELECT day FROM {$wpdb->prefix}produkt_blocked_days");

// Determine lowest price across all variants and durations
$variant_ids  = array_map(fn($v) => (int) $v->id, $variants);
$duration_ids = array_map(fn($d) => (int) $d->id, $durations);
$price_data   = \ProduktVerleih\StripeService::get_lowest_price_with_durations($variant_ids, $duration_ids);
$price_count  = 0;
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

// Preise für Rabatt-Badges ermitteln
$badge_base_price = 0.0;
$badge_prices = [];
$variant_min_prices = [];
$badge_base_duration_id = null;
$badge_base_duration_months = PHP_INT_MAX;
$badge_base_duration_sort = PHP_INT_MAX;

if (!empty($durations)) {
    foreach ($durations as $duration) {
        $current_months = isset($duration->months_minimum) ? (int) $duration->months_minimum : PHP_INT_MAX;
        $current_sort = isset($duration->sort_order) ? (int) $duration->sort_order : PHP_INT_MAX;

        if ($badge_base_duration_id === null) {
            $badge_base_duration_id = (int) $duration->id;
            $badge_base_duration_months = $current_months;
            $badge_base_duration_sort = $current_sort;
        } else {
            if (
                $current_months < $badge_base_duration_months ||
                (
                    $current_months === $badge_base_duration_months &&
                    ($current_sort < $badge_base_duration_sort ||
                        ($current_sort === $badge_base_duration_sort && (int) $duration->id < $badge_base_duration_id))
                )
            ) {
                $badge_base_duration_id = (int) $duration->id;
                $badge_base_duration_months = $current_months;
                $badge_base_duration_sort = $current_sort;
            }
        }
    }
}

if (!empty($variant_ids) && !empty($duration_ids)) {
    $variant_placeholders = implode(',', array_fill(0, count($variant_ids), '%d'));
    $duration_placeholders = implode(',', array_fill(0, count($duration_ids), '%d'));
    $query_args = array_merge($variant_ids, $duration_ids);
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT variant_id, duration_id, custom_price FROM {$wpdb->prefix}produkt_duration_prices WHERE variant_id IN ($variant_placeholders) AND duration_id IN ($duration_placeholders)",
        $query_args
    ));
    foreach ($rows as $row) {
        $duration_id = (int) $row->duration_id;
        $variant_id = (int) $row->variant_id;
        $price = ($row->custom_price !== null) ? floatval($row->custom_price) : 0.0;
        if ($price > 0) {
            if (!isset($badge_prices[$duration_id]) || $price < $badge_prices[$duration_id]) {
                $badge_prices[$duration_id] = $price;
            }
            if (!isset($variant_min_prices[$variant_id]) || $price < $variant_min_prices[$variant_id]) {
                $variant_min_prices[$variant_id] = $price;
            }
        }
    }
}

if (!empty($variants)) {
    foreach ($variants as $variant) {
        $variant_id = (int) $variant->id;
        $base_price = 0.0;

        if (isset($variant_min_prices[$variant_id])) {
            continue;
        }

        if (!empty($variant->base_price)) {
            $base_price = floatval($variant->base_price);
        }

        if ($base_price <= 0 && !empty($variant->mietpreis_monatlich)) {
            $base_price = floatval($variant->mietpreis_monatlich);
        }

        if ($base_price <= 0 && !empty($variant->stripe_price_id)) {
            $stripe_price = \ProduktVerleih\StripeService::get_price_amount($variant->stripe_price_id);
            if (!is_wp_error($stripe_price)) {
                $base_price = floatval($stripe_price);
            }
        }

        if ($base_price > 0) {
            $variant_min_prices[$variant_id] = $base_price;
        }
    }
}

if ($badge_base_duration_id !== null && isset($badge_prices[$badge_base_duration_id])) {
    $badge_base_price = $badge_prices[$badge_base_duration_id];
} elseif (!empty($variants)) {
    foreach ($variants as $variant) {
        $base = floatval($variant->base_price);
        if ($base <= 0) {
            $base = floatval($variant->mietpreis_monatlich);
        }
        if ($base > 0 && ($badge_base_price <= 0 || $base < $badge_base_price)) {
            $badge_base_price = $base;
        }
    }
}

// Get category settings
$default_image = isset($category) ? $category->default_image : '';
$product_title = isset($category) ? $category->product_title : '';
$product_description = isset($category) ? $category->product_description : '';

// Features
$features_title = isset($category) ? ($category->features_title ?? '') : '';
$feature_1_icon = isset($category) ? $category->feature_1_icon : '';
$feature_1_title = isset($category) ? $category->feature_1_title : '';
$feature_1_description = isset($category) ? $category->feature_1_description : '';
$feature_2_icon = isset($category) ? $category->feature_2_icon : '';
$feature_2_title = isset($category) ? $category->feature_2_title : '';
$feature_2_description = isset($category) ? $category->feature_2_description : '';
$feature_3_icon = isset($category) ? $category->feature_3_icon : '';
$feature_3_title = isset($category) ? $category->feature_3_title : '';
$feature_3_description = isset($category) ? $category->feature_3_description : '';
$feature_4_icon = isset($category) ? $category->feature_4_icon : '';
$feature_4_title = isset($category) ? $category->feature_4_title : '';
$feature_4_description = isset($category) ? $category->feature_4_description : '';
$show_features = isset($category) ? ($category->show_features ?? 0) : 0;

$default_feature_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 81.5 81.9"><path d="M56.5,26.8l-21.7,21.7-9.7-9.7c-1.2-1.2-3.3-1.2-4.5,0s-1.2,3.3,0,4.5l12,12c.6.6,1.5.9,2.3.9s1.6-.3,2.3-.9l24-23.9c1.2-1.2,1.2-3.3,0-4.5-1.3-1.3-3.3-1.3-4.5,0Z"/><path d="M40.8,1C18.7,1,.8,18.9.8,41s17.9,40,40,40,40-17.9,40-40S62.8,1,40.8,1ZM40.8,74.6c-18.5,0-33.6-15.1-33.6-33.6S22.3,7.4,40.8,7.4s33.6,15.1,33.6,33.6-15.1,33.6-33.6,33.6Z"/></svg>';
// Button
$ui = get_option('produkt_ui_settings', []);
$category_button = isset($category) && property_exists($category, 'button_text') ? trim((string) $category->button_text) : '';
$global_button = isset($ui['button_text']) ? trim((string) $ui['button_text']) : '';
$legacy_button_defaults = ['In den Warenkorb', 'Jetzt kaufen', 'Jetzt mieten'];
$custom_label = ($category_button !== '' && !in_array($category_button, $legacy_button_defaults, true)) ? $category_button : $global_button;
$button_text = $custom_label; // default, final label determined later
$button_icon = $ui['button_icon'] ?? '';
$payment_icons = is_array($ui['payment_icons'] ?? null) ? $ui['payment_icons'] : [];
$accordions = isset($category) && property_exists($category, 'accordion_data') ? json_decode($category->accordion_data, true) : [];
if (!is_array($accordions)) { $accordions = []; }
$page_blocks = isset($category) && property_exists($category, 'page_blocks') ? json_decode($category->page_blocks, true) : [];
if (!is_array($page_blocks)) { $page_blocks = []; }
$detail_blocks = isset($category) && property_exists($category, 'detail_blocks') ? json_decode($category->detail_blocks, true) : [];
if (!is_array($detail_blocks)) { $detail_blocks = []; }
$tech_blocks = isset($category) && property_exists($category, 'tech_blocks') ? json_decode($category->tech_blocks, true) : [];
if (!is_array($tech_blocks)) { $tech_blocks = []; }
$scope_blocks = isset($category) && property_exists($category, 'scope_blocks') ? json_decode($category->scope_blocks, true) : [];
if (!is_array($scope_blocks)) { $scope_blocks = []; }

$shipping = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
$shipping_methods = [];
$select_shipping = false;
if (!$shipping) {
    $shipping_methods = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_shipping_methods ORDER BY price ASC");
    if (!empty($shipping_methods)) {
        $shipping = $shipping_methods[0];
        $select_shipping = true;
    }
}
$shipping_price_id = $shipping->stripe_price_id ?? '';
$shipping_cost = $shipping->price ?? 0;
$shipping_provider = $shipping->service_provider ?? '';
$modus = get_option('produkt_betriebsmodus', 'miete');
$cart_mode = get_option('produkt_miete_cart_mode', 'direct');
$cart_enabled = $modus === 'kauf' || ($modus === 'miete' && $cart_mode === 'cart');
$button_text = !empty($button_text)
    ? $button_text
    : ($modus === 'kauf' ? 'Jetzt kaufen' : ($cart_enabled ? 'In den Warenkorb' : 'Jetzt mieten'));
$price_label = $ui['price_label'] ?? ($modus === 'kauf' ? 'Einmaliger Kaufpreis' : 'Monatlicher Mietpreis');
$shipping_label = $ui['shipping_label'] ?? 'Einmalige Versandkosten:';
$price_period = $ui['price_period'] ?? 'month';
$vat_included = isset($ui['vat_included']) ? intval($ui['vat_included']) : 0;

// Layout
$layout_style = isset($category) ? ($category->layout_style ?? 'default') : 'default';
$price_layout = isset($category) ? ($category->price_layout ?? 'default') : 'default';
$description_layout = isset($category) ? ($category->description_layout ?? 'left') : 'left';

// Tooltips
$duration_tooltip = $ui['duration_tooltip'] ?? '';
$condition_tooltip = $ui['condition_tooltip'] ?? '';
$show_tooltips = isset($ui['show_tooltips']) ? intval($ui['show_tooltips']) : 1;
$tooltip_icon = '<svg viewBox="0 0 16.7 16.9" width="16" height="16" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" fill-rule="evenodd" d="M8.3,16.4C3.9,16.4.3,12.8.3,8.4S3.9.4,8.3.4s8,3.6,8,8-3.6,8-8,8ZM7.4,10.4h1.7c0-.3,0-.5.1-.7s.2-.4.4-.6l.7-.6c.3-.3.5-.5.6-.8.1-.3.2-.6.2-.9,0-.7-.2-1.3-.7-1.7-.5-.4-1.2-.6-2-.6s-1.6.2-2,.7c-.5.4-.7,1-.7,1.8h2c0-.3,0-.5.2-.7.1-.2.3-.3.6-.3.5,0,.8.3.8.9s0,.5-.2.7-.4.4-.7.7c-.3.2-.5.5-.6.9-.1.3-.2.8-.2,1.4ZM7.2,12.2c0,.3.1.5.3.7s.5.3.8.3.6,0,.8-.3.3-.4.3-.7-.1-.5-.3-.7-.5-.3-.8-.3-.6,0-.8.3-.3.4-.3.7Z"/></svg>';
$show_rating = isset($category) ? ($category->show_rating ?? 0) : 0;
$rating_value = isset($category) ? floatval(str_replace(',', '.', $category->rating_value ?? 0)) : 0;
$rating_display = number_format($rating_value, 1, ',', '');
$rating_link = isset($category) ? ($category->rating_link ?? '') : '';

// Get initial conditions and colors (will be updated via AJAX when variant is selected)
$initial_conditions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_conditions WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$initial_product_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order",
    $category_id
));

$initial_frame_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order",
    $category_id
));
?>

<div class="produkt-container" data-category-id="<?php echo esc_attr($category_id); ?>" data-layout="<?php echo esc_attr($layout_style); ?>" data-shipping-cost="<?php echo esc_attr($shipping_cost); ?>" data-shipping-price-id="<?php echo esc_attr($shipping_price_id); ?>" data-shipping-provider="<?php echo esc_attr($shipping_provider); ?>">

    <?php if (function_exists('rank_math_the_breadcrumbs')): ?>
        <nav class="produkt-breadcrumbs" aria-label="Breadcrumb">
            <?php rank_math_the_breadcrumbs(); ?>
        </nav>
    <?php endif; ?>

    <div class="produkt-content">
        <div class="produkt-left">
            <div class="produkt-product-info">
                <div class="produkt-product-image">
                    <div class="produkt-image-gallery" id="produkt-image-gallery">
                        <!-- Main Image Container -->
                        <div class="produkt-main-image-container" id="produkt-main-image-container">
                            <?php if (!empty($default_image)): ?>
                                <img src="<?php echo esc_url($default_image); ?>" alt="Produkt" id="produkt-main-image" class="produkt-main-image">
                            <?php else: ?>
                                <div class="produkt-placeholder-image produkt-fade-in" id="produkt-placeholder">
                                    <svg viewBox="0 0 200 100" xmlns="http://www.w3.org/2000/svg" width="70%" height="100%">
                                        <rect width="100%" height="100%" fill="#f0f0f0" stroke="#ccc" stroke-width="0" rx="8" ry="8" />
                                        <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#666" font-size="14">Produktbild folgt in Kürze</text>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Extra Image Overlay -->
                            <div class="produkt-extra-overlay" id="produkt-extra-overlay" style="display: none;">
                                <img src="" alt="Extra" id="produkt-extra-image" class="produkt-extra-image">
                            </div>
                        </div>
                        
                        <!-- Thumbnail Navigation -->
                        <div class="produkt-thumbnails" id="produkt-thumbnails" style="display: none;">
                            <!-- Thumbnails will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <?php
                ob_start();
                ?>
                <div class="produkt-product-details">
                    <h1><?php echo esc_html($product_title); ?></h1>
                    <div class="produkt-card-price">
                        <?php
                            $pd = $price_data;
                            if (is_array($pd)) {
                                $pd['count'] = $price_count;
                                $pd['duration_count'] = $duration_count;
                                $pd['mode'] = $modus;
                            }
                            echo esc_html(pv_format_price_label($pd));
                        ?>
                    </div>
                    <?php if ($show_rating && $rating_value > 0): ?>
                    <div class="produkt-rating">
                        <span class="produkt-rating-number"><?php echo esc_html($rating_display); ?></span>
                        <span class="produkt-star-rating" style="--rating: <?php echo esc_attr($rating_value); ?>;"></span>
                        <?php if (!empty($rating_link)): ?>
                            <a href="<?php echo esc_url($rating_link); ?>" target="_blank">Bewertungen ansehen</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="produkt-product-description">
                        <?php echo wp_kses_post(wpautop($product_description)); ?>
                    </div>

                </div>
                <?php
                $product_details_markup = ob_get_clean();
                if ($description_layout !== 'right') {
                    echo $product_details_markup;
                }
                ?>
            </div>

<?php
ob_start();
?>
            <div class="produkt-price-display<?php echo $select_shipping ? ' no-default-shipping' : ''; ?><?php echo $price_layout === 'sidebar' ? ' sidebar-layout' : ''; ?>" id="produkt-price-display" style="display: none;">
                <div class="produkt-price-box produkt-monthly-box">
                    <div class="produkt-price-content">
                        <p class="produkt-price-label"><?php echo esc_html($price_label); ?></p>
                        <div class="produkt-price-wrapper">
                            <span class="produkt-original-price" id="produkt-original-price" style="display: none;"></span>
                            <?php $final_price_tag = $price_layout === 'sidebar' ? 'h2' : 'span'; ?>
                            <<?php echo $final_price_tag; ?> class="produkt-final-price" id="produkt-final-price">0,00€</<?php echo $final_price_tag; ?>>
                            <?php if ($price_period === 'month'): ?>
                            <span class="produkt-price-period">/Monat</span>
                            <?php endif; ?>
                        </div>
                        <p class="produkt-savings" id="produkt-savings" style="display: none;"></p>
                        <p class="produkt-weekend-note" id="produkt-weekend-note" style="display:none;"></p>
                        <p class="produkt-vat-note"><?php echo $vat_included ? 'inkl. MwSt.' : 'Kein Ausweis der Umsatzsteuer gemäß § 19 UStG.'; ?></p>
                    </div>
                </div>

                <?php if ($shipping): ?>
                <div class="produkt-price-box produkt-shipping-box">
                    <p class="produkt-price-label">
                        <?php echo esc_html($shipping_label); ?>
                    </p>
                    <?php if ($select_shipping && !empty($shipping_methods)): ?>
                    <div class="produkt-options shipping-options layout-list">
                        <?php foreach ($shipping_methods as $index => $method): ?>
                        <div class="produkt-option<?php echo $index === 0 ? ' selected' : ''; ?>" data-type="shipping" data-id="<?php echo esc_attr($method->id); ?>" data-price-id="<?php echo esc_attr($method->stripe_price_id); ?>" data-price="<?php echo esc_attr($method->price); ?>" data-provider="<?php echo esc_attr($method->service_provider); ?>" data-available="true">
                            <div class="produkt-option-content">
                                <span class="produkt-extra-name"><?php echo esc_html($method->name); ?></span>
                                <div class="produkt-extra-price"><?php echo number_format($method->price, 2, ',', '.'); ?>€</div>
                            </div>
                            <?php if (!empty($method->service_provider) && $method->service_provider !== 'none' && $method->service_provider !== 'pickup'): ?>
                                <img class="produkt-shipping-provider-icon" src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $method->service_provider . '.svg'); ?>" alt="<?php echo esc_attr(strtoupper($method->service_provider)); ?>">
                                <?php if (!empty($method->description)): ?>
                                    <span class="produkt-tooltip">
                                        <?php echo $tooltip_icon; ?>
                                        <span class="produkt-tooltiptext"><?php echo esc_html($method->description); ?></span>
                                    </span>
                                <?php endif; ?>
                            <?php elseif (!empty($method->description)): ?>
                                <span class="produkt-tooltip">
                                    <?php echo $tooltip_icon; ?>
                                    <span class="produkt-tooltiptext"><?php echo esc_html($method->description); ?></span>
                                </span>
                            <?php endif; ?>
                            <div class="produkt-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="produkt-price-wrapper">
                        <span class="produkt-final-price"><?php echo number_format($shipping_cost, 2, ',', '.'); ?>€</span>
                    </div>
                    <?php if (!empty($shipping_provider) && $shipping_provider !== 'none' && $shipping_provider !== 'pickup'): ?>
                        <img class="produkt-shipping-provider-icon" src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $shipping_provider . '.svg'); ?>" alt="<?php echo esc_attr(strtoupper($shipping_provider)); ?>">
                        <?php if (!empty($shipping->description)): ?>
                            <span class="produkt-tooltip">
                                <?php echo $tooltip_icon; ?>
                                <span class="produkt-tooltiptext"><?php echo esc_html($shipping->description); ?></span>
                            </span>
                        <?php endif; ?>
                    <?php elseif (!empty($shipping->description)): ?>
                        <span class="produkt-tooltip">
                            <?php echo $tooltip_icon; ?>
                            <span class="produkt-tooltiptext"><?php echo esc_html($shipping->description); ?></span>
                        </span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
<?php
$price_display_markup = ob_get_clean();
if ($price_layout !== 'sidebar') {
    echo $price_display_markup;
}
?>

        </div>

        <div class="produkt-right">
            <?php if ($description_layout === 'right') { echo $product_details_markup; } ?>
            <?php if ($price_layout === 'sidebar') { echo $price_display_markup; } ?>
            <div class="produkt-configuration">
                <!-- Variants Selection -->
                <?php if (!empty($variants)): ?>
                <div class="produkt-section">
                    <h3>Wählen Sie Ihre Ausführung</h3>
                    <div class="produkt-options variants layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($variants as $variant): ?>
                        <div class="produkt-option <?php echo !($variant->available ?? 1) ? 'unavailable' : ''; ?>"
                             data-type="variant"
                             data-id="<?php echo esc_attr($variant->id); ?>"
                             data-available="<?php echo esc_attr(($variant->available ?? 1) ? 'true' : 'false'); ?>"
                             data-delivery="<?php echo esc_attr($variant->delivery_time ?? ''); ?>"
                             data-weekend="<?php echo intval($variant->weekend_only ?? 0); ?>"
                             data-min-days="<?php echo intval($variant->min_rental_days ?? 0); ?>"
                             data-weekend-price="<?php echo esc_attr($variant->weekend_price ?? 0); ?>"
                             data-sale-enabled="<?php echo esc_attr($variant->sale_enabled ?? 0); ?>"
                             data-sale-price-id="<?php echo esc_attr($variant->stripe_price_id_sale ?? ''); ?>"
                             data-sale-price="<?php echo esc_attr($variant->verkaufspreis_einmalig ?? 0); ?>"
                             data-images="<?php echo esc_attr(json_encode(array(
                                 $variant->image_url_1 ?? '',
                                 $variant->image_url_2 ?? '',
                                 $variant->image_url_3 ?? '',
                                 $variant->image_url_4 ?? '',
                                 $variant->image_url_5 ?? ''
                             ))); ?>">
                            <div class="produkt-option-content">
                                <h4><?php echo esc_html($variant->name); ?></h4>
                                <p><?php echo esc_html($variant->description); ?></p>
                                <?php
                                    $display_price = 0;
                                    $variant_id = (int) $variant->id;
                                    if ($modus === 'kauf') {
                                        $display_price = floatval($variant->verkaufspreis_einmalig);
                                    } else {
                                        if (isset($variant_min_prices[$variant_id])) {
                                            $display_price = $variant_min_prices[$variant_id];
                                        } elseif (!empty($variant->stripe_price_id)) {
                                            $p = \ProduktVerleih\StripeService::get_price_amount($variant->stripe_price_id);
                                            if (!is_wp_error($p)) {
                                                $display_price = $p;
                                            }
                                        }
                                    }
                                ?>
                                <?php
                                    $price_prefix = ($modus === 'miete' && $duration_count >= 2 && $display_price > 0) ? 'ab ' : '';
                                    $formatted_price = number_format($display_price, 2, ',', '.') . '€';
                                    if ($modus !== 'kauf' && $price_period === 'month') {
                                        $formatted_price .= '/Monat';
                                    }
                                ?>
                                <p class="produkt-option-price"><?php echo esc_html($price_prefix . $formatted_price); ?></p>
                                <?php if ($modus === 'kauf' && floatval($variant->weekend_price) > 0): ?>
                                    <div class="produkt-weekend-info">Wochenendpreis: <?php echo number_format((float)$variant->weekend_price, 2, ',', '.'); ?>€</div>
                                <?php endif; ?>
                                <?php if (!($variant->available ?? 1)): ?>
                                    <div class="produkt-availability-notice">
                                        <span class="produkt-unavailable-badge"><span class="produkt-emoji">❌</span> Nicht verfügbar</span>
                                        <?php if (!empty($variant->availability_note)): ?>
                                            <p class="produkt-availability-note"><?php echo esc_html($variant->availability_note); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="produkt-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($modus === 'kauf'): ?>
                <div class="produkt-section" id="booking-section">
                    <h3>Mietzeitraum</h3>
                    <div class="produkt-booking-calendar" id="booking-calendar"></div>
                    <div id="booking-info"></div>
                </div>
                <?php endif; ?>

                <!-- Extras Selection -->
                <?php if (!empty($extras)): ?>
                <div class="produkt-section" id="extras-section">
                    <h3>Wählen Sie Ihre Extras</h3>
                    <div class="produkt-options extras layout-<?php echo esc_attr($layout_style); ?>" id="extras-container">
                        <?php foreach ($extras as $extra):
                            $pid = $modus === 'kauf'
                                ? ($extra->stripe_price_id_sale ?: ($extra->stripe_price_id ?? ''))
                                : ($extra->stripe_price_id_rent ?: ($extra->stripe_price_id ?? ''));
                        ?>
                        <div class="produkt-option" data-type="extra" data-id="<?php echo esc_attr($extra->id); ?>"
                             data-extra-image="<?php echo esc_attr($extra->image_url ?? ''); ?>"
                             data-price-id="<?php echo esc_attr($pid); ?>"
                             data-sale-price-id="<?php echo esc_attr($extra->stripe_price_id_sale ?? ''); ?>"
                             data-rent-price-id="<?php echo esc_attr($extra->stripe_price_id_rent ?? ''); ?>"
                             data-available="<?php echo intval($extra->available ?? 1) ? 'true' : 'false'; ?>"
                             data-stock="<?php echo intval($extra->stock_available); ?>">
                            <div class="produkt-option-content">
                                <span class="produkt-extra-name"><?php echo esc_html($extra->name); ?></span>
                                <?php if (!empty($pid)) {
                                    $p = \ProduktVerleih\StripeService::get_price_amount($pid);
                                    if (!is_wp_error($p) && $p > 0) {
                                        echo '<div class="produkt-extra-price">+' . number_format($p, 2, ',', '.') . '€' . ($price_period === 'month' ? '/Monat' : '') . '</div>';
                                    }
                                } ?>
                            </div>
                            <div class="produkt-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Duration Selection -->
                <?php if (!empty($durations)): ?>
                <div class="produkt-section">
                    <h3>
                        Wählen Sie Ihre Mietdauer
                        <?php if ($show_tooltips): ?>
                        <span class="produkt-tooltip">
                            <?php echo $tooltip_icon; ?>
                            <span class="produkt-tooltiptext"><?php echo esc_html($duration_tooltip); ?></span>
                        </span>
                        <?php endif; ?>
                    </h3>
                    <div class="produkt-options durations layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($durations as $duration):
                            $popular_gradient_start = function_exists('pv_normalize_hex_color_value')
                                ? pv_normalize_hex_color_value($duration->popular_gradient_start ?? '', '#ff8a3d')
                                : (sanitize_hex_color($duration->popular_gradient_start ?? '') ?: '#ff8a3d');
                            $popular_gradient_end = function_exists('pv_normalize_hex_color_value')
                                ? pv_normalize_hex_color_value($duration->popular_gradient_end ?? '', '#ff5b0f')
                                : (sanitize_hex_color($duration->popular_gradient_end ?? '') ?: '#ff5b0f');
                            $popular_text_color = function_exists('pv_normalize_hex_color_value')
                                ? pv_normalize_hex_color_value($duration->popular_text_color ?? '', '#ffffff')
                                : (sanitize_hex_color($duration->popular_text_color ?? '') ?: '#ffffff');
                            $popular_style = sprintf(
                                '--popular-gradient-start:%1$s; --popular-gradient-end:%2$s; --popular-text-color:%3$s;',
                                $popular_gradient_start,
                                $popular_gradient_end,
                                $popular_text_color
                            );
                        ?>
                        <div class="produkt-option" data-type="duration" data-id="<?php echo esc_attr($duration->id); ?>">
                            <?php if (!empty($duration->show_popular)): ?>
                            <span class="produkt-popular-badge" style="<?php echo esc_attr($popular_style); ?>">beliebt</span>
                            <?php endif; ?>
                            <div class="produkt-option-content">
                                <div class="produkt-duration-header">
                                    <span class="produkt-duration-name"><?php echo esc_html($duration->name); ?></span>
                                    <?php if ($duration->show_badge && isset($badge_prices[$duration->id]) && $badge_base_price > 0): ?>
                                        <?php
                                            $mietpreis = $badge_prices[$duration->id];
                                            if ($mietpreis > 0 && $mietpreis < $badge_base_price) {
                                                $rabatt = number_format((1 - ($mietpreis / $badge_base_price)) * 100, 1, ',', '');
                                                echo '<span class="produkt-discount-badge">-' . $rabatt . '%</span>';
                                            }
                                        ?>
                                    <?php endif; ?>
                                </div>
                                <p class="produkt-duration-info">
                                    Mindestlaufzeit: <?php echo $duration->months_minimum; ?> Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?>
                                </p>
                            </div>
                            <div class="produkt-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Condition Selection (initially populated, will be updated via AJAX) -->
                <div class="produkt-section" id="condition-section" style="<?php echo esc_attr(empty($initial_conditions) ? 'display: none;' : ''); ?>">
                    <h3>
                        Zustand
                        <?php if ($show_tooltips): ?>
                        <span class="produkt-tooltip">
                            <?php echo $tooltip_icon; ?>
                            <span class="produkt-tooltiptext"><?php echo esc_html($condition_tooltip); ?></span>
                        </span>
                        <?php endif; ?>
                    </h3>
                    <div class="produkt-options conditions layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_conditions as $condition): ?>
                        <div class="produkt-option" data-type="condition" data-id="<?php echo esc_attr($condition->id); ?>" data-available="true">
                            <div class="produkt-option-content">
                                <div class="produkt-condition-header">
                                    <span class="produkt-condition-name"><?php echo esc_html($condition->name); ?></span>
                                    <?php if ($condition->price_modifier != 0): ?>
                                    <span class="produkt-condition-badge">
                                        <?php echo $condition->price_modifier > 0 ? '+' : ''; ?><?php echo round($condition->price_modifier * 100); ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="produkt-condition-info"><?php echo esc_html($condition->description); ?></p>
                            </div>
                            <div class="produkt-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Color Selection (initially populated, will be updated via AJAX) -->
                <div class="produkt-section" id="product-color-section" style="<?php echo esc_attr(empty($initial_product_colors) ? 'display: none;' : ''); ?>">
                    <h3>Produktfarbe</h3>
                    <small id="selected-product-color-name" class="produkt-selected-color-name"></small>
                    <div class="produkt-options product-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_product_colors as $color): ?>
                        <?php
                            $color_preview_class = 'produkt-color-preview' . (!empty($color->is_multicolor) ? ' produkt-color-preview--multicolor' : '');
                            $color_preview_style = empty($color->is_multicolor) ? 'background-color: ' . esc_attr($color->color_code) . ';' : '';
                        ?>
                        <div class="produkt-option" data-type="product-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="produkt-option-content">
                                <div class="produkt-color-display">
                                    <div class="<?php echo esc_attr($color_preview_class); ?>" style="<?php echo esc_attr($color_preview_style); ?>"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Frame Color Selection (initially populated, will be updated via AJAX) -->
                <div class="produkt-section" id="frame-color-section" style="<?php echo esc_attr(empty($initial_frame_colors) ? 'display: none;' : ''); ?>">
                    <h3>Gestellfarbe</h3>
                    <small id="selected-frame-color-name" class="produkt-selected-color-name"></small>
                    <div class="produkt-options frame-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_frame_colors as $color): ?>
                        <?php
                            $frame_preview_class = 'produkt-color-preview' . (!empty($color->is_multicolor) ? ' produkt-color-preview--multicolor' : '');
                            $frame_preview_style = empty($color->is_multicolor) ? 'background-color: ' . esc_attr($color->color_code) . ';' : '';
                        ?>
                        <div class="produkt-option" data-type="frame-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="produkt-option-content">
                                <div class="produkt-color-display">
                                    <div class="<?php echo esc_attr($frame_preview_class); ?>" style="<?php echo esc_attr($frame_preview_style); ?>"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Rent Button -->
                <div class="produkt-button-section">
					 <form id="produkt-order-form" method="post">
                        <input type="hidden" name="produkt" id="produkt-field-produkt">
                        <input type="hidden" name="extra" id="produkt-field-extra">
                        <input type="hidden" name="dauer" id="produkt-field-dauer">
                        <input type="hidden" name="dauer_name" id="produkt-field-dauer-name">
                        <input type="hidden" name="zustand" id="produkt-field-zustand">
                        <input type="hidden" name="farbe" id="produkt-field-farbe">
                        <input type="hidden" name="produktfarbe" id="produkt-field-produktfarbe">
                        <input type="hidden" name="gestellfarbe" id="produkt-field-gestellfarbe">
                        <input type="hidden" name="preis" id="produkt-field-preis">
                        <input type="hidden" name="shipping" id="produkt-field-shipping">
                        <input type="hidden" name="variant_id" id="produkt-field-variant-id">
                        <input type="hidden" name="duration_id" id="produkt-field-duration-id">
                        <input type="hidden" name="start_date" id="produkt-field-start-date">
                        <input type="hidden" name="end_date" id="produkt-field-end-date">
                        <input type="hidden" name="days" id="produkt-field-days">
                        <input type="hidden" name="price_id" id="produkt-field-price-id">
                        <input type="hidden" name="jetzt_mieten" value="1">
                    <div class="produkt-availability-wrapper" id="produkt-availability-wrapper" style="display:none;">
                        <div id="produkt-availability-status" class="produkt-availability-status available">
                            <span class="status-dot"></span>
                            <span class="status-text">Sofort verfügbar</span>
                        </div>
                        <div id="produkt-delivery-box" class="produkt-delivery-box" style="display:none;">
                            Lieferung <span id="produkt-delivery-time">3-5 Werktage</span>
                        </div>
                    </div>
                    <?php
                    $required_selections = array();
                    if (!empty($variants)) $required_selections[] = 'variant';
                    if (!empty($extras)) $required_selections[] = 'extra';
                    if (!empty($durations)) $required_selections[] = 'duration';
                    // Note: conditions and colors are optional and will be checked dynamically
                    ?>
                    
                    <?php if (!empty($required_selections)): ?>
                    <button id="produkt-rent-button" class="produkt-rent-button" disabled data-icon="<?php echo esc_url($button_icon); ?>">
                        <?php if (!empty($button_icon)): ?>
                            <img src="<?php echo esc_url($button_icon); ?>" alt="Button Icon" class="produkt-button-icon-img">
                        <?php endif; ?>
                        <span><?php echo esc_html($button_text); ?></span>
                    </button>
                    <button id="produkt-direct-buy-button" type="button" class="produkt-direct-buy-button" style="display:none;">
                        <span>oder direkt kaufen</span>
                    </button>
                    <p class="produkt-button-help" id="produkt-button-help">
                        Bitte treffen Sie alle Auswahlen um fortzufahren
                    </p>
                    <p class="produkt-unavailable-help" id="produkt-unavailable-help" style="display: none;">
                        Das gewählte Produkt ist aktuell nicht verfügbar
                    </p>
                    <div class="produkt-notify" id="produkt-notify" style="display: none;">
                        <p>Benachrichtigen Sie mich sobald das Produkt wieder erhältlich ist.</p>
                        <div class="produkt-notify-form">
                            <input type="email" id="produkt-notify-email" placeholder="Ihre E-Mail" required>
                            <button id="produkt-notify-submit">Senden</button>
                        </div>
                        <p class="produkt-notify-success" id="produkt-notify-success" style="display:none;">Vielen Dank! Wir benachrichtigen Sie umgehend.</p>
                    </div>
                    <?php if (!empty($payment_icons)): ?>
                    <div class="produkt-payment-icons">
                        <?php foreach ($payment_icons as $icon): ?>
                            <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $icon . '.svg'); ?>" alt="<?php echo esc_attr($icon); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($accordions)): ?>
                    <div class="produkt-accordions">
                        <?php foreach ($accordions as $acc): ?>
                        <div class="produkt-accordion-item">
                            <button type="button" class="produkt-accordion-header"><?php echo esc_html($acc['title']); ?></button>
                            <div class="produkt-accordion-content"><?php echo wp_kses_post(wpautop($acc['content'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div style="padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; text-align: center;">
                        <h4>⚠️ Produkt noch nicht vollständig konfiguriert</h4>
                        <p>Für dieses Produkt sind noch nicht alle erforderlichen Daten hinterlegt.</p>
                        <p><strong>Bitte konfigurieren Sie die fehlenden Daten im Admin-Bereich.</strong></p>
                    </div>
                    <?php endif; ?>
					</form>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <?php if ($show_features): ?>
    <div class="produkt-features-section">
        <h3><?php echo esc_html($features_title); ?></h3>
        <div class="produkt-features-grid">
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_1_icon)): ?>
                        <img src="<?php echo esc_url($feature_1_icon); ?>" alt="<?php echo esc_attr($feature_1_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <?php echo $default_feature_svg; ?>
                    <?php endif; ?>
                </div>
                <div class="produkt-feature-text">
                    <h4><?php echo esc_html($feature_1_title); ?></h4>
                    <p><?php echo esc_html($feature_1_description); ?></p>
                </div>
            </div>
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_2_icon)): ?>
                        <img src="<?php echo esc_url($feature_2_icon); ?>" alt="<?php echo esc_attr($feature_2_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <?php echo $default_feature_svg; ?>
                    <?php endif; ?>
                </div>
                <div class="produkt-feature-text">
                    <h4><?php echo esc_html($feature_2_title); ?></h4>
                    <p><?php echo esc_html($feature_2_description); ?></p>
                </div>
            </div>
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_3_icon)): ?>
                        <img src="<?php echo esc_url($feature_3_icon); ?>" alt="<?php echo esc_attr($feature_3_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <?php echo $default_feature_svg; ?>
                    <?php endif; ?>
                </div>
                <div class="produkt-feature-text">
                    <h4><?php echo esc_html($feature_3_title); ?></h4>
                    <p><?php echo esc_html($feature_3_description); ?></p>
                </div>
            </div>
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_4_icon)): ?>
                        <img src="<?php echo esc_url($feature_4_icon); ?>" alt="<?php echo esc_attr($feature_4_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <?php echo $default_feature_svg; ?>
                    <?php endif; ?>
                </div>
                <div class="produkt-feature-text">
                    <h4><?php echo esc_html($feature_4_title); ?></h4>
                    <p><?php echo esc_html($feature_4_description); ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <?php if (!empty($page_blocks)): ?>
        <?php foreach ($page_blocks as $i => $block): ?>
        <div class="produkt-seo-block<?php echo $i % 2 === 1 ? ' reverse' : ''; ?>">
            <div class="produkt-seo-text">
                <?php if (!empty($block['title'])): ?>
                <h3><?php echo esc_html($block['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($block['text'])): ?>
                <p><?php echo wp_kses_post(wpautop($block['text'])); ?></p>
                <?php endif; ?>
            </div>
            <div class="produkt-seo-image">
                <?php if (!empty($block['image'])): ?>
                <img src="<?php echo esc_url($block['image']); ?>" alt="<?php echo esc_attr($block['alt'] ?? ($block['title'] ?? '')); ?>">
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($detail_blocks)): ?>
    <div class="produkt-info-section produkt-accordion-item">
        <button type="button" class="produkt-accordion-header">Details</button>
        <div class="produkt-accordion-content">
        <div class="produkt-info-grid">
        <?php foreach ($detail_blocks as $block): ?>
        <div class="produkt-info-block">
            <?php if (!empty($block['title'])): ?>
            <h4><?php echo esc_html($block['title']); ?></h4>
            <?php endif; ?>
            <?php if (!empty($block['text'])): ?>
            <p><?php echo wp_kses_post(wpautop($block['text'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($tech_blocks)): ?>
    <div class="produkt-info-section produkt-accordion-item">
        <button type="button" class="produkt-accordion-header">Technische Daten</button>
        <div class="produkt-accordion-content">
        <div class="produkt-info-grid">
        <?php foreach ($tech_blocks as $block): ?>
        <div class="produkt-info-block">
            <?php if (!empty($block['title'])): ?>
            <h4><?php echo esc_html($block['title']); ?></h4>
            <?php endif; ?>
            <?php if (!empty($block['text'])): ?>
            <p><?php echo wp_kses_post(wpautop($block['text'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($scope_blocks)): ?>
    <div class="produkt-info-section produkt-accordion-item">
        <button type="button" class="produkt-accordion-header">Lieferumfang</button>
        <div class="produkt-accordion-content">
        <div class="produkt-info-grid">
        <?php foreach ($scope_blocks as $block): ?>
        <div class="produkt-info-block">
            <?php if (!empty($block['title'])): ?>
            <h4><?php echo esc_html($block['title']); ?></h4>
            <?php endif; ?>
            <?php if (!empty($block['text'])): ?>
            <p><?php echo wp_kses_post(wpautop($block['text'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        </div>
    </div>
    <?php endif; ?>
</div>




<div id="produkt-exit-popup" class="produkt-exit-popup" style="display:none;">
    <div class="produkt-exit-popup-content">
        <button type="button" class="produkt-exit-popup-close">&times;</button>
        <h3 id="produkt-exit-title"></h3>
        <div id="produkt-exit-message"></div>
        <div id="produkt-exit-email-wrapper" style="display:none;">
            <input type="email" id="produkt-exit-email" placeholder="E-Mail-Adresse">
        </div>
        <div id="produkt-exit-select-wrapper" style="display:none;">
            <select id="produkt-exit-select"></select>
        </div>
        <button id="produkt-exit-send" style="display:none;">Senden</button>
    </div>
</div>
<script>
if (typeof produkt_ajax !== 'undefined') {
    produkt_ajax.blocked_days = <?php echo json_encode($blocked_days); ?>;
    produkt_ajax.variant_blocked_days = [];
    produkt_ajax.extra_blocked_days = [];
    produkt_ajax.variant_weekend_only = false;
    produkt_ajax.variant_min_days = 0;
}
</script>
<?php get_footer(); ?>
