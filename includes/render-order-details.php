<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lädt die Auftragsdetails in die Sidebar (für AJAX).
 *
 * Diese Funktion rendert die Datei "sidebar-order-details.php"
 * im Ordner /admin/dashboard/ mit allen notwendigen Variablen.
 *
 * @param int $order_id
 */
function render_order_details($order_id) {
    require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

    $order_data = pv_get_order_by_id($order_id);
    $order = $order_data ? (object) $order_data : null;

    if (!$order) {
        echo '<p>Keine Daten gefunden.</p>';
        return;
    }

    // Daten vorbereiten
    $image_url = pv_get_order_image($order);
    $is_open = ($order->status === 'offen');
    $rental_periods = [];
    if ($is_open) {
        $rental_periods[] = [
            'produkt' => '',
            'start'   => null,
            'end'     => null,
            'percent' => 0,
            'badge'   => 'offen',
        ];
    } else {
        $produkte = $order->produkte ?? [$order];
        $groups = [];
        foreach ($produkte as $p) {
            if (empty($p->start_date) || empty($p->end_date)) {
                continue;
            }
            $key      = $p->start_date . '|' . $p->end_date;
            $start_ts = strtotime($p->start_date);
            $end_ts   = strtotime($p->end_date);
            $today    = time();
            $total    = max(1, $end_ts - $start_ts);
            $elapsed  = min(max(0, $today - $start_ts), $total);
            $percent  = floor(($elapsed / $total) * 100);
            $badge    = 'In Vermietung';
            if ($percent >= 100) {
                $badge = 'Abgeschlossen';
            } elseif ($percent <= 0) {
                $badge = 'Ausstehend';
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'start'    => $p->start_date,
                    'end'      => $p->end_date,
                    'percent'  => $percent,
                    'badge'    => $badge,
                    'products' => [$p->produkt_name ?? ''],
                ];
            } else {
                $groups[$key]['products'][] = $p->produkt_name ?? '';
            }
        }
        foreach ($groups as $g) {
            $name = implode(', ', array_filter($g['products']));
            $rental_periods[] = [
                'produkt' => $name,
                'start'   => $g['start'],
                'end'     => $g['end'],
                'percent' => $g['percent'],
                'badge'   => $g['badge'],
            ];
        }
    }

    // Template einbinden: /admin/dashboard/sidebar-order-details.php
    $template_path = plugin_dir_path(__FILE__) . '../admin/dashboard/sidebar-order-details.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>Template nicht gefunden: sidebar-order-details.php</p>';
    }
}
