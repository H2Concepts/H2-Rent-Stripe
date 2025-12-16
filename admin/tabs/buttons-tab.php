<?php
// Buttons & Tooltips Tab Content

if (isset($_POST['submit_buttons'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    global $wpdb;
    $previous_ui = get_option('produkt_ui_settings', []);
    $settings = [
        'button_text' => sanitize_text_field($_POST['button_text'] ?? ''),
        'button_icon' => esc_url_raw($_POST['button_icon'] ?? ''),
        'payment_icons' => isset($_POST['payment_icons']) ? array_map('sanitize_text_field', (array) $_POST['payment_icons']) : [],
        'price_label' => sanitize_text_field($_POST['price_label'] ?? ''),
        'shipping_label' => sanitize_text_field($_POST['shipping_label'] ?? ''),
        'price_period' => sanitize_text_field($_POST['price_period'] ?? 'month'),
        'vat_included' => isset($_POST['vat_included']) ? 1 : 0,
        'duration_tooltip' => sanitize_textarea_field($_POST['duration_tooltip'] ?? ''),
        'condition_tooltip' => sanitize_textarea_field($_POST['condition_tooltip'] ?? ''),
        'cart_icon' => esc_url_raw($_POST['cart_icon'] ?? ''),
        'cart_badge_position' => in_array(
            $_POST['cart_badge_position'] ?? 'top_right',
            ['top_right', 'top_left', 'bottom_right', 'bottom_left'],
            true
        )
            ? sanitize_text_field($_POST['cart_badge_position'])
            : 'top_right',
        'show_tooltips' => isset($_POST['show_tooltips']) ? 1 : 0,
    ];
    update_option('produkt_ui_settings', $settings);

    $old_label = isset($previous_ui['button_text']) ? trim((string) $previous_ui['button_text']) : '';
    $new_label = trim((string) $settings['button_text']);
    $legacy_defaults = ['In den Warenkorb', 'Jetzt kaufen', 'Jetzt mieten'];
    $labels_to_replace = array_filter(array_unique(array_merge(
        $old_label !== '' ? [$old_label] : [],
        $legacy_defaults
    )), static fn($value) => $value !== $new_label);

    if (!empty($labels_to_replace)) {
        $placeholders = implode(',', array_fill(0, count($labels_to_replace), '%s'));
        $query = "UPDATE {$wpdb->prefix}produkt_categories SET button_text = %s WHERE button_text IN ($placeholders)";
        $wpdb->query($wpdb->prepare($query, array_merge([$new_label], $labels_to_replace)));
    }

    $menus = isset($_POST['menu_locations']) ? array_map('intval', (array) $_POST['menu_locations']) : [];
    update_option('produkt_menu_locations', $menus);
    update_option('produkt_inject_block_nav_all', isset($_POST['inject_block_nav_all']) ? 1 : 0);
    if (isset($_POST['order_number_start'])) {
        update_option('produkt_next_order_number', sanitize_text_field($_POST['order_number_start']));
    }
    if (isset($_POST['invoice_number_start'])) {
        update_option('produkt_next_invoice_number', sanitize_text_field($_POST['invoice_number_start']));
    }
    echo '<div class="notice notice-success"><p>✅ ' . esc_html__('Einstellungen gespeichert!', 'h2-rental-pro') . '</p></div>';
}

$ui = wp_parse_args(get_option('produkt_ui_settings', []), [
    'button_text' => '',
    'button_icon' => '',
    'cart_icon' => '',
    'cart_badge_position' => 'top_right',
    'payment_icons' => [],
    'price_label' => '',
    'shipping_label' => '',
    'price_period' => 'month',
    'vat_included' => 0,
    'duration_tooltip' => '',
    'condition_tooltip' => '',
    'show_tooltips' => 1,
]);
$next_order_nr = get_option('produkt_next_order_number', '');
$last_order_nr = get_option('produkt_last_order_number', '');
$next_invoice_nr = get_option('produkt_next_invoice_number', '');
$last_invoice_nr = get_option('produkt_last_invoice_number', '');
$menu_locations = get_option('produkt_menu_locations', []);
$all_menus = wp_get_nav_menus();
$inject_block_nav_all = (int) get_option('produkt_inject_block_nav_all', 0);
?>
<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_buttons" class="icon-btn buttons-save-btn"
            aria-label="<?php echo esc_attr__('Speichern', 'h2-rental-pro'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path
                    d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z" />
                <path
                    d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z" />
            </svg>
        </button>
        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Buttons', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Beschriftung und Preisinformationen', 'h2-rental-pro'); ?>
                </p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Jetzt mieten Text', 'h2-rental-pro'); ?></label>
                        <input type="text" name="button_text" value="<?php echo esc_attr($ui['button_text']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Button-Icon', 'h2-rental-pro'); ?></label>
                        <div class="image-field-row">
                            <div id="button_icon_preview" class="image-preview">
                                <?php if (!empty($ui['button_icon'])): ?>
                                    <img src="<?php echo esc_url($ui['button_icon']); ?>" alt="">
                                <?php else: ?>
                                    <span><?php echo esc_html__('Noch kein Bild vorhanden', 'h2-rental-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn produkt-media-button" data-target="button_icon"
                                aria-label="<?php echo esc_attr__('Bild auswählen', 'h2-rental-pro'); ?>">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6">
                                    <path
                                        d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z" />
                                </svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="button_icon"
                                aria-label="<?php echo esc_attr__('Bild entfernen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                    <path
                                        d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z" />
                                    <path
                                        d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z" />
                                </svg>
                            </button>
                        </div>
                        <input type="hidden" name="button_icon" id="button_icon"
                            value="<?php echo esc_attr($ui['button_icon']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Preis-Label', 'h2-rental-pro'); ?></label>
                        <input type="text" name="price_label" value="<?php echo esc_attr($ui['price_label']); ?>"
                            placeholder="<?php echo esc_attr__('Monatlicher Mietpreis', 'h2-rental-pro'); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Versand-Label', 'h2-rental-pro'); ?></label>
                        <input type="text" name="shipping_label" value="<?php echo esc_attr($ui['shipping_label']); ?>"
                            placeholder="<?php echo esc_attr__('Einmalige Versandkosten', 'h2-rental-pro'); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Preiszeitraum', 'h2-rental-pro'); ?></label>
                        <select name="price_period">
                            <option value="month" <?php selected($ui['price_period'], 'month'); ?>>
                                <?php echo esc_html__('pro Monat', 'h2-rental-pro'); ?>
                            </option>
                            <option value="one-time" <?php selected($ui['price_period'], 'one-time'); ?>>
                                <?php echo esc_html__('pro Tag', 'h2-rental-pro'); ?>
                            </option>
                        </select>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('MwSt label anzeigen?', 'h2-rental-pro'); ?></label>
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="vat_included" value="1" <?php checked($ui['vat_included'], 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Warenkorb', 'h2-rental-pro'); ?></h2>
                <p class="card-subline"><?php echo esc_html__('Menü-Auswahl', 'h2-rental-pro'); ?></p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Warenkorb-Icon', 'h2-rental-pro'); ?></label>
                        <div class="image-field-row">
                            <div id="cart_icon_preview" class="image-preview">
                                <?php if (!empty($ui['cart_icon'])): ?>
                                    <img src="<?php echo esc_url($ui['cart_icon']); ?>" alt="">
                                <?php else: ?>
                                    <span><?php echo esc_html__('Noch kein Bild vorhanden', 'h2-rental-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn produkt-media-button" data-target="cart_icon"
                                aria-label="<?php echo esc_attr__('Bild auswählen', 'h2-rental-pro'); ?>">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6">
                                    <path
                                        d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z" />
                                </svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="cart_icon"
                                aria-label="<?php echo esc_attr__('Bild entfernen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                    <path
                                        d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z" />
                                    <path
                                        d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z" />
                                </svg>
                            </button>
                        </div>
                        <input type="hidden" name="cart_icon" id="cart_icon"
                            value="<?php echo esc_attr($ui['cart_icon']); ?>">
                        <p class="description">
                            <?php echo esc_html__('Eigenes Warenkorb-Icon für die Navigation hochladen.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Menüs', 'h2-rental-pro'); ?></label>
                        <?php if (!empty($all_menus)): ?>
                            <select name="menu_locations[]" multiple size="<?php echo count($all_menus); ?>">
                                <?php foreach ($all_menus as $menu): ?>
                                    <option value="<?php echo esc_attr($menu->term_id); ?>" <?php echo in_array((int) $menu->term_id, (array) $menu_locations, true) ? 'selected' : ''; ?>>
                                        <?php echo esc_html($menu->name . ' (' . $menu->slug . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php echo esc_html__('In den ausgewählten Menüs wird der Warenkorb-Button angezeigt.', 'h2-rental-pro'); ?>
                            </p>
                        <?php else: ?>
                            <p><?php echo esc_html__('Keine Menüs gefunden.', 'h2-rental-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Position der Warenkorb-Badge', 'h2-rental-pro'); ?></label>
                        <select name="cart_badge_position">
                            <option value="top_right" <?php selected($ui['cart_badge_position'], 'top_right'); ?>>
                                <?php echo esc_html__('Oben rechts', 'h2-rental-pro'); ?>
                            </option>
                            <option value="top_left" <?php selected($ui['cart_badge_position'], 'top_left'); ?>>
                                <?php echo esc_html__('Oben links', 'h2-rental-pro'); ?>
                            </option>
                            <option value="bottom_right" <?php selected($ui['cart_badge_position'], 'bottom_right'); ?>>
                                <?php echo esc_html__('Unten rechts', 'h2-rental-pro'); ?>
                            </option>
                            <option value="bottom_left" <?php selected($ui['cart_badge_position'], 'bottom_left'); ?>>
                                <?php echo esc_html__('Unten links', 'h2-rental-pro'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Wählen Sie in welcher Ecke die Stückzahl-Badge am Icon angezeigt wird.', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <div class="produkt-form-group full-width">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="inject_block_nav_all" value="1" <?php checked($inject_block_nav_all, 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span><?php echo esc_html__('In alle Navigations-Blöcke einfügen', 'h2-rental-pro'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Bezahlmethoden', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Icons der unterstützten Zahlungsmittel', 'h2-rental-pro'); ?>
                </p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Icons auswählen', 'h2-rental-pro'); ?></label>
                        <div class="produkt-payment-checkboxes">
                            <?php $payment_methods = [
                                'american-express' => 'American Express',
                                'apple-pay' => 'Apple Pay',
                                'google-pay' => 'Google Pay',
                                'klarna' => 'Klarna',
                                'maestro' => 'Maestro',
                                'mastercard' => 'Mastercard',
                                'paypal' => 'Paypal',
                                'shop' => 'Shop',
                                'union-pay' => 'Union Pay',
                                'visa' => 'Visa'
                            ]; ?>
                            <?php foreach ($payment_methods as $key => $label): ?>
                                <label>
                                    <input type="checkbox" name="payment_icons[]" value="<?php echo esc_attr($key); ?>"
                                        <?php checked(in_array($key, (array) $ui['payment_icons'])); ?>>
                                    <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/payment-icons/' . $key . '.svg'); ?>"
                                        alt="<?php echo esc_attr($label); ?>">
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2><?php echo esc_html__('Tooltips', 'h2-rental-pro'); ?></h2>
                        <p class="card-subline">
                            <?php echo esc_html__('Hilfetexte auf der Produktseite', 'h2-rental-pro'); ?>
                        </p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="show_tooltips" value="1" <?php checked($ui['show_tooltips'], 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span><?php echo esc_html__('Tooltips auf Produktseite anzeigen', 'h2-rental-pro'); ?></span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Mietdauer-Tooltip', 'h2-rental-pro'); ?></label>
                        <textarea name="duration_tooltip"
                            rows="3"><?php echo esc_textarea($ui['duration_tooltip']); ?></textarea>
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Zustand-Tooltip', 'h2-rental-pro'); ?></label>
                        <textarea name="condition_tooltip"
                            rows="4"><?php echo esc_textarea($ui['condition_tooltip']); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Bestellnummer', 'h2-rental-pro'); ?></h2>
                <p class="card-subline"><?php echo esc_html__('Startwert der laufenden Nummer', 'h2-rental-pro'); ?></p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Bestellnummer Startwert', 'h2-rental-pro'); ?></label>
                        <input type="text" name="order_number_start" value="<?php echo esc_attr($next_order_nr); ?>">
                        <?php if ($last_order_nr): ?>
                            <p class="description">
                                <?php printf(esc_html__('Letzte vergebene Bestellnummer: %s', 'h2-rental-pro'), esc_html($last_order_nr)); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2><?php echo esc_html__('Rechnungsnummer', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Startwert der laufenden Rechnungsnummer', 'h2-rental-pro'); ?>
                </p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Rechnungsnummer Startwert', 'h2-rental-pro'); ?></label>
                        <input type="text" name="invoice_number_start"
                            value="<?php echo esc_attr($next_invoice_nr); ?>">
                        <?php if ($last_invoice_nr): ?>
                            <p class="description">
                                <?php printf(esc_html__('Letzte vergebene Rechnungsnummer: %s', 'h2-rental-pro'), esc_html($last_invoice_nr)); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.produkt-media-button').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.getElementById(this.dataset.target);
                const preview = document.getElementById(this.dataset.target + '_preview');
                const frame = wp.media({ title: '<?php echo esc_js(__('Bild auswählen', 'h2-rental-pro')); ?>', button: { text: '<?php echo esc_js(__('Bild verwenden', 'h2-rental-pro')); ?>' }, multiple: false });
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    if (target) { target.value = attachment.url; }
                    if (preview) { preview.innerHTML = '<img src="' + attachment.url + '" alt="">'; }
                });
                frame.open();
            });
        });
        document.querySelectorAll('.produkt-remove-image').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = document.getElementById(this.dataset.target);
                const preview = document.getElementById(this.dataset.target + '_preview');
                if (target) { target.value = ''; }
                if (preview) { preview.innerHTML = '<span><?php echo esc_js(__('Noch kein Bild vorhanden', 'h2-rental-pro')); ?></span>'; }
            });
        });
    });
</script>