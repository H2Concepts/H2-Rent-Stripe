<?php
// Email Settings Tab

if (!function_exists('produkt_send_test_customer_email')) {
    function produkt_send_test_customer_email()
    {
        $admin_email = get_option('admin_email');
        if (empty($admin_email)) {
            return new \WP_Error('missing_admin_email', __('Es ist keine Admin-E-Mail-Adresse hinterlegt.', 'h2-rental-pro'));
        }

        $start = current_time('timestamp');
        $end = strtotime('+30 days', $start);

        $order = [
            'customer_email' => $admin_email,
            'customer_name' => 'Max Mustermann',
            'customer_phone' => '01234 567890',
            'customer_street' => 'Musterstraße 1',
            'customer_postal' => '12345',
            'customer_city' => 'Musterstadt',
            'customer_country' => 'Deutschland',
            'mode' => 'kauf',
            'created_at' => current_time('mysql'),
            'final_price' => 199.99,
            'shipping_cost' => 9.99,
            'order_items' => json_encode([
                [
                    'produkt_name' => 'Kinderwagen Modell X',
                    'variant_name' => 'Edition 2024',
                    'extra_names' => 'Regenschutz, Becherhalter',
                    'product_color_name' => 'Space Grau',
                    'frame_color_name' => 'Schwarz',
                    'condition_name' => 'Neu',
                    'duration_name' => '1 Monat',
                    'start_date' => date('Y-m-d', $start),
                    'end_date' => date('Y-m-d', $end),
                    'final_price' => 199.99,
                ],
            ]),
            'order_number' => 'TEST-1001',
            'produkt_name' => 'Kinderwagen Modell X',
            'extra_text' => 'Regenschutz, Becherhalter',
            'dauer_text' => '1 Monat',
            'zustand_text' => 'Neu',
            'produktfarbe_text' => 'Space Grau',
            'gestellfarbe_text' => 'Schwarz',
        ];

        \ProduktVerleih\send_produkt_welcome_email($order, 0, false, true);

        return true;
    }
}

if (!function_exists('produkt_send_test_email_by_type')) {
    function produkt_send_test_email_by_type(string $type)
    {
        $admin_email = get_option('admin_email');
        if (empty($admin_email)) {
            return new \WP_Error('missing_admin_email', __('Es ist keine Admin-E-Mail-Adresse hinterlegt.', 'h2-rental-pro'));
        }

        switch ($type) {
            case 'login_code': {
                $site_title = get_bloginfo('name');
                $logo_url = get_option('plugin_firma_logo_url', '');
                $divider = '<div style="height:1px;background:#E6E8ED;margin:20px 0;"></div>';
                $code = 123456;

                $message = '<html><body style="margin:0;padding:0;background:#F6F7FA;font-family:Arial,sans-serif;color:#000;">';
                $message .= '<div style="max-width:680px;margin:0 auto;padding:24px;">';
                if ($logo_url) {
                    $message .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="width:100px;max-width:100%;height:auto;"></div>';
                }
                $message .= '<h1 style="text-align:center;font-size:22px;margin:0 0 40px;">' . esc_html__('Login-Code für dein Kundenkonto', 'h2-rental-pro') . '</h1>';
                $message .= '<div style="background:#FFFFFF;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">';
                $message .= '<p style="margin:0 0 12px;font-size:14px;line-height:1.6;">' . esc_html__('Gebe den Code zum Einloggen im Kundenkonto ein:', 'h2-rental-pro') . '</p>';
                $message .= '<div style="text-align:center;font-size:32px;font-weight:700;letter-spacing:6px;padding:14px;border:1px solid #E6E8ED;border-radius:12px;background:#F6F7FA;">' . esc_html($code) . '</div>';
                $message .= '<p style="margin:16px 0 0;font-size:14px;line-height:1.7;">' . esc_html__('Dies ist eine Test-E-Mail (Login-Code).', 'h2-rental-pro') . '<br><br><strong>' . esc_html__('Dieser Code ist nun für 15 Minuten gültig.', 'h2-rental-pro') . '</strong></p>';
                $message .= '</div>';
                $message .= $divider;
                $footer_html = function_exists('pv_get_email_footer_html') ? pv_get_email_footer_html() : '';
                if ($footer_html) {
                    $message .= $footer_html;
                }
                $message .= '</div>';
                $message .= '</body></html>';

                $headers = ['Content-Type: text/html; charset=UTF-8'];
                $from_name = get_bloginfo('name');
                $from_email = get_option('admin_email');
                $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

                $sent = wp_mail($admin_email, __('Ihr Login-Code (TEST)', 'h2-rental-pro'), $message, $headers);
                return $sent ? true : new \WP_Error('send_failed', __('Login-Code Test-E-Mail konnte nicht gesendet werden.', 'h2-rental-pro'));
            }

            case 'customer_order_confirmation':
                return produkt_send_test_customer_email();

            case 'admin_order_confirmation': {
                $start = current_time('timestamp');
                $end = strtotime('+30 days', $start);
                $order = [
                    'customer_email' => $admin_email,
                    'customer_name' => 'Max Mustermann',
                    'customer_phone' => '01234 567890',
                    'customer_street' => 'Musterstraße 1',
                    'customer_postal' => '12345',
                    'customer_city' => 'Musterstadt',
                    'customer_country' => 'Deutschland',
                    'mode' => 'kauf',
                    'created_at' => current_time('mysql'),
                    'final_price' => 199.99,
                    'shipping_cost' => 9.99,
                    'order_items' => json_encode([
                        [
                            'produkt_name' => 'Kinderwagen Modell X',
                            'variant_name' => 'Edition 2024',
                            'extra_names' => 'Regenschutz, Becherhalter',
                            'product_color_name' => 'Space Grau',
                            'frame_color_name' => 'Schwarz',
                            'condition_name' => 'Neu',
                            'duration_name' => '1 Monat',
                            'start_date' => date('Y-m-d', $start),
                            'end_date' => date('Y-m-d', $end),
                            'final_price' => 199.99,
                        ],
                    ]),
                    'order_number' => 'TEST-1001',
                    'produkt_name' => 'Kinderwagen Modell X',
                    'extra_text' => 'Regenschutz, Becherhalter',
                    'dauer_text' => '1 Monat',
                    'zustand_text' => 'Neu',
                    'produktfarbe_text' => 'Space Grau',
                    'gestellfarbe_text' => 'Schwarz',
                ];
                \ProduktVerleih\send_admin_order_email($order, 0, 'TEST-SESSION');
                return true;
            }

            case 'low_stock_variant': {
                global $wpdb;
                $variant_id = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}produkt_variants ORDER BY id ASC LIMIT 1");
                if (!$variant_id) {
                    return new \WP_Error('missing_variant', __('Keine Ausführung gefunden (für Lagerbestand-niedrig Test).', 'h2-rental-pro'));
                }
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT option_id, COALESCE(condition_id, 0) AS condition_id, stock_available, stock_threshold
                     FROM {$wpdb->prefix}produkt_variant_options
                     WHERE variant_id = %d AND option_type = 'product_color'
                     ORDER BY id ASC LIMIT 1",
                    $variant_id
                ));
                $product_color_id = $row ? (int) $row->option_id : null;
                $condition_id = $row ? ((int) $row->condition_id ?: null) : null;
                $stock_available = $row ? (int) ($row->stock_available ?? 0) : 2;
                $threshold = $row ? (int) ($row->stock_threshold ?? 2) : 2;
                \ProduktVerleih\send_admin_low_stock_email($variant_id, $stock_available, $product_color_id, $condition_id, $threshold);
                return true;
            }

            case 'low_stock_extra': {
                global $wpdb;
                $extra = $wpdb->get_row("SELECT id, stock_available, stock_threshold FROM {$wpdb->prefix}produkt_extras ORDER BY id ASC LIMIT 1");
                $extra_id = $extra ? (int) $extra->id : 0;
                $stock_available = $extra ? (int) ($extra->stock_available ?? 2) : 2;
                $threshold = $extra ? (int) ($extra->stock_threshold ?? 2) : 2;

                if (function_exists('\\ProduktVerleih\\send_admin_low_stock_extra_email')) {
                    \ProduktVerleih\send_admin_low_stock_extra_email($extra_id ?: 0, $stock_available, $threshold);
                    return true;
                }

                return new \WP_Error('missing_template', __('Extra Low-Stock Template-Funktion wurde nicht gefunden.', 'h2-rental-pro'));
            }

            case 'customer_rental_cancellation': {
                if (!function_exists('\\ProduktVerleih\\send_customer_rental_cancellation_email')) {
                    return new \WP_Error('missing_template', __('Kündigungsbestätigung (Kunde) Template-Funktion wurde nicht gefunden.', 'h2-rental-pro'));
                }

                $now = current_time('timestamp');
                $end = strtotime('+21 days', $now);

                $order_override = [
                    'customer_email' => $admin_email,
                    'customer_name' => 'Max Mustermann',
                    'customer_phone' => '01234 567890',
                    'customer_street' => 'Musterstraße 1',
                    'customer_postal' => '12345',
                    'customer_city' => 'Musterstadt',
                    'customer_country' => 'Deutschland',
                    'mode' => 'miete',
                    'order_number' => 'TEST-MIETE-2001',
                    'created_at' => current_time('mysql'),
                    // Used for status counts
                    'order_items' => json_encode([
                        [
                            'rental_status' => 'gekündigt',
                            'end_date' => date('Y-m-d', $end),
                        ],
                    ]),
                    // Used for rendering product details in this template
                    'produkte' => [
                        (object) [
                            'produkt_name' => 'Kinderwagen Modell X',
                            'variant_name' => 'Edition 2024',
                            'extra_names' => 'Regenschutz, Becherhalter',
                            'condition_name' => 'Neu',
                            'product_color_name' => 'Space Grau',
                            'frame_color_name' => 'Schwarz',
                            'final_price' => 39.90,
                            'start_date' => date('Y-m-d', $now),
                            'end_date' => date('Y-m-d', $end),
                            'image_url' => '',
                        ],
                    ],
                ];

                \ProduktVerleih\send_customer_rental_cancellation_email(0, 0, $order_override);
                return true;
            }

            case 'admin_rental_cancellation': {
                if (!function_exists('\\ProduktVerleih\\send_admin_rental_cancellation_email')) {
                    return new \WP_Error('missing_template', __('Kündigung (Admin) Template-Funktion wurde nicht gefunden.', 'h2-rental-pro'));
                }

                $now = current_time('timestamp');
                $end = strtotime('+21 days', $now);

                $order_override = [
                    'customer_email' => $admin_email,
                    'customer_name' => 'Max Mustermann',
                    'customer_phone' => '01234 567890',
                    'customer_street' => 'Musterstraße 1',
                    'customer_postal' => '12345',
                    'customer_city' => 'Musterstadt',
                    'customer_country' => 'Deutschland',
                    'mode' => 'miete',
                    'order_number' => 'TEST-MIETE-2001',
                    'created_at' => current_time('mysql'),
                    'order_items' => json_encode([
                        [
                            'rental_status' => 'gekündigt',
                            'end_date' => date('Y-m-d', $end),
                        ],
                        [
                            'rental_status' => 'aktiv',
                        ],
                    ]),
                    'produkte' => [
                        (object) [
                            'produkt_name' => 'Kinderwagen Modell X',
                            'variant_name' => 'Edition 2024',
                            'extra_names' => 'Regenschutz, Becherhalter',
                            'condition_name' => 'Neu',
                            'product_color_name' => 'Space Grau',
                            'frame_color_name' => 'Schwarz',
                            'final_price' => 39.90,
                            'start_date' => date('Y-m-d', $now),
                            'end_date' => date('Y-m-d', $end),
                            'image_url' => '',
                        ],
                        (object) [
                            'produkt_name' => 'Buggy Modell Y',
                            'variant_name' => 'Classic',
                            'extra_names' => '',
                            'condition_name' => 'Neu',
                            'product_color_name' => 'Schwarz',
                            'frame_color_name' => '',
                            'final_price' => 29.90,
                            'start_date' => date('Y-m-d', $now),
                            'end_date' => '',
                            'image_url' => '',
                        ],
                    ],
                ];

                \ProduktVerleih\send_admin_rental_cancellation_email(0, 0, $order_override);
                return true;
            }

            case 'review_reminder': {
                $account_page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
                $account_url = $account_page_id ? get_permalink($account_page_id) : home_url('/kundenkonto');
                $subscription_key = 'demo-sub-001';
                $cta_url = add_query_arg([
                    'view' => 'abos',
                    'review' => $subscription_key,
                ], $account_url);

                $sent = \ProduktVerleih\send_produkt_review_reminder_email(
                    $admin_email,
                    'Max Mustermann',
                    'Kinderfahrrad Woom GO 2',
                    $cta_url,
                    date('Y-m-d', current_time('timestamp') - DAY_IN_SECONDS)
                );

                return $sent ? true : new \WP_Error('send_failed', 'Bewertungs-Erinnerung konnte nicht gesendet werden.');
            }

            case 'newsletter': {
                $token = 'TEST-TOKEN-' . bin2hex(random_bytes(16));
                $confirm_url = add_query_arg([
                    'action' => 'produkt_newsletter_confirm',
                    'token' => rawurlencode($token),
                ], admin_url('admin-post.php'));

                $subject = __('Bitte Newsletter-Anmeldung bestätigen (TEST)', 'h2-rental-pro');

                $site_title = get_bloginfo('name');
                $logo_url = get_option('plugin_firma_logo_url', '');
                $divider = '<div style="height:1px;background:#E6E8ED;margin:20px 0;"></div>';

                $message = '<html><body style="margin:0;padding:0;background:#F6F7FA;font-family:Arial,sans-serif;color:#000;">';
                $message .= '<div style="max-width:680px;margin:0 auto;padding:24px;">';

                if ($logo_url) {
                    $message .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="width:100px;max-width:100%;height:auto;"></div>';
                }

                $message .= '<h1 style="text-align:center;font-size:22px;margin:0 0 40px;">' . esc_html__('Newsletter-Anmeldung bestätigen', 'h2-rental-pro') . '</h1>';
                $message .= '<p style="margin:0 0 16px;font-size:14px;line-height:1.6;">' . esc_html__('Hallo,', 'h2-rental-pro') . '<br>' . esc_html__('vielen Dank für Ihre Newsletter-Anmeldung! Bitte bestätigen Sie Ihre Anmeldung, indem Sie auf den folgenden Button klicken:', 'h2-rental-pro') . '</p>';

                $message .= '<div style="background:#FFFFFF;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">';
                $message .= '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;">' . sprintf(esc_html__('Mit Ihrer Bestätigung erhalten Sie regelmäßig aktuelle Informationen von %s zu Produkten und exklusiven Angeboten.', 'h2-rental-pro'), esc_html($site_title)) . '</p>';

                $message .= '<div style="text-align:center;margin:18px 0 8px;">';
                $message .= '<a href="' . esc_url($confirm_url) . '" style="display:inline-block;padding:14px 36px;background:#000;color:#fff;text-decoration:none;border-radius:999px;font-weight:bold;font-size:15px;">' . esc_html__('Anmeldung bestätigen', 'h2-rental-pro') . '</a>';
                $message .= '</div>';
                $message .= '</div>';

                $message .= '<p style="margin:16px 0 8px;font-size:12px;line-height:1.6;color:#666;">' . esc_html__('Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail einfach.', 'h2-rental-pro') . '</p>';
                $message .= '<p style="margin:8px 0 0;font-size:12px;line-height:1.6;color:#999;"><em>' . esc_html__('Dies ist eine Test-E-Mail (Newsletter).', 'h2-rental-pro') . '</em></p>';

                if ($logo_url) {
                    $message .= '<div style="text-align:center;margin:22px 0 8px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="width:70px;max-width:100%;height:auto;"></div>';
                }

                $message .= $divider;

                $footer_html = function_exists('pv_get_email_footer_html') ? pv_get_email_footer_html() : '';
                if ($footer_html) {
                    $message .= $footer_html;
                }

                $message .= '</div>';
                $message .= '</body></html>';

                $headers = ['Content-Type: text/html; charset=UTF-8'];
                $from_name = get_bloginfo('name');
                $from_email = get_option('admin_email');
                $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

                $sent = wp_mail($admin_email, $subject, $message, $headers);
                return $sent ? true : new \WP_Error('send_failed', __('Newsletter Test-E-Mail konnte nicht gesendet werden.', 'h2-rental-pro'));
            }

            default:
                return new \WP_Error('unknown_type', __('Unbekannter Test-E-Mail-Typ.', 'h2-rental-pro'));
        }
    }
}

if (isset($_POST['submit_email_settings']) || isset($_POST['send_test_email'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $footer = [
        'company' => sanitize_text_field($_POST['footer_company'] ?? ''),
        'owner' => sanitize_text_field($_POST['footer_owner'] ?? ''),
        'street' => sanitize_text_field($_POST['footer_street'] ?? ''),
        'postal_city' => sanitize_text_field($_POST['footer_postal_city'] ?? ''),
        'website' => sanitize_text_field($_POST['footer_website'] ?? ''),
        'copyright' => sanitize_text_field($_POST['footer_copyright'] ?? ''),
    ];
    update_option('produkt_email_footer', $footer);

    $invoice = [
        'firma_name' => sanitize_text_field($_POST['firma_name'] ?? ''),
        'firma_strasse' => sanitize_text_field($_POST['firma_strasse'] ?? ''),
        'firma_plz_ort' => sanitize_text_field($_POST['firma_plz_ort'] ?? ''),
        'firma_ust_id' => sanitize_text_field($_POST['firma_ust_id'] ?? ''),
        'firma_email' => sanitize_text_field($_POST['firma_email'] ?? ''),
        'firma_telefon' => sanitize_text_field($_POST['firma_telefon'] ?? ''),
    ];
    update_option('produkt_invoice_sender', $invoice);

    $email_toggle = !empty($_POST['invoice_email_enabled']) ? '1' : '0';
    update_option('produkt_invoice_email_enabled', $email_toggle);

    // Logo für Rechnungen separat speichern
    if (!empty($_POST['firma_logo_url'])) {
        update_option('plugin_firma_logo_url', esc_url_raw($_POST['firma_logo_url']));
    } else {
        delete_option('plugin_firma_logo_url');
    }

    echo '<div class="notice notice-success"><p>✅ ' . esc_html__('Einstellungen gespeichert!', 'h2-rental-pro') . '</p></div>';

    if (isset($_POST['send_test_email'])) {
        $test_type = sanitize_text_field($_POST['test_email_type'] ?? 'customer_order_confirmation');
        $test_result = produkt_send_test_email_by_type($test_type);
        if (true === $test_result) {
            echo '<div class="notice notice-success"><p>✅ ' . esc_html__('Test-E-Mail wurde versendet.', 'h2-rental-pro') . '</p></div>';
        } elseif (is_wp_error($test_result)) {
            echo '<div class="notice notice-error"><p>❌ ' . sprintf(esc_html__('Test-E-Mail konnte nicht gesendet werden: %s', 'h2-rental-pro'), esc_html($test_result->get_error_message())) . '</p></div>';
        }
    }
}

$footer_defaults = [
    'company' => '',
    'owner' => '',
    'street' => '',
    'postal_city' => '',
    'website' => '',
    'copyright' => '',
];
$footer = wp_parse_args((array) get_option('produkt_email_footer', []), $footer_defaults);

$invoice_defaults = [
    'firma_name' => '',
    'firma_strasse' => '',
    'firma_plz_ort' => '',
    'firma_ust_id' => '',
    'firma_email' => '',
    'firma_telefon' => '',
];
$invoice = wp_parse_args((array) get_option('produkt_invoice_sender', []), $invoice_defaults);
$logo_url = get_option('plugin_firma_logo_url', '');
$invoice_email_enabled = get_option('produkt_invoice_email_enabled', '1');
?>
<div class="settings-tab">
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit_email_settings" class="icon-btn email-save-btn"
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
                <div class="card-header-flex">
                    <div>
                        <h2><?php echo esc_html__('Email Footer', 'h2-rental-pro'); ?></h2>
                        <p class="card-subline"><?php echo esc_html__('Absenderinformationen', 'h2-rental-pro'); ?></p>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <select name="test_email_type" style="min-width:260px;">
                            <option value="login_code">
                                <?php echo esc_html__('Login Code versenden', 'h2-rental-pro'); ?></option>
                            <option value="customer_order_confirmation" selected>
                                <?php echo esc_html__('Kunde Bestellbestätigung', 'h2-rental-pro'); ?></option>
                            <option value="admin_order_confirmation">
                                <?php echo esc_html__('Admin Bestellbestätigung', 'h2-rental-pro'); ?></option>
                            <option value="customer_rental_cancellation">
                                <?php echo esc_html__('Kündigungsbestätigung (Kunde)', 'h2-rental-pro'); ?></option>
                            <option value="admin_rental_cancellation">
                                <?php echo esc_html__('Kündigung (Admin)', 'h2-rental-pro'); ?></option>
                            <option value="review_reminder">
                                <?php echo esc_html__('Bewertungs-Erinnerung', 'h2-rental-pro'); ?></option>
                            <option value="low_stock_variant">
                                <?php echo esc_html__('Lagerbestand Niedrig', 'h2-rental-pro'); ?></option>
                            <option value="low_stock_extra">
                                <?php echo esc_html__('Lagerbestand Niedrig (Extras)', 'h2-rental-pro'); ?></option>
                            <option value="newsletter"><?php echo esc_html__('Newsletter', 'h2-rental-pro'); ?></option>
                        </select>
                        <button type="submit" name="send_test_email" value="1"
                            class="button button-secondary"><?php echo esc_html__('Test Email versenden', 'h2-rental-pro'); ?></button>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Firmenname', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_company" value="<?php echo esc_attr($footer['company']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Ansprechpartner / Inhaber', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_owner" value="<?php echo esc_attr($footer['owner']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Straße &amp; Hausnummer', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_street" value="<?php echo esc_attr($footer['street']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('PLZ &amp; Ort', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_postal_city"
                            value="<?php echo esc_attr($footer['postal_city']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Webseite', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_website" value="<?php echo esc_attr($footer['website']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Copyright', 'h2-rental-pro'); ?></label>
                        <input type="text" name="footer_copyright"
                            value="<?php echo esc_attr($footer['copyright']); ?>">
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2><?php echo esc_html__('Daten Rechnungsversand', 'h2-rental-pro'); ?></h2>
                        <p class="card-subline"><?php echo esc_html__('Angaben für Rechnungen', 'h2-rental-pro'); ?></p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="invoice_email_enabled" value="1" <?php checked($invoice_email_enabled, '1'); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span><?php echo esc_html__('Rechnungsversand aktivieren', 'h2-rental-pro'); ?></span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Firmenname', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_name" value="<?php echo esc_attr($invoice['firma_name']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Straße &amp; Hausnummer', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_strasse"
                            value="<?php echo esc_attr($invoice['firma_strasse']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('PLZ &amp; Ort', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_plz_ort"
                            value="<?php echo esc_attr($invoice['firma_plz_ort']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('USt-ID', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_ust_id"
                            value="<?php echo esc_attr($invoice['firma_ust_id']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('E-Mail (optional)', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_email" value="<?php echo esc_attr($invoice['firma_email']); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label><?php echo esc_html__('Telefon (optional)', 'h2-rental-pro'); ?></label>
                        <input type="text" name="firma_telefon"
                            value="<?php echo esc_attr($invoice['firma_telefon']); ?>">
                    </div>
                    <div class="produkt-form-group full-width">
                        <label><?php echo esc_html__('Logo für Rechnung (optional)', 'h2-rental-pro'); ?></label>
                        <div class="image-field-row">
                            <div id="firma_logo_url_preview" class="image-preview">
                                <?php if ($logo_url): ?>
                                    <img src="<?php echo esc_url($logo_url); ?>" alt="">
                                <?php else: ?>
                                    <span><?php echo esc_html__('Noch kein Bild vorhanden', 'h2-rental-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn produkt-media-button" data-target="firma_logo_url"
                                aria-label="<?php echo esc_attr__('Bild auswählen', 'h2-rental-pro'); ?>">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6">
                                    <path
                                        d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z" />
                                </svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="firma_logo_url"
                                aria-label="<?php echo esc_attr__('Bild entfernen', 'h2-rental-pro'); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                    <path
                                        d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z" />
                                    <path
                                        d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z" />
                                </svg>
                            </button>
                        </div>
                        <input type="hidden" name="firma_logo_url" id="firma_logo_url"
                            value="<?php echo esc_attr($logo_url); ?>">
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