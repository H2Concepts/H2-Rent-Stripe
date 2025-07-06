<?php
// Analytics Dashboard Tab Content

// Get statistics for selected category or all categories
$where_clause = $selected_category > 0 ? "WHERE o.category_id = %d" : "";
$where_values = $selected_category > 0 ? array($selected_category) : array();

// Get basic stats
$total_orders = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_orders o " . $where_clause,
    ...$where_values
));

$total_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(final_price) FROM {$wpdb->prefix}federwiegen_orders o " . $where_clause,
    ...$where_values
)) ?? 0;

$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Get popular variants - FIXED QUERY
$popular_variants = $wpdb->get_results($wpdb->prepare(
    "SELECT v.name, COUNT(*) as order_count 
     FROM {$wpdb->prefix}federwiegen_orders o
     LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON o.variant_id = v.id
     " . $where_clause . "
     AND v.name IS NOT NULL
     GROUP BY o.variant_id, v.name 
     ORDER BY order_count DESC 
     LIMIT 5",
    ...$where_values
));

// Get popular extras - FIXED QUERY
$popular_extras = $wpdb->get_results($wpdb->prepare(
    "SELECT e.name, COUNT(*) as order_count
     FROM {$wpdb->prefix}federwiegen_orders o
     LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON FIND_IN_SET(e.id, o.extra_ids)
     " . $where_clause . "
     AND e.name IS NOT NULL
     GROUP BY e.id
     ORDER BY order_count DESC
     LIMIT 5",
    ...$where_values
));

// Get recent orders
$recent_orders = $wpdb->get_results($wpdb->prepare(
    "SELECT o.*, v.name as variant_name,
            GROUP_CONCAT(e.name SEPARATOR ', ') AS extra_names,
            d.name as duration_name
     FROM {$wpdb->prefix}federwiegen_orders o
     LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON o.variant_id = v.id
     LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON FIND_IN_SET(e.id, o.extra_ids)
     LEFT JOIN {$wpdb->prefix}federwiegen_durations d ON o.duration_id = d.id
     " . $where_clause . "
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT 10",
    ...$where_values
));

// Get monthly revenue trend (last 6 months)
$monthly_revenue = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        DATE_FORMAT(o.created_at, '%%Y-%%m') as month,
        COUNT(*) as order_count,
        SUM(o.final_price) as revenue
     FROM {$wpdb->prefix}federwiegen_orders o
     " . $where_clause . "
     AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(o.created_at, '%%Y-%%m')
     ORDER BY month DESC
     LIMIT 6",
    ...$where_values
));
?>

<div class="federwiegen-analytics-dashboard">
    <!-- Stats Cards -->
    <div class="federwiegen-stats-grid">
        <div class="federwiegen-stat-card">
            <div class="federwiegen-stat-icon">📋</div>
            <div class="federwiegen-stat-content">
                <h3><?php echo number_format($total_orders); ?></h3>
                <p>Gesamt-Bestellungen</p>
            </div>
        </div>
        
        <div class="federwiegen-stat-card">
            <div class="federwiegen-stat-icon">💰</div>
            <div class="federwiegen-stat-content">
                <h3><?php echo number_format($total_revenue, 2, ',', '.'); ?>€</h3>
                <p>Gesamt-Umsatz</p>
            </div>
        </div>
        
        <div class="federwiegen-stat-card">
            <div class="federwiegen-stat-icon">📊</div>
            <div class="federwiegen-stat-content">
                <h3><?php echo number_format($avg_order_value, 2, ',', '.'); ?>€</h3>
                <p>Durchschnittswert</p>
            </div>
        </div>
        
        <div class="federwiegen-stat-card">
            <div class="federwiegen-stat-icon">📈</div>
            <div class="federwiegen-stat-content">
                <h3><?php echo count($monthly_revenue); ?></h3>
                <p>Aktive Monate</p>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="federwiegen-charts-grid">
        <!-- Popular Variants -->
        <div class="federwiegen-chart-card">
            <h4>📦 Beliebte Ausführungen</h4>
            <?php if (!empty($popular_variants)): ?>
                <div class="federwiegen-chart-list">
                    <?php foreach ($popular_variants as $variant): ?>
                    <div class="federwiegen-chart-item">
                        <span class="federwiegen-chart-label"><?php echo esc_html($variant->name); ?></span>
                        <div class="federwiegen-chart-bar">
                            <div class="federwiegen-chart-fill" style="width: <?php echo min(100, ($variant->order_count / max(1, $popular_variants[0]->order_count)) * 100); ?>%;"></div>
                        </div>
                        <span class="federwiegen-chart-value"><?php echo $variant->order_count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="federwiegen-no-data">Noch keine Bestelldaten verfügbar</p>
            <?php endif; ?>
        </div>
        
        <!-- Popular Extras -->
        <div class="federwiegen-chart-card">
            <h4>🎁 Beliebte Extras</h4>
            <?php if (!empty($popular_extras)): ?>
                <div class="federwiegen-chart-list">
                    <?php foreach ($popular_extras as $extra): ?>
                    <div class="federwiegen-chart-item">
                        <span class="federwiegen-chart-label"><?php echo esc_html($extra->name); ?></span>
                        <div class="federwiegen-chart-bar">
                            <div class="federwiegen-chart-fill" style="width: <?php echo min(100, ($extra->order_count / max(1, $popular_extras[0]->order_count)) * 100); ?>%;"></div>
                        </div>
                        <span class="federwiegen-chart-value"><?php echo $extra->order_count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="federwiegen-no-data">Noch keine Bestelldaten verfügbar</p>
            <?php endif; ?>
        </div>
        
        <!-- Monthly Revenue -->
        <div class="federwiegen-chart-card federwiegen-chart-wide">
            <h4>📈 Monatlicher Umsatz (letzte 6 Monate)</h4>
            <?php if (!empty($monthly_revenue)): ?>
                <div class="federwiegen-revenue-chart">
                    <?php 
                    $max_revenue = max(array_column($monthly_revenue, 'revenue'));
                    foreach (array_reverse($monthly_revenue) as $month_data): 
                        $month_name = date('M Y', strtotime($month_data->month . '-01'));
                        $percentage = $max_revenue > 0 ? ($month_data->revenue / $max_revenue) * 100 : 0;
                    ?>
                    <div class="federwiegen-revenue-item">
                        <div class="federwiegen-revenue-bar">
                            <div class="federwiegen-revenue-fill" style="height: <?php echo $percentage; ?>%;"></div>
                        </div>
                        <div class="federwiegen-revenue-label"><?php echo $month_name; ?></div>
                        <div class="federwiegen-revenue-value">
                            <?php echo number_format($month_data->revenue, 0, ',', '.'); ?>€<br>
                            <small><?php echo $month_data->order_count; ?> Bestellungen</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="federwiegen-no-data">Noch keine Umsatzdaten verfügbar</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="federwiegen-recent-orders">
        <h4>📋 Neueste Bestellungen</h4>
        <?php if (!empty($recent_orders)): ?>
            <div class="federwiegen-orders-list">
                <?php foreach ($recent_orders as $order): ?>
                <div class="federwiegen-order-item">
                    <div class="federwiegen-order-content">
                        <h5>#<?php echo $order->id; ?> - <?php echo esc_html($order->variant_name); ?></h5>
                        <p><?php echo esc_html($order->extra_names); ?> | <?php echo esc_html($order->duration_name); ?></p>
                        <small><?php echo date('d.m.Y H:i', strtotime($order->created_at)); ?></small>
                    </div>
                    <div class="federwiegen-order-price">
                        <strong><?php echo number_format($order->final_price, 2, ',', '.'); ?>€</strong>
                        <small>/Monat</small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="federwiegen-view-all">
                <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics&tab=orders&category=' . $selected_category); ?>" class="button">
                    📋 Alle Bestellungen anzeigen
                </a>
            </div>
        <?php else: ?>
            <p class="federwiegen-no-data">Noch keine Bestellungen vorhanden</p>
        <?php endif; ?>
    </div>
</div>

<style>
.federwiegen-analytics-dashboard {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.federwiegen-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.federwiegen-stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.federwiegen-stat-icon {
    font-size: 2rem;
    background: #f8f9fa;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.federwiegen-stat-content h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    color: #5f7f5f;
}

.federwiegen-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9rem;
}

.federwiegen-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.federwiegen-chart-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-chart-card.federwiegen-chart-wide {
    grid-column: 1 / -1;
}

.federwiegen-chart-card h4 {
    margin: 0 0 15px 0;
    color: #3c434a;
}

.federwiegen-chart-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.federwiegen-chart-item {
    display: grid;
    grid-template-columns: 1fr 100px 50px;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
}

.federwiegen-chart-label {
    font-weight: 500;
    color: #3c434a;
    font-size: 0.9rem;
}

.federwiegen-chart-bar {
    background: #f0f0f0;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.federwiegen-chart-fill {
    background: linear-gradient(90deg, #5f7f5f, #4a674a);
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.federwiegen-chart-value {
    color: #5f7f5f;
    font-weight: 600;
    font-size: 0.9rem;
    text-align: right;
}

.federwiegen-revenue-chart {
    display: flex;
    gap: 15px;
    align-items: end;
    height: 200px;
    padding: 20px 0;
}

.federwiegen-revenue-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.federwiegen-revenue-bar {
    background: #f0f0f0;
    width: 100%;
    height: 120px;
    border-radius: 4px 4px 0 0;
    display: flex;
    align-items: end;
    overflow: hidden;
}

.federwiegen-revenue-fill {
    background: linear-gradient(180deg, #5f7f5f, #4a674a);
    width: 100%;
    border-radius: 4px 4px 0 0;
    transition: height 0.3s ease;
    min-height: 4px;
}

.federwiegen-revenue-label {
    font-size: 0.8rem;
    color: #666;
    font-weight: 500;
}

.federwiegen-revenue-value {
    text-align: center;
    font-size: 0.8rem;
    color: #5f7f5f;
    font-weight: 600;
}

.federwiegen-revenue-value small {
    color: #999;
    font-weight: normal;
}

.federwiegen-recent-orders {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-recent-orders h4 {
    margin: 0 0 15px 0;
    color: #3c434a;
}

.federwiegen-orders-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.federwiegen-order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.federwiegen-order-content h5 {
    margin: 0 0 5px 0;
    color: #3c434a;
}

.federwiegen-order-content p {
    margin: 0 0 5px 0;
    color: #666;
    font-size: 0.9rem;
}

.federwiegen-order-content small {
    color: #999;
    font-size: 0.8rem;
}

.federwiegen-order-price {
    text-align: right;
    color: #5f7f5f;
    font-size: 1.1rem;
}

.federwiegen-order-price small {
    display: block;
    font-size: 0.8rem;
    color: #999;
}

.federwiegen-view-all {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.federwiegen-no-data {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .federwiegen-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .federwiegen-charts-grid {
        grid-template-columns: 1fr;
    }
    
    .federwiegen-chart-item {
        grid-template-columns: 1fr 60px 40px;
        gap: 8px;
    }
    
    .federwiegen-revenue-chart {
        gap: 8px;
        height: 150px;
    }
    
    .federwiegen-revenue-bar {
        height: 80px;
    }
    
    .federwiegen-order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
