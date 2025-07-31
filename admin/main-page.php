<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

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
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
$monthly_income = $wpdb->get_var("
    SELECT SUM(final_price)
    FROM {$wpdb->prefix}produkt_orders
    WHERE status = 'abgeschlossen' AND created_at BETWEEN '$start_date' AND '$end_date'
");
$monthly_income = $monthly_income ? number_format($monthly_income, 2, ',', '.') : '0,00';

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

// Rückgaben abrufen (fällige Rückgaben, noch nicht bestätigt)
$return_orders = \ProduktVerleih\Database::get_due_returns();

// Branding holen
$branding_result = $wpdb->get_row("SELECT setting_value FROM {$wpdb->prefix}produkt_branding WHERE setting_key = 'plugin_name'");
$plugin_name = $branding_result ? esc_html($branding_result->setting_value) : 'H2 Concepts Rental Pro';
?>

<div class="produkt-admin dashboard-wrapper">

    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Willkommen zu Ihrem Dashboard für Mietprodukte.</p>

    <div class="dashboard-grid">
        <!-- Linke Spalte -->
<div class="dashboard-left">

    <div class="dashboard-card card-income">
        <h2>Gesamteinnahmen <?php echo date_i18n('F'); ?></h2>
        <p class="card-subline">Ihre Umsatzübersicht für Mietprodukte</p>
        <p class="income-amount">€ <?php echo $monthly_income; ?></p>
        <small>Zwischen dem 01. – <?php echo date_i18n('d.m.Y'); ?></small>
    </div>

    <div class="dashboard-card card-company">
        <h2><?php echo $plugin_name; ?></h2>
        <p class="card-subline">Sie brauchen Hilfe? Dann melden Sie sich gerne bei uns</p>
        <p>Support: <a href="mailto:support@h2concepts.de">support@h2concepts.de</a></p>
        <p>Version: <?php echo PRODUKT_PLUGIN_VERSION; ?></p>
        <p>Website: <a href="https://h2concepts.de" target="_blank">www.h2concepts.de</a></p>
    </div>

    <!-- Rückgaben-Box -->
    <div class="dashboard-card card-returns">
        <h2>Offene Rückgaben</h2>
        <p class="card-subline">Folgende Rückgaben warten auf Bestätigung:</p>

        <?php if (!empty($return_orders)): ?>
            <ul class="return-list">
                <?php foreach ($return_orders as $return): ?>
                    <li class="return-item">
                        <div>
                            <strong>#<?php echo esc_html($return->order_number ?: $return->id); ?></strong><br>
                            <?php echo esc_html($return->customer_name); ?><br>
                            <?php echo esc_html($return->category_name); ?><br>
                            Rückgabe am: <?php echo date_i18n('d.m.Y', strtotime($return->end_date)); ?>
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
