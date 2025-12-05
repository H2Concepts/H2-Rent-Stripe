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

if (!function_exists('pv_normalize_hex_color_value')) {
    /**
     * Normalize stored hex color strings to a consistent "#rrggbb" format.
     *
     * Accepts values with or without a leading hash and expands shorthand
     * values while falling back to a provided default when sanitization fails.
     *
     * @param string $value   Raw color value from storage/user input.
     * @param string $default Default hex value to use when normalization fails.
     *
     * @return string Normalized hex color including leading hash.
     */
    function pv_normalize_hex_color_value($value, $default = '') {
        if (!is_string($value)) {
            $value = '';
        }

        $value = trim($value);
        if ($value === '') {
            return $default;
        }

        $value = '#' . ltrim($value, '#');
        $sanitized = sanitize_hex_color($value);
        if (!$sanitized) {
            return $default;
        }

        if (strlen($sanitized) === 4) {
            $sanitized = sprintf(
                '#%1$s%1$s%2$s%2$s%3$s%3$s',
                substr($sanitized, 1, 1),
                substr($sanitized, 2, 1),
                substr($sanitized, 3, 1)
            );
        }

        return strtolower($sanitized);
    }
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

function pv_get_category_title_by_id($category_id) {
    global $wpdb;

    if (!$category_id) {
        return '';
    }

    $title = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
            $category_id
        )
    );

    return $title ?: '';
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
 * Determine the normalized rental start date for an order.
 *
 * @param object|array $order Order data
 * @return string Empty string when unavailable otherwise Y-m-d formatted date
 */
function pv_get_order_start_date($order) {
    if (is_array($order)) {
        $order = (object) $order;
    }

    if (empty($order)) {
        return '';
    }

    if (!empty($order->start_date)) {
        return substr($order->start_date, 0, 10);
    }

    list($start,) = pv_get_order_period($order);
    if (!empty($start)) {
        return substr($start, 0, 10);
    }

    if (!empty($order->created_at)) {
        return substr($order->created_at, 0, 10);
    }

    return '';
}

/**
 * Resolve the timestamp when a rental was marked as returned.
 *
 * @param object|array $order Order data
 * @param array|null   $logs  Optional pre-fetched order logs
 * @return int|null Unix timestamp or null when still active
 */
function pv_get_order_return_timestamp($order, $logs = null) {
    if (is_array($order)) {
        $order = (object) $order;
    }

    if (empty($order) || empty($order->id)) {
        return null;
    }

    $logs = is_array($logs) ? $logs : [];

    foreach ($logs as $log) {
        if (!isset($log->event) || $log->event !== 'inventory_returned_accepted') {
            continue;
        }
        if (!empty($log->created_at)) {
            $ts = strtotime($log->created_at);
            if ($ts) {
                return $ts;
            }
        }
    }

    if (!empty($order->inventory_reverted)) {
        global $wpdb;
        $created = $wpdb->get_var($wpdb->prepare(
            "SELECT created_at FROM {$wpdb->prefix}produkt_order_logs WHERE order_id = %d AND event = 'inventory_returned_accepted' ORDER BY created_at DESC LIMIT 1",
            $order->id
        ));
        if ($created) {
            $ts = strtotime($created);
            if ($ts) {
                return $ts;
            }
        }
    }

    return null;
}

/**
 * Build a synthetic payment history for rental orders.
 *
 * @param object|array $order Order data including pricing
 * @param array|null   $logs  Optional order logs
 * @return array{
 *     payments: array<int, array{date:string,amount:float,type:string,note:string,shipping:float}>,
 *     total: float,
 *     log_entries: array,
 *     monthly_amount: float,
 *     shipping_once: float
 * }
 */
function pv_calculate_rental_payments($order, $logs = null) {
    if (is_array($order)) {
        $order = (object) $order;
    }

    $monthly_amount = isset($order->final_price) ? max(0.0, (float) $order->final_price) : 0.0;
    $shipping_cost  = isset($order->shipping_cost) ? max(0.0, (float) $order->shipping_cost) : 0.0;

    $result = [
        'payments'      => [],
        'total'         => 0.0,
        'log_entries'   => [],
        'monthly_amount'=> $monthly_amount,
        'shipping_once' => $shipping_cost,
    ];

    if (empty($order) || empty($order->mode) || empty($order->status)) {
        return $result;
    }

    $mode    = strtolower($order->mode);
    $status  = strtolower($order->status);
    $allowed = ['abgeschlossen', 'gekündigt'];
    if ($mode === 'kauf' || !in_array($status, $allowed, true)) {
        return $result;
    }

    $start_date = pv_get_order_start_date($order);
    if (!$start_date) {
        return $result;
    }

    $timezone = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone(date_default_timezone_get());
    $current  = \DateTimeImmutable::createFromFormat('Y-m-d', $start_date, $timezone);
    if (!$current) {
        return $result;
    }
    $current = $current->setTime(0, 0, 0);

    $now_ts = function_exists('current_time') ? current_time('timestamp') : time();
    $return_ts = pv_get_order_return_timestamp($order, $logs);
    $cutoff_ts = $return_ts ?: $now_ts;
    $cutoff    = (new \DateTimeImmutable('@' . $cutoff_ts))->setTimezone($timezone)->setTime(23, 59, 59);

    if ($current > $cutoff) {
        return $result;
    }

    $monthly_amount = $result['monthly_amount'];
    $shipping_cost  = $result['shipping_once'];
    $iteration      = 0;

    while ($current <= $cutoff) {
        $iteration++;
        if ($iteration > 240) {
            break; // safety guard (~20 years)
        }

        $amount = $monthly_amount;
        $note   = '';
        $label  = 'Monatszahlung verbucht';
        $shipping_portion = 0.0;
        if ($iteration === 1) {
            $amount += $shipping_cost;
            if ($shipping_cost > 0) {
                $note = 'inkl. Versand (einmalig)';
                $shipping_portion = $shipping_cost;
            }
            $label = 'Erste Monatszahlung verbucht';
        }

        if ($amount > 0) {
            $result['payments'][] = [
                'date'  => $current->format('Y-m-d'),
                'amount'=> $amount,
                'type'  => $iteration === 1 ? 'initial' : 'recurring',
                'note'  => $note,
                'shipping' => $shipping_portion,
            ];
            $result['total'] += $amount;

            $message = $label . ': ' . number_format($amount, 2, ',', '.') . ' €';
            if ($note) {
                $message .= ' (' . $note . ')';
            }

            $result['log_entries'][] = (object) [
                'id'           => 'auto_' . ($order->id ?? 0) . '_' . $iteration,
                'order_id'     => $order->id ?? 0,
                'order_number' => $order->order_number ?? '',
                'event'        => 'auto_rental_payment',
                'message'      => $message,
                'created_at'   => $current->format('Y-m-d H:i:s'),
            ];
        }

        $current = $current->add(new \DateInterval('P1M'))->setTime(0, 0, 0);
        if ($current > $cutoff) {
            break;
        }
    }

    return $result;
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
 * Generate and increment the next invoice number.
 *
 * @return string Invoice number or empty string when numbering disabled
 */
function pv_generate_invoice_number() {
    $next = get_option('produkt_next_invoice_number', '');
    if ($next === '') {
        return '';
    }

    if (preg_match('/^(.*?)(\d+)$/', $next, $m)) {
        $prefix   = $m[1];
        $num      = (int) $m[2];
        $len      = strlen($m[2]);
        $next_val = $prefix . str_pad($num + 1, $len, '0', STR_PAD_LEFT);
    } else {
        $num      = (int) $next;
        $next_val = (string) ($num + 1);
    }

    update_option('produkt_next_invoice_number', $next_val);
    update_option('produkt_last_invoice_number', $next);

    return $next;
}

/**
 * Ensure an invoice number exists for the given order.
 *
 * @param array $order
 * @param int   $order_id
 * @return string Invoice number or empty string when not available
 */
function pv_ensure_invoice_number(array $order, int $order_id) {
    if ($order_id <= 0) {
        return $order['invoice_number'] ?? '';
    }

    $invoice_number = $order['invoice_number'] ?? '';
    if ($invoice_number !== '') {
        return $invoice_number;
    }

    $generated = pv_generate_invoice_number();
    if ($generated === '') {
        return '';
    }

    global $wpdb;
    $wpdb->update(
        "{$wpdb->prefix}produkt_orders",
        ['invoice_number' => $generated],
        ['id' => $order_id],
        ['%s'],
        ['%d']
    );

    return $generated;
}

/**
 * Create a provisional order number based on the current date and time.
 *
 * Uses the pattern DDMMYYHHMM so open orders get a unique, time-based
 * identifier that will later be replaced by the final sequential number.
 *
 * @return string
 */
function pv_generate_preliminary_order_number() {
    $timestamp = function_exists('current_time') ? current_time('timestamp') : time();
    return date_i18n('dmyHi', $timestamp);
}

/**
 * Build the email footer HTML from stored settings.
 *
 * @return string
 */
function pv_get_email_footer_html() {
    $footer = get_option('produkt_email_footer', []);
    $lines = [];

    if (!empty($footer['company'])) {
        $lines[] = esc_html($footer['company']);
    }
    if (!empty($footer['owner'])) {
        $lines[] = esc_html($footer['owner']);
    }
    if (!empty($footer['street'])) {
        $lines[] = esc_html($footer['street']);
    }
    if (!empty($footer['postal_city'])) {
        $lines[] = esc_html($footer['postal_city']);
    }

    $website   = !empty($footer['website']) ? esc_url($footer['website']) : '';
    $copyright = !empty($footer['copyright']) ? esc_html($footer['copyright']) : '';

    if (!$lines && !$website && !$copyright) {
        return '';
    }

    $content = '<div style="padding:20px;text-align:center;background:#F6F7FA;color:#000;font-size:12px;line-height:1.6;">';

    if ($lines) {
        $content .= '<div>' . implode('<br>', $lines) . '</div>';
    }

    if ($website) {
        $content .= '<div style="margin-top:8px;"><a href="' . $website . '" style="color:#000;text-decoration:none;">' . esc_html($footer['website']) . '</a></div>';
    }

    if ($copyright) {
        $content .= '<div style="margin-top:8px;">' . $copyright . '</div>';
    }

    $content .= '</div>';

    return $content;
}

/**
 * Determine whether automatic invoice emails are enabled for customers.
 *
 * @return bool
 */
function pv_is_invoice_email_enabled() {
    $value = get_option('produkt_invoice_email_enabled', '1');
    return in_array($value, ['1', 1, true, 'true', 'on'], true);
}

/**
 * Determine the order mode, falling back to database or global defaults when missing.
 *
 * @param array $order    Order payload
 * @param int   $order_id Optional order ID reference
 * @return string Either 'kauf' or 'miete'
 */
function pv_get_order_mode(array $order = [], int $order_id = 0) {
    $mode = $order['mode'] ?? '';

    if (!$mode && $order_id) {
        global $wpdb;
        $mode = $wpdb->get_var($wpdb->prepare(
            "SELECT mode FROM {$wpdb->prefix}produkt_orders WHERE id = %d",
            $order_id
        ));
    }

    if (!$mode) {
        $mode = get_option('produkt_betriebsmodus', 'miete');
    }

    return $mode;
}

/**
 * Decide whether the plugin should send its own invoice email for the given order.
 *
 * @param array $order    Order payload
 * @param int   $order_id Optional order ID reference
 * @return bool
 */
function pv_should_send_invoice_email(array $order = [], int $order_id = 0) {
    if (!pv_is_invoice_email_enabled()) {
        return false;
    }

    return pv_get_order_mode($order, $order_id) === 'kauf';
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

    if (!$row) {
        return null;
    }

    $row['produkte'] = pv_expand_order_products($row);

    return $row;
}

/**
 * Retrieve an order by Stripe session id including expanded products.
 *
 * @param string $session_id
 * @return array|null
 */
function pv_get_order_by_session_id($session_id) {
    global $wpdb;

    $sql = $wpdb->prepare(
        "SELECT o.*, c.name AS category_name,
                COALESCE(v.name, o.produkt_name) AS variant_name,
                COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                sm.name AS shipping_name
         FROM {$wpdb->prefix}produkt_orders o
         LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
         LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
         LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
         LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
            ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
         WHERE o.stripe_session_id = %s
         GROUP BY o.id
         ORDER BY o.id DESC LIMIT 1",
        $session_id
    );

    $row = $wpdb->get_row($sql, ARRAY_A);
    if (!$row) {
        return null;
    }

    $row['produkte'] = pv_expand_order_products($row);
    return $row;
}

/**
 * Expand serialized order items into product objects with resolved names and images.
 *
 * @param array|object $row Order row data
 * @return array
 */
function pv_expand_order_products($row) {
    global $wpdb;

    $row_arr = is_object($row) ? (array) $row : (array) $row;

    $items = [];
    if (!empty($row_arr['order_items'])) {
        $decoded = json_decode($row_arr['order_items'], true);
        if (is_array($decoded)) {
            $items = $decoded;
        }
    }

    if (empty($items)) {
        $items[] = [
            'category_id'      => $row_arr['category_id'] ?? 0,
            'variant_id'       => $row_arr['variant_id'] ?? 0,
            'extra_ids'        => $row_arr['extra_ids'] ?? '',
            'duration_id'      => $row_arr['duration_id'] ?? 0,
            'condition_id'     => $row_arr['condition_id'] ?? 0,
            'product_color_id' => $row_arr['product_color_id'] ?? 0,
            'frame_color_id'   => $row_arr['frame_color_id'] ?? 0,
            'final_price'      => $row_arr['final_price'] ?? 0,
            'start_date'       => $row_arr['start_date'] ?? null,
            'end_date'         => $row_arr['end_date'] ?? null,
            'weekend_tariff'   => $row_arr['weekend_tariff'] ?? 0,
            'metadata'         => [
                'produkt'      => $row_arr['produkt_name'] ?? '',
                'extra'        => $row_arr['extra_text'] ?? '',
                'dauer_name'   => $row_arr['dauer_text'] ?? '',
                'zustand'      => $row_arr['zustand_text'] ?? '',
                'produktfarbe' => $row_arr['produktfarbe_text'] ?? '',
                'gestellfarbe' => $row_arr['gestellfarbe_text'] ?? '',
            ],
        ];
    }

    $variant_ids   = [];
    $category_ids  = [];
    $duration_ids  = [];
    $condition_ids = [];
    $color_ids     = [];
    $frame_ids     = [];
    $extra_ids_all = [];

    foreach ($items as $itm) {
        $variant_ids[]   = intval($itm['variant_id'] ?? 0);
        $category_ids[]  = intval($itm['category_id'] ?? ($row_arr['category_id'] ?? 0));
        $duration_ids[]  = intval($itm['duration_id'] ?? 0);
        $condition_ids[] = intval($itm['condition_id'] ?? 0);
        $color_ids[]     = intval($itm['product_color_id'] ?? 0);
        $frame_ids[]     = intval($itm['frame_color_id'] ?? 0);

        $extra_raw = $itm['extra_ids'] ?? '';
        if (is_array($extra_raw)) {
            $extra_ids_all = array_merge($extra_ids_all, array_map('intval', $extra_raw));
        } elseif (!empty($extra_raw)) {
            $extra_ids_all = array_merge($extra_ids_all, array_map('intval', explode(',', $extra_raw)));
        }
    }

    $variant_map   = pv_get_name_map($wpdb->prefix . 'produkt_variants', $variant_ids);
    $category_map  = pv_get_name_map($wpdb->prefix . 'produkt_categories', $category_ids);
    $duration_map  = pv_get_name_map($wpdb->prefix . 'produkt_durations', $duration_ids);
    $condition_map = pv_get_name_map($wpdb->prefix . 'produkt_conditions', $condition_ids);
    $color_map     = pv_get_name_map($wpdb->prefix . 'produkt_colors', $color_ids);
    $frame_map     = pv_get_name_map($wpdb->prefix . 'produkt_colors', $frame_ids);
    $extras_map    = pv_get_name_map($wpdb->prefix . 'produkt_extras', $extra_ids_all);

    $produkte = [];
    foreach ($items as $itm) {
        $extra_raw = $itm['extra_ids'] ?? '';
        $extra_ids = [];
        if (is_array($extra_raw)) {
            $extra_ids = array_filter(array_map('intval', $extra_raw));
        } elseif (!empty($extra_raw)) {
            $extra_ids = array_filter(array_map('intval', explode(',', $extra_raw)));
        }

        $extra_names = [];
        foreach ($extra_ids as $exid) {
            if (isset($extras_map[$exid])) {
                $extra_names[] = $extras_map[$exid];
            }
        }

        $variant_id  = intval($itm['variant_id'] ?? 0);
        $category_id = intval($itm['category_id'] ?? $row_arr['category_id'] ?? 0);

        $category_name = '';
        if ($category_id && isset($category_map[$category_id])) {
            $category_name = $category_map[$category_id];
        } elseif (!empty($itm['metadata']['produkt'])) {
            $category_name = $itm['metadata']['produkt'];
        } elseif (!empty($row_arr['category_name'])) {
            $category_name = $row_arr['category_name'];
        } elseif (!empty($row_arr['produkt_name'])) {
            $category_name = $row_arr['produkt_name'];
        }

        $produkte[] = (object) [
            'produkt_name'       => $category_name,
            'variant_name'       => $variant_map[$variant_id] ?? ($row_arr['variant_name'] ?? ''),
            'extra_names'        => implode(', ', $extra_names),
            'duration_name'      => $duration_map[intval($itm['duration_id'] ?? 0)] ?? ($itm['metadata']['dauer_name'] ?? $row_arr['duration_name'] ?? ''),
            'condition_name'     => $condition_map[intval($itm['condition_id'] ?? 0)] ?? ($itm['metadata']['zustand'] ?? ''),
            'product_color_name' => $color_map[intval($itm['product_color_id'] ?? 0)] ?? ($itm['metadata']['produktfarbe'] ?? ''),
            'frame_color_name'   => $frame_map[intval($itm['frame_color_id'] ?? 0)] ?? ($itm['metadata']['gestellfarbe'] ?? ''),
            'weekend_tariff'     => !empty($itm['weekend_tariff']) ? 1 : 0,
            'final_price'        => floatval($itm['final_price'] ?? 0),
            'start_date'         => $itm['start_date'] ?? ($row_arr['start_date'] ?? null),
            'end_date'           => $itm['end_date'] ?? ($row_arr['end_date'] ?? null),
            'image_url'          => pv_get_image_url_by_variant_or_category($variant_id, $category_id),
            'category_id'        => $category_id,
            'duration_id'        => intval($itm['duration_id'] ?? 0),
        ];
    }

    return $produkte;
}

function pv_get_name_map($table, $ids) {
    global $wpdb;

    $ids = array_values(array_filter(array_map('intval', (array) $ids)));
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, name FROM {$table} WHERE id IN ($placeholders)",
            ...$ids
        )
    );

    $map = [];
    foreach ($rows as $row) {
        $map[intval($row->id)] = $row->name;
    }

    return $map;
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

    $invoice_number = pv_ensure_invoice_number($order, $order_id);
    $invoice_display = $invoice_number ?: ($order['order_number'] ?: ('RE-' . $order_id));

    // 2. PDF-API-Endpunkt + Key
    $endpoint = 'https://h2concepts.de/tools/generate-invoice.php?key=h2c_92DF!kf392AzJxLP0sQRX';

    // 3. Daten aufbauen
    $sender    = pv_get_invoice_sender();
    $logo_url = get_option('plugin_firma_logo_url', '');
    $product    = $order['produkt_name'];
    if (!$product) {
        $product = $order['variant_name'] ?? '';
    }
    if (!empty($order['product_color_name'])) {
        $product .= ' – ' . $order['product_color_name'];
    }
    if (!empty($order['frame_color_name'])) {
        $product .= ' – ' . $order['frame_color_name'];
    }

    $customer_name = trim($order['customer_name']);
    $customer_addr = trim($order['customer_street'] . ', ' . $order['customer_postal'] . ' ' . $order['customer_city']);

    $post_data = [
        'rechnungsnummer'  => $invoice_display,
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

    // 5. Artikel hinzufügen (Produkt + Extras + Versand)
    $i = 1;

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

    // Hauptprodukt
    $main_total = floatval($order['final_price']);
    foreach ($extras as $ex) {
        $p_field = ($order['mode'] === 'kauf') ? ($ex->price_sale ?? $ex->price) : ($ex->price_rent ?? $ex->price);
        $extras_total += floatval($p_field) * $tage;
    }
    $main_total -= $extras_total;
    if ($main_total < 0) { $main_total = 0; }

    $unit_price = $tage ? round($main_total / $tage, 2) : 0;
    $post_data["artikel_{$i}_name"]  = $product;
    $post_data["artikel_{$i}_menge"] = $tage;
    $post_data["artikel_{$i}_preis"] = $unit_price;
    $i++;

    // Extras
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

    // Versandkosten als eigener Artikel
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

    $filename = 'rechnung-' . sanitize_title_with_dashes($invoice_display) . '.pdf';
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
