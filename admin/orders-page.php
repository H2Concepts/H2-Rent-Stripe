
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
?>

<div class="wrap">
    <!-- Standard Admin Header -->
    <div class="federwiegen-admin-header">
        <div class="federwiegen-admin-logo">
            📋
        </div>
        <div class="federwiegen-admin-title">
            <h1>Bestellungen</h1>
            <p>Übersicht aller Kundenbestellungen mit detaillierten Produktinformationen</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="federwiegen-admin-nav">
        <h3>🧭 Schnellnavigation</h3>
        <div class="federwiegen-nav-grid">
            <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-dashboard"></span>
                Dashboard
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-category"></span>
                Kategorien
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-chart-bar"></span>
                Analytics
            </a>
            <a href="<?php echo admin_url('admin.php?page=federwiegen-links'); ?>" class="federwiegen-nav-item">
                <span class="dashicons dashicons-admin-links"></span>
                Stripe Links
            </a>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div style="background: #f0f8ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>🔍 Filter & Zeitraum</h3>
        <form method="get" action="" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <input type="hidden" name="page" value="federwiegen-orders">
            
            <div>
                <label for="category-select"><strong>Kategorie:</strong></label>
                <select name="category" id="category-select" style="min-width: 200px;">
                    <option value="0" <?php selected($selected_category, 0); ?>>Alle Kategorien</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                        <?php echo esc_html($category->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="date-from"><strong>Von:</strong></label>
                <input type="date" name="date_from" id="date-from" value="<?php echo esc_attr($date_from); ?>">
            </div>
            
            <div>
                <label for="date-to"><strong>Bis:</strong></label>
                <input type="date" name="date_to" id="date-to" value="<?php echo esc_attr($date_to); ?>">
            </div>
            
            <input type="submit" value="Filter anwenden" class="button button-primary">
        </form>
        
        <?php if ($current_category): ?>
        <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
            <strong>📝 Aktuelle Kategorie:</strong> <?php echo esc_html($current_category->name); ?>
            <code>[federwiegen_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Summary Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #5f7f5f;">📋 Gesamt-Bestellungen</h3>
            <div style="font-size: 2.5rem; font-weight: bold; color: #2a372a;"><?php echo number_format($total_orders); ?></div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Im gewählten Zeitraum</p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #5f7f5f;">💰 Gesamt-Umsatz</h3>
            <div style="font-size: 2.5rem; font-weight: bold; color: #4a674a;"><?php echo number_format($total_revenue, 2, ',', '.'); ?>€</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Monatlicher Mietumsatz</p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #5f7f5f;">📊 Durchschnittswert</h3>
            <div style="font-size: 2.5rem; font-weight: bold; color: #dc3232;"><?php echo number_format($avg_order_value, 2, ',', '.'); ?>€</div>
            <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">Pro Bestellung</p>
        </div>
        
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center;">
            <h3 style="margin: 0 0 10px 0; color: #5f7f5f;">📅 Zeitraum</h3>
            <div style="font-size: 1.2rem; font-weight: bold; color: #2a372a;">
                <?php echo date('d.m.Y', strtotime($date_from)); ?><br>
                <small>bis</small><br>
                <?php echo date('d.m.Y', strtotime($date_to)); ?>
            </div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>📋 Bestellübersicht</h3>
        <?php if (!empty($orders)): ?>
        <div style="margin:10px 0;">
            <button type="button" class="button" onclick="toggleSelectAll()">Alle auswählen</button>
            <button type="button" class="button" onclick="deleteSelected()" style="color:#dc3232;">Ausgewählte löschen</button>
        </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 40px;">
            <p style="font-size: 18px; color: #666;">Keine Bestellungen im gewählten Zeitraum gefunden.</p>
            <p>Versuchen Sie einen anderen Zeitraum oder eine andere Kategorie.</p>
        </div>
        <?php else: ?>
        
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="select-all-orders"></th>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 120px;">Datum</th>
                        <th>Kunde</th>
                        <th>Produktdetails</th>
                        <th style="width: 100px;">Preis</th>
                        <th style="width: 120px;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><input type="checkbox" class="order-checkbox" value="<?php echo $order->id; ?>"></td>
                        <td><strong>#<?php echo $order->id; ?></strong></td>
                        <td>
                            <?php echo date('d.m.Y', strtotime($order->created_at)); ?><br>
                            <small style="color: #666;"><?php echo date('H:i', strtotime($order->created_at)); ?> Uhr</small>
                        </td>
                        <td>
                            <?php if (!empty($order->customer_name)): ?>
                                <strong><?php echo esc_html($order->customer_name); ?></strong><br>
                            <?php endif; ?>
                            <?php if (!empty($order->customer_email)): ?>
                                <a href="mailto:<?php echo esc_attr($order->customer_email); ?>"><?php echo esc_html($order->customer_email); ?></a><br>
                            <?php endif; ?>
                            <small style="color: #666;">IP: <?php echo esc_html($order->user_ip); ?></small>
                        </td>
                        <td>
                            <div style="line-height: 1.4;">
                                <strong><?php echo esc_html($order->category_name); ?></strong><br>
                                <span style="color: #666;">📦 <?php echo esc_html($order->variant_name); ?></span><br>
                                <span style="color: #666;">🎁 <?php echo esc_html($order->extra_names); ?></span><br>
                                <span style="color: #666;">⏰ <?php echo esc_html($order->duration_name); ?></span><br>
                                
                                <?php if ($order->condition_name): ?>
                                    <span style="color: #666;">🔄 <?php echo esc_html($order->condition_name); ?></span><br>
                                <?php endif; ?>
                                
                                <?php if ($order->product_color_name): ?>
                                    <span style="color: #666;">🎨 Produkt: <?php echo esc_html($order->product_color_name); ?></span><br>
                                <?php endif; ?>
                                
                                <?php if ($order->frame_color_name): ?>
                                    <span style="color: #666;">🖼️ Gestell: <?php echo esc_html($order->frame_color_name); ?></span><br>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <strong style="color: #4a674a; font-size: 16px;">
                                <?php echo number_format($order->final_price, 2, ',', '.'); ?>€
                            </strong><br>
                            <small style="color: #666;">/Monat</small>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($order->stripe_link); ?>" target="_blank" class="button button-small" title="Stripe Link öffnen">
                                💳 Stripe
                            </a>
                            <br><br>
                            <button type="button" class="button button-small" onclick="showOrderDetails(<?php echo $order->id; ?>)" title="Details anzeigen">
                                👁️ Details
                            </button>
                             <br><br>
                            <a href="<?php echo admin_url('admin.php?page=federwiegen-orders&category=' . $selected_category . '&delete_order=' . $order->id . '&date_from=' . $date_from . '&date_to=' . $date_to); ?>"
                               class="button button-small"
                               style="color: #dc3232;"
                               onclick="return confirm('Sind Sie sicher, dass Sie diese Bestellung löschen möchten?\n\nBestellung #<?php echo $order->id; ?> wird unwiderruflich gelöscht!')">
                                🗑️ Löschen
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Export Section -->
    <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>📤 Export & Aktionen</h3>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <button type="button" class="button" onclick="exportOrders('csv')">
                📊 Als CSV exportieren
            </button>
            <button type="button" class="button" onclick="exportOrders('excel')">
                📈 Als Excel exportieren
            </button>
            <button type="button" class="button" onclick="printOrders()">
                🖨️ Drucken
            </button>
        </div>
        <p style="margin-top: 10px; color: #666; font-size: 13px;">
            Exportiert werden alle Bestellungen im aktuell gewählten Filter-Zeitraum und der ausgewählten Kategorie.
        </p>
    </div>
    
    <!-- Info Box -->
    <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 20px; border-radius: 8px;">
        <h3>📋 Bestellungen-System</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>🎯 Was wird erfasst:</h4>
                <ul>
                    <li><strong>Produktauswahl:</strong> Alle gewählten Optionen</li>
                    <li><strong>Kundendaten:</strong> E-Mail und Name (falls angegeben)</li>
                    <li><strong>Preisberechnung:</strong> Finaler Mietpreis pro Monat</li>
                    <li><strong>Stripe-Link:</strong> Direkter Link zur Zahlung</li>
                    <li><strong>Zeitstempel:</strong> Exakte Bestellzeit</li>
                    <li><strong>Tracking-Daten:</strong> IP-Adresse und Browser</li>
                </ul>
            </div>
            <div>
                <h4>📊 Verwendung der Daten:</h4>
                <ul>
                    <li><strong>Bestellverfolgung:</strong> Nachvollziehung aller Anfragen</li>
                    <li><strong>Kundenservice:</strong> Support bei Fragen</li>
                    <li><strong>Analytics:</strong> Beliebte Produktkombinationen</li>
                    <li><strong>Umsatzanalyse:</strong> Monatliche Einnahmen</li>
                    <li><strong>Produktoptimierung:</strong> Welche Optionen werden gewählt</li>
                    <li><strong>E-Mail-Marketing:</strong> Kundenkommunikation</li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">
            <strong>💡 Tipp:</strong> Nutzen Sie die Filterfunktionen um spezifische Zeiträume oder Kategorien zu analysieren. Die Export-Funktion hilft bei der weiteren Datenverarbeitung in Excel oder anderen Tools.
        </div>
        
        <div style="margin-top: 10px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <strong>🔒 Datenschutz:</strong> Alle Kundendaten werden sicher gespeichert und nur für die Bestellabwicklung verwendet. IP-Adressen dienen der Fraud-Prevention und werden nach 30 Tagen anonymisiert.
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 30px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">
        <h3 style="margin-top: 0;">📋 Bestelldetails</h3>
        <div id="order-details-content"></div>
        <div style="text-align: right; margin-top: 20px;">
            <button type="button" class="button button-primary" onclick="closeOrderDetails()">Schließen</button>
        </div>
    </div>
</div>

<style>
.wp-list-table th,
.wp-list-table td {
    padding: 12px 8px;
    vertical-align: top;
}

.wp-list-table .button-small {
    margin-bottom: 5px;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .wp-list-table {
        font-size: 12px;
    }
    
    .wp-list-table th,
    .wp-list-table td {
        padding: 8px 4px;
    }
}
</style>

<script>
function showOrderDetails(orderId) {
    // Find order data from PHP
    const orders = <?php echo json_encode($orders); ?>;
    const order = orders.find(o => o.id == orderId);
    
    if (!order) return;
    
    let detailsHtml = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4>📋 Bestellinformationen</h4>
                <p><strong>Bestellnummer:</strong> #${order.id}</p>
                <p><strong>Datum:</strong> ${new Date(order.created_at).toLocaleString('de-DE')}</p>
                <p><strong>Kategorie:</strong> ${order.category_name}</p>
                <p><strong>Preis:</strong> ${parseFloat(order.final_price).toFixed(2).replace('.', ',')}€/Monat</p>
            </div>
            <div>
                <h4>👤 Kundendaten</h4>
                <p><strong>Name:</strong> ${order.customer_name || 'Nicht angegeben'}</p>
                <p><strong>E-Mail:</strong> ${order.customer_email || 'Nicht angegeben'}</p>
                <p><strong>IP-Adresse:</strong> ${order.user_ip}</p>
            </div>
        </div>
        
        <h4>🛍️ Produktauswahl</h4>
        <ul>
            <li><strong>Ausführung:</strong> ${order.variant_name}</li>
            <li><strong>Extra:</strong> ${order.extra_names}</li>
            <li><strong>Mietdauer:</strong> ${order.duration_name}</li>
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
        
        <h4>🔗 Stripe-Link</h4>
        <p><a href="${order.stripe_link}" target="_blank">${order.stripe_link}</a></p>
        
        <h4>🖥️ Technische Daten</h4>
        <p><strong>User Agent:</strong> ${order.user_agent}</p>
    `;
    
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

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all-orders');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');

    const allChecked = Array.from(orderCheckboxes).every(cb => cb.checked);

    orderCheckboxes.forEach(cb => cb.checked = !allChecked);
    selectAllCheckbox.checked = !allChecked;
}

function deleteSelected() {
    const selectedOrders = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);

    if (selectedOrders.length === 0) {
        alert('Bitte wählen Sie mindestens eine Bestellung aus.');
        return;
    }

    if (!confirm(`Sind Sie sicher, dass Sie ${selectedOrders.length} Bestellung(en) löschen möchten?\n\nDieser Vorgang kann nicht rückgängig gemacht werden!`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;

    selectedOrders.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_orders[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
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
