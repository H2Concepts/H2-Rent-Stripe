<?php

global $wpdb;

// Get category data
$category_id = isset($category) ? $category->id : 1;

// Get all data for this category
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$extras = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$durations = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE category_id = %d ORDER BY sort_order",
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

// Button
$button_text = isset($category) ? $category->button_text : '';
$button_icon = isset($category) ? $category->button_icon : '';
$payment_icons = [];
if (isset($category) && property_exists($category, 'payment_icons')) {
    $payment_icons = array_filter(array_map('trim', explode(',', $category->payment_icons)));
}

// Shipping
$shipping_cost = defined('FEDERWIEGEN_SHIPPING_COST')
    ? constant('FEDERWIEGEN_SHIPPING_COST')
    : 0;
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
    "SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d ORDER BY sort_order",
    $category_id
));

$initial_product_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order",
    $category_id
));

$initial_frame_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order",
    $category_id
));
?>

<div class="federwiegen-container" data-category-id="<?php echo esc_attr($category_id); ?>" data-layout="<?php echo esc_attr($layout_style); ?>">

    <div class="federwiegen-content">
        <div class="federwiegen-left">
            <div class="federwiegen-product-info">
                <div class="federwiegen-product-image">
                    <div class="federwiegen-image-gallery" id="federwiegen-image-gallery">
                        <!-- Main Image Container -->
                        <div class="federwiegen-main-image-container" id="federwiegen-main-image-container">
                            <?php if (!empty($default_image)): ?>
                                <img src="<?php echo esc_url($default_image); ?>" alt="Federwiege" id="federwiegen-main-image" class="federwiegen-main-image">
                            <?php else: ?>
                                <div class="federwiegen-placeholder-image" id="federwiegen-placeholder">👶</div>
                            <?php endif; ?>
                            
                            <!-- Extra Image Overlay -->
                            <div class="federwiegen-extra-overlay" id="federwiegen-extra-overlay" style="display: none;">
                                <img src="" alt="Extra" id="federwiegen-extra-image" class="federwiegen-extra-image">
                            </div>
                        </div>
                        
                        <!-- Thumbnail Navigation -->
                        <div class="federwiegen-thumbnails" id="federwiegen-thumbnails" style="display: none;">
                            <!-- Thumbnails will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <div class="federwiegen-product-details">
                    <h2><?php echo esc_html($product_title); ?></h2>
                    <?php if ($show_rating && $rating_value > 0): ?>
                    <div class="federwiegen-rating">
                        <span class="federwiegen-rating-number"><?php echo esc_html($rating_display); ?></span>
                        <span class="federwiegen-star-rating" style="--rating: <?php echo esc_attr($rating_value); ?>;"></span>
                        <?php if (!empty($rating_link)): ?>
                            <a href="<?php echo esc_url($rating_link); ?>" target="_blank">Bewertungen ansehen</a>
                        <?php else: ?>
                            <span>Bewertungen ansehen</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="federwiegen-product-description">
                        <?php echo wp_kses_post(wpautop($product_description)); ?>
                    </div>
                    
                    <div class="federwiegen-features">
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">🛡️</span>
                            <span>Sicherheitsgeprüft</span>
                        </div>
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">❤️</span>
                            <span>Baby-gerecht</span>
                        </div>
                        <div class="federwiegen-feature">
                            <span class="federwiegen-feature-icon">📱</span>
                            <span>App-Steuerung</span>
                        </div>
                    </div>
                </div>
            </div>

             <div class="federwiegen-price-display" id="federwiegen-price-display" style="display: none;">
                <div class="federwiegen-price-box federwiegen-monthly-box">
                    <div class="federwiegen-price-content">
                        <p class="federwiegen-price-label"><?php echo esc_html($price_label); ?></p>
                        <div class="federwiegen-price-wrapper">
                            <span class="federwiegen-original-price" id="federwiegen-original-price" style="display: none;"></span>
                            <span class="federwiegen-final-price" id="federwiegen-final-price">0,00€</span>
                            <?php if ($price_period === 'month'): ?>
                            <span class="federwiegen-price-period">/Monat</span>
                            <?php endif; ?>
                        </div>
                        <p class="federwiegen-savings" id="federwiegen-savings" style="display: none;"></p>
                        <p class="federwiegen-vat-note"><?php echo $vat_included ? 'inkl. MwSt.' : 'Kein Ausweis der Umsatzsteuer gemäß § 19 UStG.'; ?></p>
                    </div>
                    <div class="federwiegen-price-icon">💶</div>
                </div>

                <div class="federwiegen-price-box federwiegen-shipping-box">
                    <p class="federwiegen-price-label">
                        <span class="federwiegen-shipping-icon">🚚</span>
                        <?php echo esc_html($shipping_label); ?>
                    </p>
                    <div class="federwiegen-price-wrapper">
                        <span class="federwiegen-final-price"><?php echo number_format($shipping_cost, 2, ',', '.'); ?>€</span>
                    </div>
                    <?php if (!empty($shipping_provider)): ?>
                        <img class="federwiegen-shipping-provider-icon" src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/shipping-icons/' . $shipping_provider . '.svg'); ?>" alt="<?php echo esc_attr(strtoupper($shipping_provider)); ?>">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="federwiegen-right">
            <div class="federwiegen-configuration">
                <!-- Variants Selection -->
                <?php if (!empty($variants)): ?>
                <div class="federwiegen-section">
                    <h3>Wählen Sie Ihre Ausführung</h3>
                    <div class="federwiegen-options variants layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($variants as $variant): ?>
                        <div class="federwiegen-option <?php echo !($variant->available ?? 1) ? 'unavailable' : ''; ?>" 
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
                            <div class="federwiegen-option-content">
                                <h4><?php echo esc_html($variant->name); ?></h4>
                                <p><?php echo esc_html($variant->description); ?></p>
                                <?php
                                    $display_price = 0;
                                    if (!empty($variant->stripe_price_id)) {
                                        $p = \FederwiegenVerleih\StripeService::get_price_amount($variant->stripe_price_id);
                                        if (!is_wp_error($p)) {
                                            $display_price = $p;
                                        }
                                    }
                                ?>
                                <p class="federwiegen-option-price"><?php echo number_format($display_price, 2, ',', '.'); ?>€<?php echo $price_period === 'month' ? '/Monat' : ''; ?></p>
                                <?php if (!($variant->available ?? 1)): ?>
                                    <div class="federwiegen-availability-notice">
                                        <span class="federwiegen-unavailable-badge">❌ Nicht verfügbar</span>
                                        <?php if (!empty($variant->availability_note)): ?>
                                            <p class="federwiegen-availability-note"><?php echo esc_html($variant->availability_note); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Extras Selection -->
                <?php if (!empty($extras)): ?>
                <div class="federwiegen-section" id="extras-section">
                    <h3>Wählen Sie Ihre Extras</h3>
                    <div class="federwiegen-options extras layout-<?php echo esc_attr($layout_style); ?>" id="extras-container">
                        <?php foreach ($extras as $extra): ?>
                        <div class="federwiegen-option" data-type="extra" data-id="<?php echo esc_attr($extra->id); ?>"
                             data-extra-image="<?php echo esc_attr($extra->image_url ?? ''); ?>"
                             data-available="true">
                            <div class="federwiegen-option-content">
                                <span class="federwiegen-extra-name"><?php echo esc_html($extra->name); ?></span>
                                <?php if (!empty($extra->stripe_price_id)) {
                                    $p = \FederwiegenVerleih\StripeService::get_price_amount($extra->stripe_price_id);
                                    if (!is_wp_error($p) && $p > 0) {
                                        echo '<div class="federwiegen-extra-price">+' . number_format($p, 2, ',', '.') . '€' . ($price_period === 'month' ? '/Monat' : '') . '</div>';
                                    }
                                } ?>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Duration Selection -->
                <?php if (!empty($durations)): ?>
                <div class="federwiegen-section">
                    <h3>
                        Wählen Sie Ihre Mietdauer
                        <?php if ($show_tooltips): ?>
                        <span class="federwiegen-tooltip">
                            ℹ️
                            <span class="federwiegen-tooltiptext"><?php echo esc_html($duration_tooltip); ?></span>
                        </span>
                        <?php endif; ?>
                    </h3>
                    <div class="federwiegen-options durations layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($durations as $duration): ?>
                        <div class="federwiegen-option" data-type="duration" data-id="<?php echo esc_attr($duration->id); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-duration-header">
                                    <span class="federwiegen-duration-name"><?php echo esc_html($duration->name); ?></span>
                                    <?php if ($duration->discount > 0): ?>
                                    <span class="federwiegen-discount-badge">-<?php echo round($duration->discount * 100); ?>%</span>
                                    <?php endif; ?>
                                </div>
                                <p class="federwiegen-duration-info">
                                    Mindestlaufzeit: <?php echo $duration->months_minimum; ?> Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?>
                                </p>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Condition Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="condition-section" style="<?php echo esc_attr(empty($initial_conditions) ? 'display: none;' : ''); ?>">
                    <h3>
                        Zustand
                        <?php if ($show_tooltips): ?>
                        <span class="federwiegen-tooltip">
                            ℹ️
                            <span class="federwiegen-tooltiptext"><?php echo esc_html($condition_tooltip); ?></span>
                        </span>
                        <?php endif; ?>
                    </h3>
                    <div class="federwiegen-options conditions layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_conditions as $condition): ?>
                        <div class="federwiegen-option" data-type="condition" data-id="<?php echo esc_attr($condition->id); ?>" data-available="true">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-condition-header">
                                    <span class="federwiegen-condition-name"><?php echo esc_html($condition->name); ?></span>
                                    <?php if ($condition->price_modifier != 0): ?>
                                    <span class="federwiegen-condition-badge">
                                        <?php echo $condition->price_modifier > 0 ? '+' : ''; ?><?php echo round($condition->price_modifier * 100); ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p class="federwiegen-condition-info"><?php echo esc_html($condition->description); ?></p>
                            </div>
                            <div class="federwiegen-option-check">✓</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Product Color Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="product-color-section" style="<?php echo esc_attr(empty($initial_product_colors) ? 'display: none;' : ''); ?>">
                    <h3>Produktfarbe</h3>
                    <small id="selected-product-color-name" class="federwiegen-selected-color-name"></small>
                    <div class="federwiegen-options product-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_product_colors as $color): ?>
                        <div class="federwiegen-option" data-type="product-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-color-display">
                                    <div class="federwiegen-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Frame Color Selection (initially populated, will be updated via AJAX) -->
                <div class="federwiegen-section" id="frame-color-section" style="<?php echo esc_attr(empty($initial_frame_colors) ? 'display: none;' : ''); ?>">
                    <h3>Gestellfarbe</h3>
                    <small id="selected-frame-color-name" class="federwiegen-selected-color-name"></small>
                    <div class="federwiegen-options frame-colors layout-<?php echo esc_attr($layout_style); ?>">
                        <?php foreach ($initial_frame_colors as $color): ?>
                        <div class="federwiegen-option" data-type="frame-color" data-id="<?php echo esc_attr($color->id); ?>" data-available="true" data-color-name="<?php echo esc_attr($color->name); ?>" data-color-image="<?php echo esc_url($color->image_url ?? ''); ?>">
                            <div class="federwiegen-option-content">
                                <div class="federwiegen-color-display">
                                    <div class="federwiegen-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Rent Button -->
                <div class="federwiegen-button-section">
					 <form id="federwiegen-order-form" method="post">
                        <input type="hidden" name="produkt" id="federwiegen-field-produkt">
                        <input type="hidden" name="extra" id="federwiegen-field-extra">
                        <input type="hidden" name="dauer" id="federwiegen-field-dauer">
                        <input type="hidden" name="dauer_name" id="federwiegen-field-dauer-name">
                        <input type="hidden" name="zustand" id="federwiegen-field-zustand">
                        <input type="hidden" name="farbe" id="federwiegen-field-farbe">
                        <input type="hidden" name="preis" id="federwiegen-field-preis">
                        <input type="hidden" name="shipping" id="federwiegen-field-shipping">
                        <input type="hidden" name="jetzt_mieten" value="1">
                    <div class="federwiegen-availability-wrapper" id="federwiegen-availability-wrapper" style="display:none;">
                        <div id="federwiegen-availability-status" class="federwiegen-availability-status available">
                            <span class="status-dot"></span>
                            <span class="status-text">Sofort verfügbar</span>
                        </div>
                        <div id="federwiegen-delivery-box" class="federwiegen-delivery-box" style="display:none;">
                            Lieferung in <span id="federwiegen-delivery-time">3-5 Werktagen</span>
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
                    <button id="federwiegen-rent-button" class="federwiegen-rent-button" disabled>
                        <?php if (!empty($button_icon)): ?>
                            <img src="<?php echo esc_url($button_icon); ?>" alt="Button Icon" class="federwiegen-button-icon-img">
                        <?php else: ?>
                            <span class="federwiegen-button-icon">🛒</span>
                        <?php endif; ?>
                        <span><?php echo esc_html($button_text); ?></span>
                    </button>
                    <p class="federwiegen-button-help" id="federwiegen-button-help">
                        Bitte treffen Sie alle Auswahlen um fortzufahren
                    </p>
                    <p class="federwiegen-unavailable-help" id="federwiegen-unavailable-help" style="display: none;">
                        Das gewählte Produkt ist aktuell nicht verfügbar
                    </p>
                    <div class="federwiegen-notify" id="federwiegen-notify" style="display: none;">
                        <p>Benachrichtigen Sie mich sobald das Produkt wieder erhältlich ist.</p>
                        <div class="federwiegen-notify-form">
                            <input type="email" id="federwiegen-notify-email" placeholder="Ihre E-Mail" required>
                            <button id="federwiegen-notify-submit">Senden</button>
                        </div>
                        <p class="federwiegen-notify-success" id="federwiegen-notify-success" style="display:none;">Vielen Dank! Wir benachrichtigen Sie umgehend.</p>
                    </div>
                    <?php if (!empty($payment_icons)): ?>
                    <div class="federwiegen-payment-icons">
                        <?php foreach ($payment_icons as $icon): ?>
                            <img src="<?php echo esc_url(FEDERWIEGEN_PLUGIN_URL . 'assets/payment-icons/' . $icon . '.svg'); ?>" alt="<?php echo esc_attr($icon); ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div style="padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; text-align: center;">
                        <h4>⚠️ Kategorie noch nicht vollständig konfiguriert</h4>
                        <p>Für diese Produktkategorie sind noch nicht alle erforderlichen Daten hinterlegt.</p>
                        <p><strong>Bitte konfigurieren Sie die fehlenden Daten im Admin-Bereich.</strong></p>
                    </div>
                    <?php endif; ?>
					</form>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="federwiegen-features-section">
        <h3><?php echo esc_html($features_title); ?></h3>
        <div class="federwiegen-features-grid">
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
                    <?php if (!empty($feature_1_icon)): ?>
                        <img src="<?php echo esc_url($feature_1_icon); ?>" alt="<?php echo esc_attr($feature_1_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        🛡️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_1_title); ?></h4>
                <p><?php echo esc_html($feature_1_description); ?></p>
            </div>
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
                    <?php if (!empty($feature_2_icon)): ?>
                        <img src="<?php echo esc_url($feature_2_icon); ?>" alt="<?php echo esc_attr($feature_2_title); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        ❤️
                    <?php endif; ?>
                </div>
                <h4><?php echo esc_html($feature_2_title); ?></h4>
                <p><?php echo esc_html($feature_2_description); ?></p>
            </div>
            <div class="federwiegen-feature-item">
                <div class="federwiegen-feature-icon-large">
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
</div>

<div id="federwiegen-exit-popup" class="federwiegen-exit-popup" style="display:none;">
    <div class="federwiegen-exit-popup-content">
        <button type="button" class="federwiegen-exit-popup-close">&times;</button>
        <h3 id="federwiegen-exit-title"></h3>
        <div id="federwiegen-exit-message"></div>
        <div id="federwiegen-exit-select-wrapper" style="display:none;">
            <select id="federwiegen-exit-select"></select>
        </div>
        <button id="federwiegen-exit-send" style="display:none;">Senden</button>
    </div>
</div>
