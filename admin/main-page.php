<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

// Rückgabe bestätigen (wenn Button gedrückt)
if (isset($_POST['confirm_return_id'])) {
    $order_id = intval($_POST['confirm_return_id']);
    $success = \ProduktVerleih\Database::process_inventory_return($order_id);
    if ($success) {
        echo '<div class="updated"><p>Rückgabe erfolgreich bestätigt.</p></div>';
    } else {
        echo '<div class="error"><p>Fehler beim Bestätigen der Rückgabe.</p></div>';
    }
}

// Umsatz berechnen (aktueller Monat)
$start_date = date('Y-m-01 00:00:00');
$end_date   = date('Y-m-d 23:59:59');
$monthly_income = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(final_price)
     FROM {$wpdb->prefix}produkt_orders
     WHERE status = 'abgeschlossen' AND created_at BETWEEN %s AND %s",
    $start_date,
    $end_date
));
$monthly_income = $monthly_income ? number_format((float) $monthly_income, 2, ',', '.') : '0,00';

// Weitere Zahlen
$products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_categories");
$extras = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_extras");
$variants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_variants");
$customers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_customers");

// Letzte Bestellungen
$orders = $wpdb->get_results("
    SELECT o.*, c.name AS produkt_name
    FROM {$wpdb->prefix}produkt_orders o
    LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 5
");

foreach ($orders as $order_item) {
    if (!empty($order_item->order_items)) {
        $expanded = pv_expand_order_products($order_item);
        if (!empty($expanded)) {
            $names = [];
            foreach ($expanded as $p) {
                $names[] = trim($p->produkt_name . ($p->variant_name ? ' (' . $p->variant_name . ')' : ''));
            }
            $order_item->produkt_name = implode(', ', array_filter($names));
        }
    }
}

// Rückgaben abrufen (fällige Rückgaben, noch nicht bestätigt)
$return_orders = \ProduktVerleih\Database::get_due_returns();
$dashboard_mode = get_option('produkt_betriebsmodus', 'miete');
$is_rental_mode = ($dashboard_mode !== 'kauf');

// Branding holen
$branding_result = $wpdb->get_row("SELECT setting_value FROM {$wpdb->prefix}produkt_branding WHERE setting_key = 'plugin_name'");
$plugin_name = $branding_result ? esc_html($branding_result->setting_value) : 'H2 Rental Pro';
?>

<div class="produkt-admin dashboard-wrapper">

    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Willkommen in Ihrem Dashboard für Mietprodukte.</p>

    <div class="dashboard-grid">
        <!-- Linke Spalte -->
<div class="dashboard-left">

    <div class="dashboard-card card-income">
        <h2>Gesamteinnahmen <?php echo date_i18n('F'); ?></h2>
        <p class="card-subline">Ihre Umsatzübersicht für Mietprodukte</p>
        <p class="income-amount">€ <?php echo $monthly_income; ?></p>
        <small>Zwischen dem 01. – <?php echo date_i18n('d.m.Y'); ?></small>
    </div>

    <?php
    // Freemius SDK sicher laden
    if (!function_exists('fs') && file_exists(plugin_dir_path(__FILE__) . '../vendor/freemius/start.php')) {
        require_once plugin_dir_path(__FILE__) . '../vendor/freemius/start.php';
    }

    $fs = function_exists('hrp_fs') ? hrp_fs() : (function_exists('fs') ? fs() : null);

    // Lizenz prüfen
    $license_status = ($fs && $fs->can_use_premium_code()) ? 'Aktiv' : 'Nicht aktiviert';
    ?>

    <div class="dashboard-card card-company">
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <div style="background: #fff; color: #000; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem;">H2</div>
            <div>
                <h2>Lizenzstatus</h2>
                <p><strong>Status:</strong> <?php if ($license_status === 'Aktiv') : ?><span class="badge status-abgeschlossen"><?php echo esc_html($license_status); ?></span><?php else : ?><?php echo esc_html($license_status); ?><?php endif; ?></p>
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <p><strong>Version:</strong> <?php echo esc_html(PRODUKT_PLUGIN_VERSION); ?></p>
            <p><strong>Support:</strong> <a href="mailto:support@h2concepts.de">support@h2concepts.de</a></p>
            <p><strong>Website:</strong> <a href="https://www.h2concepts.de" target="_blank">www.h2concepts.de</a></p>
        </div>

        <?php if ($fs) : ?>
        <div style="margin-top: 1.5rem;">
            <a href="<?php echo esc_url($fs->get_account_url()); ?>" class="button button-primary license-button">Lizenz verwalten</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rückgaben-Box -->
    <div class="dashboard-card card-returns">
        <h2>Offene Rückgaben</h2>
        <p class="card-subline">Folgende Rückgaben warten auf Bestätigung:</p>

        <?php if (!empty($return_orders)): ?>
            <ul class="return-list">
                <?php foreach ($return_orders as $return): ?>
                    <?php
                        $start_source = $return->start_date ?? $return->created_at ?? '';
                        $start_timestamp = $start_source ? strtotime($start_source) : false;
                        $start_label = $start_timestamp ? date_i18n('d.m.Y', $start_timestamp) : '–';
                        $end_timestamp = !empty($return->end_date) ? strtotime($return->end_date) : false;
                        $end_label = $end_timestamp ? date_i18n('d.m.Y', $end_timestamp) : '–';
                    ?>
                    <li class="return-item">
                        <div>
                            <strong>#<?php echo esc_html($return->order_number ?: $return->id); ?></strong><br>
                            <?php echo esc_html($return->customer_name); ?><br>
                            <?php if (!empty($return->category_name)): ?>
                                Produkt: <?php echo esc_html($return->category_name); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($return->variant_name)): ?>
                                Ausführung: <?php echo esc_html($return->variant_name); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($return->extra_names)): ?>
                                Extras: <?php echo esc_html($return->extra_names); ?><br>
                            <?php endif; ?>
                            <?php if ($is_rental_mode): ?>
                                Mietstart: <?php echo esc_html($start_label); ?>
                            <?php else: ?>
                                Rückgabe am: <?php echo esc_html($end_label); ?>
                            <?php endif; ?>
                        </div>
                        <form method="post" class="return-confirm-form" style="margin-left:auto;">
                            <input type="hidden" name="confirm_return_id" value="<?php echo (int)$return->id; ?>">
                            <button type="submit" class="button button-primary">Rückgabe bestätigen</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin-top: 1rem;">✅ Aktuell stehen keine Rückgaben an.</p>
        <?php endif; ?>
    </div>

</div>

        <!-- Rechte Spalte -->
        <div class="dashboard-right">

            <div class="dashboard-row">
                <div class="dashboard-card card-products">
                    <h2>Produktübersicht</h2>
                    <p class="card-subline">Ihre wichtigsten Produktdaten auf einen Blick</p>
                    <div class="product-info-grid">
                        <div class="product-info-box bg-pastell-orange">
                            <span class="label">Produkte</span>
                            <strong class="value"><?php echo $products; ?></strong>
                        </div>
                        <div class="product-info-box bg-pastell-mint">
                            <span class="label">Extras</span>
                            <strong class="value"><?php echo $extras; ?></strong>
                        </div>
                        <div class="product-info-box bg-pastell-gruen">
                            <span class="label">Ausführungen</span>
                            <strong class="value"><?php echo $variants; ?></strong>
                        </div>
                        <div class="product-info-box bg-pastell-gelb">
                            <span class="label">Kunden</span>
                            <strong class="value"><?php echo $customers; ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Schnellnavigation -->
                <div class="dashboard-card card-quicknav">
                    <h2>Schnellnavigation</h2>
                    <p class="card-subline">Nützliche Direktlinks für die tägliche Arbeit.</p>
                    <div class="quicknav-grid">
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-orders">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">📦</div>
                                    <div class="quicknav-label">Bestellungen</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-customers">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">👤</div>
                                    <div class="quicknav-label">Kunden</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-products">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🛒</div>
                                    <div class="quicknav-label">Produkte</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-settings">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">⚙️</div>
                                    <div class="quicknav-label">Einstellungen</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-categories">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🏷️</div>
                                    <div class="quicknav-label">Kategorien</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-extras">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">✨</div>
                                    <div class="quicknav-label">Extras</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-variants">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Ausführungen</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-calendar">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">📅</div>
                                    <div class="quicknav-label">Kalender</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Letzte Aktivitäten -->
<div class="dashboard-card card-activity">
    <h2>Letzte Aktivitäten</h2>
    <p class="card-subline">Was zuletzt passiert ist</p>
    <table class="activity-table">
        <thead>
            <tr>
                <th>Bestellnr.</th>
                <th>Kunde</th>
                <th>Produkt</th>
                <th>Datum</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo esc_html($order->order_number ?: $order->id); ?></td>
                    <td><?php echo esc_html($order->customer_name); ?></td>
                    <td><?php echo esc_html($order->produkt_name); ?></td>
                    <td><?php echo date_i18n('d.m.Y', strtotime($order->created_at)); ?></td>
                    <td><span class="badge status-<?php echo esc_attr($order->status); ?>"><?php echo ucfirst($order->status); ?></span></td>
                    <td><a href="#" class="view-details-link" data-order-id="<?php echo esc_attr($order->id); ?>">Details ansehen</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
			<!-- Sidebar-Overlay für Auftragsdetails -->
<div id="order-details-sidebar" class="order-details-sidebar">
    <div class="order-details-header">
        <h3>Auftragsdetails</h3>
        <button class="close-sidebar">&times;</button>
    </div>
    <div class="order-details-content">
        <!-- AJAX-Daten werden hier eingefügt -->
        <p>Lade Details…</p>
    </div>
</div>
<div id="order-details-overlay" class="order-details-overlay"></div>

        </div>
    </div>
</div>
