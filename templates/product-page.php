<?php

global $wpdb;

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

$durations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}produkt_durations WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

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
$show_features = isset($category) ? ($category->show_features ?? 1) : 1;

// Button
$button_text = isset($category) ? $category->button_text : '';
$button_icon = isset($category) ? $category->button_icon : '';
$payment_icons = [];
if (isset($category) && property_exists($category, 'payment_icons')) {
    $payment_icons = array_filter(array_map('trim', explode(',', $category->payment_icons)));
}
$accordions = isset($category) && property_exists($category, 'accordion_data') ? json_decode($category->accordion_data, true) : [];
if (!is_array($accordions)) { $accordions = []; }

$shipping_price_id = isset($category) ? ($category->shipping_price_id ?? '') : '';
$shipping_cost = 0;
if (!empty($shipping_price_id)) {
    $amount = \ProduktVerleih\StripeService::get_price_amount($shipping_price_id);
    if (!is_wp_error($amount)) {
        $shipping_cost = $amount;
    }
}
$shipping_provider = isset($category) ? ($category->shipping_provider ?? '') : '';
$price_label = isset($category) ? ($category->price_label ?? 'Monatlicher Mietpreis') : 'Monatlicher Mietpreis';
$shipping_label = isset($category) ? ($category->shipping_label ?? 'Einmalige Versandkosten:') : 'Einmalige Versandkosten:';
$price_period = isset($category) ? ($category->price_period ?? 'month') : 'month';
$vat_included = isset($category) ? ($category->vat_included ?? 0) : 0;

// Layout
$layout_style = isset($category) ? ($category->layout_style ?? 'default') : 'default';

// Tooltips
$duration_tooltip = isset($category) ? ($category->duration_tooltip ?? '') : '';
$condition_tooltip = isset($category) ? ($category->condition_tooltip ?? '') : '';
$show_tooltips = isset($category) ? ($category->show_tooltips ?? 1) : 1;
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

<div class="produkt-container" data-category-id="<?php echo esc_attr($category_id); ?>" data-layout="<?php echo esc_attr($layout_style); ?>" data-shipping-cost="<?php echo esc_attr($shipping_cost); ?>">

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
                                <div class="produkt-placeholder-image" id="produkt-placeholder">👶</div>
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
                
                <div class="produkt-product-details">
                    <h2><?php echo esc_html($product_title); ?></h2>
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
            </div>

             <div class="produkt-price-display" id="produkt-price-display" style="display: none;">
                <div class="produkt-price-box produkt-monthly-box">
                    <div class="produkt-price-content">
                        <p class="produkt-price-label"><?php echo esc_html($price_label); ?></p>
                        <div class="produkt-price-wrapper">
                            <span class="produkt-original-price" id="produkt-original-price" style="display: none;"></span>
                            <span class="produkt-final-price" id="produkt-final-price">0,00€</span>
                            <?php if ($price_period === 'month'): ?>
                            <span class="produkt-price-period">/Monat</span>
                            <?php endif; ?>
                        </div>
                        <p class="produkt-savings" id="produkt-savings" style="display: none;"></p>
                        <p class="produkt-vat-note"><?php echo $vat_included ? 'inkl. MwSt.' : 'Kein Ausweis der Umsatzsteuer gemäß § 19 UStG.'; ?></p>
                    </div>
                </div>

                <?php if (!empty($shipping_price_id)): ?>
                <div class="produkt-price-box produkt-shipping-box">
                    <p class="produkt-price-label">
                        <span class="produkt-shipping-icon">🚚</span>
                        <?php echo esc_html($shipping_label); ?>
                    </p>
                    <div class="produkt-price-wrapper">
                        <span class="produkt-final-price"><?php echo number_format($shipping_cost, 2, ',', '.'); ?>€</span>
                    </div>
                    <?php if (!empty($shipping_provider)): ?>
                        <img class="produkt-shipping-provider-icon" src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $shipping_provider . '.svg'); ?>" alt="<?php echo esc_attr(strtoupper($shipping_provider)); ?>">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="produkt-right">
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
                                    if (!empty($variant->stripe_price_id)) {
                                        $p = \ProduktVerleih\StripeService::get_price_amount($variant->stripe_price_id);
                                        if (!is_wp_error($p)) {
                                            $display_price = $p;
                                        }
                                    }
                                ?>
                                <p class="produkt-option-price"><?php echo number_format($display_price, 2, ',', '.'); ?>€<?php echo $price_period === 'month' ? '/Monat' : ''; ?></p>
                                <?php if (!($variant->available ?? 1)): ?>
                                    <div class="produkt-availability-notice">
                                        <span class="produkt-unavailable-badge">❌ Nicht verfügbar</span>
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

                <!-- Extras Selection -->
                <?php if (!empty($extras)): ?>
                <div class="produkt-section" id="extras-section">
                    <h3>Wählen Sie Ihre Extras</h3>
                    <div class="produkt-options extras layout-<?php echo esc_attr($layout_style); ?>" id="extras-container">
                        <?php foreach ($extras as $extra): ?>
                        <div class="produkt-option" data-type="extra" data-id="<?php echo esc_attr($extra->id); ?>"
                             data-extra-image="<?php echo esc_attr($extra->image_url ?? ''); ?>"
                             data-available="true">
                            <div class="produkt-option-content">
                                <span class="produkt-extra-name"><?php echo esc_html($extra->name); ?></span>
                                <?php if (!empty($extra->stripe_price_id)) {
                                    $p = \ProduktVerleih\StripeService::get_price_amount($extra->stripe_price_id);
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
                            ℹ️
                            <span class="produkt-tooltiptext"><?php echo esc_html($duration_tooltip); ?></span>
                        </span>
                        <?php endif; ?>
                    </h3>
                    <div class="produkt-options durations layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($durations as $duration): ?>
                        <div class="produkt-option" data-type="duration" data-id="<?php echo esc_attr($duration->id); ?>">
                            <div class="produkt-option-content">
                                <div class="produkt-duration-header">
                                    <span class="produkt-duration-name"><?php echo esc_html($duration->name); ?></span>
                                    <?php if ($duration->discount > 0): ?>
                                    <span class="produkt-discount-badge">-<?php echo round($duration->discount * 100); ?>%</span>
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
                            ℹ️
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
                        <div class="produkt-option" data-type="product-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="produkt-option-content">
                                <div class="produkt-color-display">
                                    <div class="produkt-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
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
                        <div class="produkt-option" data-type="frame-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="produkt-option-content">
                                <div class="produkt-color-display">
                                    <div class="produkt-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
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
                        <input type="hidden" name="shipping_price_id" id="produkt-field-shipping-price-id" value="<?php echo esc_attr($shipping_price_id); ?>">
                        <input type="hidden" name="variant_id" id="produkt-field-variant-id">
                        <input type="hidden" name="duration_id" id="produkt-field-duration-id">
                        <input type="hidden" name="price_id" id="produkt-field-price-id">
                        <input type="hidden" name="jetzt_mieten" value="1">
                    <div class="produkt-availability-wrapper" id="produkt-availability-wrapper" style="display:none;">
                        <div id="produkt-availability-status" class="produkt-availability-status available">
                            <span class="status-dot"></span>
                            <span class="status-text">Sofort verfügbar</span>
                        </div>
                        <div id="produkt-delivery-box" class="produkt-delivery-box" style="display:none;">
                            Lieferung in <span id="produkt-delivery-time">3-5 Werktagen</span>
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
                    <button id="produkt-rent-button" class="produkt-rent-button" disabled>
                        <?php if (!empty($button_icon)): ?>
                            <img src="<?php echo esc_url($button_icon); ?>" alt="Button Icon" class="produkt-button-icon-img">
                        <?php else: ?>
                            <span class="produkt-button-icon">🛒</span>
                        <?php endif; ?>
                        <span><?php echo esc_html($button_text); ?></span>
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
                        🛡️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_1_title); ?></h4>
                <p><?php echo esc_html($feature_1_description); ?></p>
            </div>
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_2_icon)): ?>
                        <img src="<?php echo esc_url($feature_2_icon); ?>" alt="<?php echo esc_attr($feature_2_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        ❤️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_2_title); ?></h4>
                <p><?php echo esc_html($feature_2_description); ?></p>
            </div>
            <div class="produkt-feature-item">
                <div class="produkt-feature-icon-large">
                    <?php if (!empty($feature_3_icon)): ?>
                        <img src="<?php echo esc_url($feature_3_icon); ?>" alt="<?php echo esc_attr($feature_3_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        📱
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_3_title); ?></h4>
                <p><?php echo esc_html($feature_3_description); ?></p>
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
        <div id="produkt-exit-select-wrapper" style="display:none;">
            <select id="produkt-exit-select"></select>
        </div>
        <button id="produkt-exit-send" style="display:none;">Senden</button>
    </div>
</div>
