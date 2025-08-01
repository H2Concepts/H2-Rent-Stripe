
<?php
if (!defined('ABSPATH')) {
    exit;
}

// Display notices
if (!empty($notice)) {
    if ($notice === 'deleted') {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Bestellung erfolgreich gelöscht!</p></div>';
    } elseif ($notice === 'bulk_deleted') {
        echo '<div class="notice notice-success is-dismissible"><p>✅ Ausgewählte Bestellungen gelöscht!</p></div>';
    } elseif ($notice === 'error') {
        echo '<div class="notice notice-error is-dismissible"><p>❌ Fehler beim Löschen der Bestellung.</p></div>';
    }
}

// Branding colors
global $wpdb;
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
$branding = [];
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
$primary_color = $branding['admin_color_primary'] ?? '#5f7f5f';

// Search term (not yet used in query but kept for UI consistency)
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Bestellungen verwalten</p>

    <div class="h2-rental-card">
        <h2>Statistik</h2>
        <p class="card-subline">Kennzahlen zum gewählten Zeitraum</p>
        <div class="product-info-grid cols-4">
            <div class="product-info-box bg-pastell-orange">
                <span class="label">Gesamt-Umsatz</span>
                <strong class="value orders-stat-value">€ <?php echo number_format($total_revenue, 2, ',', '.'); ?></strong>
            </div>
            <div class="product-info-box bg-pastell-mint">
                <span class="label">Durchschnitt</span>
                <strong class="value orders-stat-value">€ <?php echo number_format($avg_order_value, 2, ',', '.'); ?></strong>
            </div>
            <div class="product-info-box bg-pastell-gruen">
                <span class="label">Zeitraum</span>
                <strong class="value orders-stat-value"><?php echo date('d.m.', strtotime($date_from)); ?>–<?php echo date('d.m.', strtotime($date_to)); ?></strong>
            </div>
            <div class="product-info-box bg-pastell-gelb">
                <span class="label">Bestellungen</span>
                <strong class="value orders-stat-value"><?php echo intval($total_orders); ?></strong>
            </div>
        </div>
    </div>

    <div class="h2-rental-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Bestellübersicht</h2>
                        <p class="card-subline">Kundenaufträge ansehen</p>
                    </div>
                    <form method="get" class="produkt-filter-form product-search-bar">
                        <input type="hidden" name="page" value="produkt-orders">
                        <div class="search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon">
                                <path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/>
                            </svg>
                            <input type="text" name="s" placeholder="Suchen" value="<?php echo esc_attr($search_term); ?>">
                        </div>
                        <select name="category">
                            <option value="0" <?php selected($selected_category, 0); ?>>Alle Produkte</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                        <button type="submit" class="icon-btn filter-submit-btn" aria-label="Filtern">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                                <path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/>
                            </svg>
                        </button>
                    </form>
                </div>
    
    <!-- Orders Table -->
    <div class="orders-table-container">
        <?php if (empty($orders)): ?>
        <div class="orders-empty">
            <p class="orders-empty-message">Keine Bestellungen im gewählten Zeitraum gefunden.</p>
            <p>Versuchen Sie einen anderen Zeitraum oder eine andere Produkt.</p>
        </div>
        <?php else: ?>
        
        <table class="activity-table">
                <thead>
                    <tr>
                        <th class="col-checkbox"><input type="checkbox" id="select-all-orders"></th>
                        <th class="col-id">ID</th>
                        <th class="col-date">Datum</th>
                        <th>Kunde</th>
                        <th>Telefon</th>
                        <th>Versandadresse</th>
                        <th>Rechnungsadresse</th>
                        <th class="col-type">Produkttyp</th>
                        <th class="col-price">Preis</th>
                        <th class="col-discount">Rabatt</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><input type="checkbox" class="order-checkbox" value="<?php echo $order->id; ?>"></td>
                        <td><strong>#<?php echo !empty($order->order_number) ? $order->order_number : $order->id; ?></strong></td>
                        <td>
                            <?php echo date('d.m.Y', strtotime($order->created_at)); ?><br>
                            <small class="text-gray"><?php echo date('H:i', strtotime($order->created_at)); ?> Uhr</small>
                        </td>
                        <td>
                            <?php if (!empty($order->customer_name)): ?>
                                <strong><?php echo esc_html($order->customer_name); ?></strong><br>
                            <?php endif; ?>
                            <?php if (!empty($order->customer_email)): ?>
                                <a href="mailto:<?php echo esc_attr($order->customer_email); ?>"><?php echo esc_html($order->customer_email); ?></a><br>
                            <?php endif; ?>
                            <small class="text-gray">IP: <?php echo esc_html($order->user_ip); ?></small>
                        </td>
                        <td>
                            <?php echo esc_html($order->customer_phone); ?>
                        </td>
                        <td>
                            <?php
                                $addr = trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city);
                                if ($addr || $order->customer_country) {
                                    echo esc_html(trim($addr . ', ' . $order->customer_country));
                                }
                            ?>
                        </td>
                        <td>
                            <?php
                                $addr = trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city);
                                if ($addr || $order->customer_country) {
                                    echo esc_html(trim($addr . ', ' . $order->customer_country));
                                }
                            ?>
                        </td>
                        <?php
                            $type = 'Verkauf';
                            if (isset($order->mode)) {
                                $type = ($order->mode === 'kauf') ? 'Verkauf' : 'Miete';
                            } elseif (!empty($order->stripe_subscription_id)) {
                                $type = 'Miete';
                            }
                        ?>
                        <td><?php echo esc_html($type); ?></td>
                        <td>
                            <strong class="order-price">
                                <?php echo number_format($order->final_price, 2, ',', '.'); ?>€
                            </strong><br>
                            <?php if ($type !== 'Verkauf'): ?>
                                <small class="text-gray">/Monat</small>
                            <?php endif; ?>
                            <?php if ($order->shipping_cost > 0): ?>
                                <br><span class="text-gray">+ <?php echo number_format($order->shipping_cost, 2, ',', '.'); ?>€ einmalig</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($order->discount_amount > 0): ?>
                                <span class="text-blue">-<?php echo number_format($order->discount_amount, 2, ',', '.'); ?>€</span>
                            <?php else: ?>
                                <span class="text-gray">–</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($order->status === 'offen'): ?>
                                <span class="badge badge-warning">Offen</span>
                            <?php elseif ($order->status === 'gekündigt'): ?>
                                <span class="badge badge-danger">Gekündigt</span>
                            <?php else: ?>
                                <span class="badge badge-success">Abgeschlossen</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="showOrderDetails(<?php echo $order->id; ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                    <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                    <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                </svg>
                            </button>
                            <?php
                                $due = ($order->mode === 'kauf' && $order->end_date && $order->inventory_reverted == 0 && $order->end_date <= current_time('Y-m-d'));
                                if ($due):
                            ?>
                                <button type="button" class="icon-btn produkt-return-confirm" data-id="<?php echo $order->id; ?>" aria-label="Bestätigung">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                                        <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                                        <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
                                    </svg>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-orders&category=<?php echo $selected_category; ?>&delete_order=<?php echo $order->id; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>';}" aria-label="Löschen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                    <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                    <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php endif; ?>
    </div>
    

    </div> <!-- end orders-table-container card -->
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="modal-overlay">
    <div class="modal-content">
        <button type="button" class="modal-close" onclick="closeOrderDetails()">&times;</button>
        <h3 class="modal-heading">📋 Bestelldetails</h3>
        <div id="order-details-content"></div>
        <div class="order-modal-footer">
            <button type="button" class="button-primary" onclick="closeOrderDetails()">Schließen</button>
        </div>
    </div>
</div>



<script>
function showOrderDetails(orderId) {
    // Find order data from PHP
    const orders = <?php echo json_encode($orders); ?>;
    const orderLogs = <?php echo json_encode($order_logs); ?>;
    const order = orders.find(o => o.id == orderId);
    
    if (!order) return;
    
    let detailsHtml = `
        <div class="details-grid">
            <div>
                <h4>📋 Bestellinformationen</h4>
                <p><strong>Bestellnummer:</strong> #${order.order_number ? order.order_number : order.id}</p>
                <p><strong>Datum:</strong> ${new Date(order.created_at).toLocaleString('de-DE')}</p>
                <p><strong>Preis:</strong> ${parseFloat(order.final_price).toFixed(2).replace('.', ',')}€${order.mode === 'kauf' ? '' : '/Monat'}</p>
                ${(order.shipping_name || order.shipping_cost > 0) ? `<p><strong>Versand:</strong> ${order.shipping_name ? order.shipping_name : 'Versand'}${order.shipping_cost > 0 ? ' - ' + parseFloat(order.shipping_cost).toFixed(2).replace('.', ',') + '€' : ''}</p>` : ''}
                <p><strong>Rabatt:</strong> ${order.discount_amount > 0 ? '-'+parseFloat(order.discount_amount).toFixed(2).replace('.', ',')+'€' : '–'}</p>
            </div>
            <div>
                <h4>👤 Kundendaten</h4>
                <p><strong>Name:</strong> ${order.customer_name || 'Nicht angegeben'}</p>
                <p><strong>E-Mail:</strong> ${order.customer_email || 'Nicht angegeben'}</p>
                <p><strong>Telefon:</strong> ${order.customer_phone || 'Nicht angegeben'}</p>
                <p><strong>Versandadresse:</strong> ${order.customer_street ? order.customer_street + ', ' + order.customer_postal + ' ' + order.customer_city + ', ' + order.customer_country : 'Nicht angegeben'}</p>
                <p><strong>Rechnungsadresse:</strong> ${order.customer_street ? order.customer_street + ', ' + order.customer_postal + ' ' + order.customer_city + ', ' + order.customer_country : 'Nicht angegeben'}</p>
                <p><strong>IP-Adresse:</strong> ${order.user_ip}</p>
            </div>
        </div>
        
        <h4>🛍️ Produktauswahl</h4>
        <ul>
            <li><strong>Ausführung:</strong> ${order.variant_name}</li>
            <li><strong>Extra:</strong> ${order.extra_names}</li>
            <li><strong>${order.mode === 'kauf' ? 'Miettage' : 'Mietdauer'}:</strong> ${order.rental_days ? order.rental_days : order.duration_name}</li>
            ${order.start_date && order.end_date ? `<li><strong>Zeitraum:</strong> ${new Date(order.start_date).toLocaleDateString('de-DE')} - ${new Date(order.end_date).toLocaleDateString('de-DE')}</li>` : ''}
    `;
    
    if (order.condition_name) {
        detailsHtml += `<li><strong>Zustand:</strong> ${order.condition_name}</li>`;
    }
    
    if (order.product_color_name) {
        detailsHtml += `<li><strong>Produktfarbe:</strong> ${order.product_color_name}</li>`;
    }
    
    if (order.frame_color_name) {
        detailsHtml += `<li><strong>Gestellfarbe:</strong> ${order.frame_color_name}</li>`;
    }
    
    detailsHtml += `
        </ul>


        <h4>🖥️ Technische Daten</h4>
        <p><strong>User Agent:</strong> ${order.user_agent}</p>
    `;

    const logs = orderLogs[order.id] || [];
    if (logs.length) {
        detailsHtml += '<h4>📑 Verlauf</h4><ul>';
        logs.forEach(l => {
            const date = new Date(l.created_at).toLocaleString('de-DE');
            detailsHtml += `<li>[${date}] ${l.event}${l.message ? ' - ' + l.message : ''}</li>`;
        });
        detailsHtml += '</ul>';
    }
    
    document.getElementById('order-details-content').innerHTML = detailsHtml;
    document.getElementById('order-details-modal').style.display = 'block';
}

function closeOrderDetails() {
    document.getElementById('order-details-modal').style.display = 'none';
}

function exportOrders(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    
    // Create temporary link for download
    const link = document.createElement('a');
    link.href = window.location.pathname + '?' + params.toString();
    link.download = `bestellungen_${new Date().toISOString().split('T')[0]}.${format}`;
    link.click();
}

function printOrders() {
    window.print();
}


const selectAllOrders = document.getElementById('select-all-orders');
if (selectAllOrders) {
    selectAllOrders.addEventListener('change', function() {
        const orderCheckboxes = document.querySelectorAll('.order-checkbox');
        orderCheckboxes.forEach(cb => cb.checked = this.checked);
    });
}

// Close modal when clicking outside
document.getElementById('order-details-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderDetails();
    }
});
</script>
