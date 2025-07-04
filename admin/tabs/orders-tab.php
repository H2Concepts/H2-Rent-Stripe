<?php
// Orders Tab Content

// Handle delete order
if (isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    $result = $wpdb->delete(
        $wpdb->prefix . 'federwiegen_orders',
        array('id' => $order_id),
        array('%d')
    );
    
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Bestellung erfolgreich gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen der Bestellung: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Handle bulk delete
if (!empty($_POST['delete_orders']) && is_array($_POST['delete_orders'])) {
    $ids = array_map('intval', (array) $_POST['delete_orders']);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}federwiegen_orders WHERE id IN ($placeholders)",
            ...$ids
        );
        $result = $wpdb->query($query);

        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Bestellungen erfolgreich gelöscht!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen der Bestellungen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Date range
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

// Build WHERE clause for orders
$where_conditions = array("o.created_at BETWEEN %s AND %s");
$where_values = array($date_from . ' 00:00:00', $date_to . ' 23:59:59');

if ($selected_category > 0) {
    $where_conditions[] = "o.category_id = %d";
    $where_values[] = $selected_category;
}

$where_clause = implode(' AND ', $where_conditions);

// Get orders with all details
$orders = $wpdb->get_results($wpdb->prepare(
    "SELECT o.*,
            c.name as category_name,
            v.name as variant_name,
            GROUP_CONCAT(e.name SEPARATOR ', ') AS extra_names,
            d.name as duration_name,
            cond.name as condition_name,
            pc.name as product_color_name,
            fc.name as frame_color_name
     FROM {$wpdb->prefix}federwiegen_orders o
     LEFT JOIN {$wpdb->prefix}federwiegen_categories c ON o.category_id = c.id
     LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON o.variant_id = v.id
     LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON FIND_IN_SET(e.id, o.extra_ids)
     LEFT JOIN {$wpdb->prefix}federwiegen_durations d ON o.duration_id = d.id
     LEFT JOIN {$wpdb->prefix}federwiegen_conditions cond ON o.condition_id = cond.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors pc ON o.product_color_id = pc.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors fc ON o.frame_color_id = fc.id
     WHERE $where_clause
     GROUP BY o.id
     ORDER BY o.created_at DESC",
    ...$where_values
));

// Get summary statistics
$total_orders = count($orders);
$total_revenue = array_sum(array_column($orders, 'final_price'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
?>

<div class="federwiegen-orders-tab">
    <!-- Filter Section -->
    <div class="federwiegen-filter-card">
        <h4>🔍 Filter & Zeitraum</h4>
        <form method="get" action="" class="federwiegen-filter-form">
            <input type="hidden" name="page" value="federwiegen-analytics">
            <input type="hidden" name="tab" value="orders">
            <input type="hidden" name="category" value="<?php echo $selected_category; ?>">
            
            <div class="federwiegen-filter-grid">
                <div class="federwiegen-filter-group">
                    <label for="date-from"><strong>Von:</strong></label>
                    <input type="date" name="date_from" id="date-from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="federwiegen-filter-group">
                    <label for="date-to"><strong>Bis:</strong></label>
                    <input type="date" name="date_to" id="date-to" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <div class="federwiegen-filter-group">
                    <input type="submit" value="Filter anwenden" class="button button-primary">
                </div>
            </div>
        </form>
    </div>
    
    <!-- Summary Statistics -->
    <div class="federwiegen-summary-stats">
        <div class="federwiegen-summary-card">
            <h3><?php echo number_format($total_orders); ?></h3>
            <p>Bestellungen</p>
        </div>
        
        <div class="federwiegen-summary-card">
            <h3><?php echo number_format($total_revenue, 2, ',', '.'); ?>€</h3>
            <p>Gesamt-Umsatz</p>
        </div>
        
        <div class="federwiegen-summary-card">
            <h3><?php echo number_format($avg_order_value, 2, ',', '.'); ?>€</h3>
            <p>Durchschnittswert</p>
        </div>
        
        <div class="federwiegen-summary-card">
            <h3><?php echo date('d.m.Y', strtotime($date_from)); ?> - <?php echo date('d.m.Y', strtotime($date_to)); ?></h3>
            <p>Zeitraum</p>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="federwiegen-orders-card">
        <div class="federwiegen-orders-header">
            <h4>📋 Bestellübersicht</h4>
            <?php if (!empty($orders)): ?>
            <div class="federwiegen-bulk-actions">
                <button type="button" class="button" onclick="toggleSelectAll()">Alle auswählen</button>
                <button type="button" class="button" onclick="deleteSelected()" style="color: #dc3232;">Ausgewählte löschen</button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($orders)): ?>
        <div class="federwiegen-empty-state">
            <p>Keine Bestellungen im gewählten Zeitraum gefunden.</p>
            <p>Versuchen Sie einen anderen Zeitraum oder eine andere Kategorie.</p>
        </div>
        <?php else: ?>
        
        <div style="overflow-x: auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="select-all-orders"></th>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 120px;">Datum</th>
                        <th>Kunde</th>
                        <th>Produktdetails</th>
                        <th style="width: 100px;">Preis</th>
                        <th style="width: 150px;">Aktionen</th>
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
                            <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&tab=orders&category=' . $selected_category . '&delete_order=' . $order->id . '&date_from=' . $date_from . '&date_to=' . $date_to); ?>" 
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
</div>

<style>
.federwiegen-orders-tab {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.federwiegen-filter-card {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-filter-card h4 {
    margin: 0 0 15px 0;
    color: #3c434a;
}

.federwiegen-filter-form {
    margin: 0;
}

.federwiegen-filter-grid {
    display: grid;
    grid-template-columns: auto auto auto;
    gap: 15px;
    align-items: end;
}

.federwiegen-filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.federwiegen-filter-group label {
    font-weight: 600;
    color: #3c434a;
}

.federwiegen-summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.federwiegen-summary-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.federwiegen-summary-card h3 {
    margin: 0 0 5px 0;
    font-size: 1.8rem;
    font-weight: bold;
    color: #5f7f5f;
}

.federwiegen-summary-card p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.federwiegen-orders-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.federwiegen-orders-header h4 {
    margin: 0;
    color: #3c434a;
}

.federwiegen-bulk-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .federwiegen-filter-grid {
        grid-template-columns: 1fr;
    }
    
    .federwiegen-summary-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .federwiegen-orders-header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
}
</style>

<script>
function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all-orders');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    
    const allChecked = Array.from(orderCheckboxes).every(cb => cb.checked);
    
    orderCheckboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
    
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
    
    // Create form to submit multiple deletions
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.location.href;
    
    selectedOrders.forEach(orderId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_orders[]';
        input.value = orderId;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Handle select all checkbox
const selectAllOrders = document.getElementById('select-all-orders');
if (selectAllOrders) {
    selectAllOrders.addEventListener('change', function() {
        const orderCheckboxes = document.querySelectorAll('.order-checkbox');
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}
</script>
