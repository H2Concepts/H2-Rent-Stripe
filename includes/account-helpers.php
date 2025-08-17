<?php
if (!defined('ABSPATH')) { exit; }

function pv_get_variant_image_url($variant_id) {
    $img_id = get_post_meta($variant_id, 'produkt_variant_image_id', true);
    return $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
}

function pv_format_subscription_period($start, $end) {
    return date('d.m.Y', strtotime($start)) . ' – ' . date('d.m.Y', strtotime($end));
}

function pv_get_subscription_status_badge($status) {
    switch ($status) {
        case 'active':
        case 'trialing':
            $class = 'active';
            $label = 'Aktiv';
            break;
        case 'canceled':
        case 'cancelled':
            $class = 'cancelled';
            $label = 'Gekündigt';
            break;
        default:
            $class = 'scheduled';
            $label = ucfirst($status);
            break;
    }

    return '<span class="status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}

function pv_get_minimum_duration_months($order) {
    global $wpdb;
    if ($order && !empty($order->duration_id)) {
        $months = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT months_minimum FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                $order->duration_id
            )
        );
        if ($months) {
            return $months;
        }
    }
    return 3;
}

/**
 * Retrieve the best image URL for a subscription's variant or category.
 *
 * @param int $variant_id  Variant ID from the order.
 * @param int $category_id Category ID from the order.
 * @return string Image URL or empty string when none found.
 */
function pv_get_image_url_by_variant_or_category($variant_id, $category_id) {
    global $wpdb;

    $image_url = '';
    if ($variant_id) {
        $image_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT image_url_1 FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $variant_id
            )
        );
    }

    if (empty($image_url) && $category_id) {
        $image_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT default_image FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                $category_id
            )
        );
    }

    return $image_url ?: '';
}

/**
 * Determine the start and end dates for an order.
 * Falls back to parsing the dauer_text when the explicit
 * date columns are empty.
 *
 * @param object $order Order row object
 * @return array{0:?string,1:?string} ISO dates or null when unavailable
 */
function pv_get_order_period($order) {
    $start = '';
    $end   = '';
    if (!empty($order->start_date) && !empty($order->end_date)) {
        $start = $order->start_date;
        $end   = $order->end_date;
    } elseif (!empty($order->dauer_text)) {
        if (preg_match('/(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})/', $order->dauer_text, $m)) {
            $start = $m[1];
            $end   = $m[2];
        } elseif (preg_match('/(\d{2}\.\d{2}\.\d{4})\s*-\s*(\d{2}\.\d{2}\.\d{4})/', $order->dauer_text, $m)) {
            $d1 = DateTime::createFromFormat('d.m.Y', $m[1]);
            $d2 = DateTime::createFromFormat('d.m.Y', $m[2]);
            if ($d1 && $d2) {
                $start = $d1->format('Y-m-d');
                $end   = $d2->format('Y-m-d');
            }
        }
    }

    if ($start && $end) {
        return [$start, $end];
    }

    return [null, null];
}

/**
 * Calculate the number of rental days between two dates (inclusive).
 *
 * @param string $start ISO start date.
 * @param string $end   ISO end date.
 * @return int|null Rental days or null when invalid.
 */
function pv_calc_rental_days($start, $end) {
    if ($start && $end) {
        $s = new DateTime($start);
        $e = new DateTime($end);
        return $e->diff($s)->days + 1;
    }
    return null;
}

/**
 * Determine an order's rental days from its start and end date.
 *
 * @param object $order Order row object
 * @return int|null Number of days or null when unavailable
 */
function pv_get_order_rental_days($order) {
    list($s, $e) = pv_get_order_period($order);
    return pv_calc_rental_days($s, $e);
}

/**
 * Generate and increment the next order number.
 *
 * @return string Order number or empty string when numbering disabled
 */
function pv_generate_order_number() {
    $next = get_option('produkt_next_order_number', '');
    if ($next === '') {
        return '';
    }
    if (preg_match('/^(.*?)(\d+)$/', $next, $m)) {
        $prefix = $m[1];
        $num    = (int) $m[2];
        $len    = strlen($m[2]);
        $next_val = $prefix . str_pad($num + 1, $len, '0', STR_PAD_LEFT);
    } else {
        $num = (int) $next;
        $next_val = (string) ($num + 1);
    }

    update_option('produkt_next_order_number', $next_val);
    update_option('produkt_last_order_number', $next);

    return $next;
}

/**
 * Build the email footer HTML from stored settings.
 *
 * @return string
 */
function pv_get_email_footer_html() {
    $footer = get_option('produkt_email_footer', []);
    $parts = [];
    if (!empty($footer['company'])) {
        $parts[] = esc_html($footer['company']);
    }
    if (!empty($footer['owner'])) {
        $parts[] = esc_html($footer['owner']);
    }
    if (!empty($footer['street'])) {
        $parts[] = esc_html($footer['street']);
    }
    if (!empty($footer['postal_city'])) {
        $parts[] = esc_html($footer['postal_city']);
    }
    if (!$parts) {
        return '';
    }
    return '<div style="background:#f8f9fa;color:#555;padding:20px;text-align:center;font-size:12px;">'
        . implode('<br>', $parts) . '</div>';
}

/**
 * Retrieve invoice sender data.
 *
 * @return array
 */
function pv_get_invoice_sender() {
    $defaults = [
        'firma_name'    => '',
        'firma_strasse' => '',
        'firma_plz_ort' => '',
        'firma_ust_id'  => '',
        'firma_email'   => '',
        'firma_telefon' => '',
    ];

    $data = get_option('produkt_invoice_sender', []);
    return wp_parse_args($data, $defaults);
}

/**
 * Retrieve a single order with related names for invoice generation.
 *
 * @param int $order_id Order ID
 * @return array|null Order data as associative array or null when not found
 */
function pv_get_order_by_id($order_id) {
    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT o.*, c.name AS category_name,
                COALESCE(v.name, o.produkt_name) AS variant_name,
                COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                COALESCE(d.name, o.dauer_text) AS duration_name,
                COALESCE(cond.name, o.zustand_text) AS condition_name,
                COALESCE(pc.name, o.produktfarbe_text) AS product_color_name,
                COALESCE(fc.name, o.gestellfarbe_text) AS frame_color_name,
                sm.name AS shipping_name,
                sm.service_provider AS shipping_provider
         FROM {$wpdb->prefix}produkt_orders o
         LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
         LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
         LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
         LEFT JOIN {$wpdb->prefix}produkt_durations d ON o.duration_id = d.id
         LEFT JOIN {$wpdb->prefix}produkt_conditions cond ON o.condition_id = cond.id
         LEFT JOIN {$wpdb->prefix}produkt_colors pc ON o.product_color_id = pc.id
         LEFT JOIN {$wpdb->prefix}produkt_colors fc ON o.frame_color_id = fc.id
         LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
            ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
         WHERE o.id = %d
         GROUP BY o.id",
        $order_id
    );

    $row = $wpdb->get_row($sql, ARRAY_A);
    if ($row && !empty($row['client_info'])) {
        $ci = json_decode($row['client_info'], true);
        if (!empty($ci['cart_items']) && is_array($ci['cart_items'])) {
            $row['produkte'] = [];
            foreach ($ci['cart_items'] as $item) {
                $meta = $item['metadata'] ?? [];
                $row['produkte'][] = (object) [
                    'produkt_name'      => $meta['produkt'] ?? '',
                    'extra_names'       => $meta['extra'] ?? '',
                    'produktfarbe_text' => $meta['produktfarbe'] ?? '',
                    'gestellfarbe_text' => $meta['gestellfarbe'] ?? '',
                    'zustand_text'      => $meta['zustand'] ?? '',
                    'dauer_text'        => $meta['dauer_name'] ?? '',
                    'final_price'       => $item['final_price'] ?? 0,
                    'weekend_tariff'    => $item['weekend_tariff'] ?? 0,
                    'start_date'        => $item['start_date'] ?? null,
                    'end_date'          => $item['end_date'] ?? null,
                    'days'              => $item['days'] ?? 0,
                    'price_id'          => $item['price_id'] ?? '',
                ];
            }
            if (empty($row['produkt_name']) && !empty($row['produkte'][0]->produkt_name)) {
                $row['produkt_name'] = $row['produkte'][0]->produkt_name;
            }
        }
    }
    return $row ?: null;
}

/**
 * Generate an invoice PDF for an order using the external API.
 *
 * @param int $order_id Order ID
 * @return string|false Path to the generated PDF or false on failure
 */
function pv_generate_invoice_pdf($order_id) {
    // 1. Bestelldaten auslesen
    $order = pv_get_order_by_id($order_id);
    if (!$order) {
        return false;
    }

    // 2. PDF-API-Endpunkt + Key
    $endpoint = 'https://h2concepts.de/tools/generate-invoice.php?key=h2c_92DF!kf392AzJxLP0sQRX';

    // 3. Daten aufbauen
    $sender    = pv_get_invoice_sender();
    $logo_url = get_option('plugin_firma_logo_url', '');
    $product    = $order['produkt_name'];
    if (!$product) {
        $product = $order['variant_name'] ?? '';
    }

    $customer_name = trim($order['customer_name']);
    $customer_addr = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city']);

    $post_data = [
        'bestellnummer'    => (!empty($order['order_number']) ? $order['order_number'] : $order_id),
        'rechnungsnummer'  => ($order['order_number'] ?: ('RE-' . $order_id)),
        'rechnungsdatum'   => date('Y-m-d'),
        'kunde_name'       => $customer_name ?: 'Kunde',
        'kunde_adresse'    => $customer_addr,

        // Firma (aus Einstellungen)
        'firma_name'       => $sender['firma_name'],
        'firma_strasse'    => $sender['firma_strasse'],
        'firma_plz_ort'    => $sender['firma_plz_ort'],
        'firma_ustid'      => $sender['firma_ust_id'],
        'firma_email'      => $sender['firma_email'],
        'firma_telefon'    => $sender['firma_telefon'],
        'firma_logo_url'   => $logo_url,
    ];

    // 4. Mietdauer bestimmen
    $tage = pv_get_order_rental_days((object) $order);
    if (!$tage || $tage < 1) {
        $tage = 1;
    }
    $post_data['dauer'] = $tage;

    $cart_items = [];
    if (!empty($order['client_info'])) {
        $ci = json_decode($order['client_info'], true);
        if (!empty($ci['cart_items']) && is_array($ci['cart_items'])) {
            $cart_items = $ci['cart_items'];
        }
    }

    // 5. Artikel hinzufügen (Produkt(e) + Versand)
    $i = 1;
    if ($cart_items) {
        foreach ($cart_items as $item) {
            $meta = $item['metadata'] ?? [];
            $tage = max(1, intval($item['days'] ?? 1));
            $start = !empty($item['start_date']) ? date_i18n('d.m.Y', strtotime($item['start_date'])) : '';
            $end   = !empty($item['end_date']) ? date_i18n('d.m.Y', strtotime($item['end_date'])) : '';
            $details = [];
            if ($start && $end) {
                $details[] = $start . ' - ' . $end . " ({$tage} Tage)";
            } elseif (!empty($meta['dauer_name'])) {
                $details[] = $meta['dauer_name'] . " ({$tage} Tage)";
            }
            if (!empty($meta['produktfarbe'])) {
                $details[] = 'Farbe: ' . $meta['produktfarbe'];
            }
            if (!empty($meta['gestellfarbe'])) {
                $details[] = 'Gestellfarbe: ' . $meta['gestellfarbe'];
            }
            if (!empty($meta['extra'])) {
                $details[] = 'Extras: ' . $meta['extra'];
            }
            $name = $meta['produkt'] ?? 'Produkt';
            if ($details) {
                $name .= "\n" . implode("\n", $details);
            }

            $unit_price = 0.0;
            if (!empty($item['price_id'])) {
                $amt = \ProduktVerleih\StripeService::get_price_amount($item['price_id']);
                $unit_price = is_wp_error($amt) ? 0.0 : floatval($amt);
            } elseif (!empty($item['final_price'])) {
                $unit_price = round(floatval($item['final_price']) / $tage, 2);
            }

            $post_data["artikel_{$i}_name"]  = $name;
            $post_data["artikel_{$i}_menge"] = $tage;
            $post_data["artikel_{$i}_preis"] = $unit_price;
            $i++;

            if (!empty($item['extra_ids'])) {
                global $wpdb;
                $eids = array_filter(array_map('intval', explode(',', $item['extra_ids'])));
                if ($eids) {
                    $placeholders = implode(',', array_fill(0, count($eids), '%d'));
                    $rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT name, price_rent, price_sale, price FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                            ...$eids
                        )
                    );
                    foreach ($rows as $ex) {
                        $price_val = ($order['mode'] === 'kauf') ? ($ex->price_sale ?? $ex->price) : ($ex->price_rent ?? $ex->price);
                        $post_data["artikel_{$i}_name"]  = $ex->name;
                        $post_data["artikel_{$i}_menge"] = $tage;
                        $post_data["artikel_{$i}_preis"] = floatval($price_val);
                        $i++;
                    }
                }
            }
        }
    } else {
        $extras_total = 0.0;
        $extras = [];
        if (!empty($order['extra_ids'])) {
            global $wpdb;
            $ids = array_filter(array_map('intval', explode(',', $order['extra_ids'])));
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                $extras = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, name, price_rent, price_sale, price FROM {$wpdb->prefix}produkt_extras WHERE id IN ($placeholders)",
                        ...$ids
                    )
                );
            }
        }

        $main_total = floatval($order['final_price']);
        foreach ($extras as $ex) {
            $p_field = ($order['mode'] === 'kauf') ? ($ex->price_sale ?? $ex->price) : ($ex->price_rent ?? $ex->price);
            $extras_total += floatval($p_field) * $tage;
        }
        $main_total -= $extras_total;
        if ($main_total < 0) { $main_total = 0; }

        $unit_price = $tage ? round($main_total / $tage, 2) : 0;
        $detail_lines = [];
        if (!empty($order['start_date']) && !empty($order['end_date'])) {
            $detail_lines[] = date_i18n('d.m.Y', strtotime($order['start_date'])) . ' - ' . date_i18n('d.m.Y', strtotime($order['end_date'])) . " ({$tage} Tage)";
        } elseif (!empty($order['duration_name'])) {
            $detail_lines[] = $order['duration_name'] . " ({$tage} Tage)";
        }
        if (!empty($order['product_color_name'])) {
            $detail_lines[] = 'Farbe: ' . $order['product_color_name'];
        }
        if (!empty($order['frame_color_name'])) {
            $detail_lines[] = 'Gestellfarbe: ' . $order['frame_color_name'];
        }
        $name = $product;
        if ($detail_lines) {
            $name .= "\n" . implode("\n", $detail_lines);
        }
        $post_data["artikel_{$i}_name"]  = $name;
        $post_data["artikel_{$i}_menge"] = $tage;
        $post_data["artikel_{$i}_preis"] = $unit_price;
        $i++;

        if ($extras) {
            foreach ($extras as $ex) {
                $price_val = ($order['mode'] === 'kauf') ? ($ex->price_sale ?? $ex->price) : ($ex->price_rent ?? $ex->price);
                $post_data["artikel_{$i}_name"]  = $ex->name;
                $post_data["artikel_{$i}_menge"] = $tage;
                $post_data["artikel_{$i}_preis"] = floatval($price_val);
                $i++;
            }
        } elseif (!empty($order['extra_names'])) {
            foreach (explode(', ', $order['extra_names']) as $extra_name) {
                $post_data["artikel_{$i}_name"]  = $extra_name;
                $post_data["artikel_{$i}_menge"] = $tage;
                $post_data["artikel_{$i}_preis"] = 0;
                $i++;
            }
        }
    }

    if (!empty($order['shipping_cost']) && floatval($order['shipping_cost']) > 0) {
        $shipping_name = $order['shipping_name'] ?: 'Versand';
        $post_data["artikel_{$i}_name"]  = $shipping_name;
        $post_data["artikel_{$i}_menge"] = 1;
        $post_data["artikel_{$i}_preis"] = floatval($order['shipping_cost']);
        $i++;
    }

    $url     = $endpoint;
    $payload = $post_data;

    // 5. HTTP-Request an API
    $response = wp_remote_post($url, [
        'timeout' => 15,
        'body'    => $payload,
    ]);

    $response_code = wp_remote_retrieve_response_code($response);
    $pdf_data      = wp_remote_retrieve_body($response);

    // Prüfen ob PDF-Daten plausibel sind (größer als 1000 Byte und kein HTML)
    if (
        $response_code !== 200 ||
        strlen($pdf_data) < 1000 ||
        stripos($pdf_data, '<html') !== false
    ) {
        return false;
    }

    // PDF lokal speichern in eigenem Unterordner
    $upload_dir = wp_upload_dir();
    $subdir     = trailingslashit($upload_dir['basedir']) . 'rechnungen-h2-rental-pro/';
    if (!file_exists($subdir)) {
        wp_mkdir_p($subdir);
    }

    $filename = 'rechnung-' . $order_id . '.pdf';
    $path     = $subdir . $filename;
    file_put_contents($path, $pdf_data);

    // URL speichern
    global $wpdb;
    $invoice_url = trailingslashit($upload_dir['baseurl']) . 'rechnungen-h2-rental-pro/' . $filename;
    $wpdb->update(
        "{$wpdb->prefix}produkt_orders",
        ['invoice_url' => $invoice_url],
        ['id' => $order_id],
        ['%s'],
        ['%d']
    );

    return $path;
}

/**
 * Return a German greeting based on the current time of day.
 *
 * @return string Greeting like "Guten Morgen" or "Guten Abend".
 */
function pv_get_time_greeting() {
    $hour = (int) current_time('H');

    if ($hour >= 6 && $hour < 12) {
        return 'Guten Morgen';
    }

    if ($hour >= 12 && $hour < 19) {
        return 'Hallo';
    }

    return 'Guten Abend';
}
