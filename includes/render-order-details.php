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
    list($sd, $ed) = pv_get_order_period($order);
    $days = pv_get_order_rental_days($order);

    // Template einbinden: /admin/dashboard/sidebar-order-details.php
    $template_path = plugin_dir_path(__FILE__) . '../admin/dashboard/sidebar-order-details.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>Template nicht gefunden: sidebar-order-details.php</p>';
    }
}
