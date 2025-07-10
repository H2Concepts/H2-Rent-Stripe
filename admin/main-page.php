<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get categories count
$categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_categories");
$variants_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_variants");
$extras_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_extras");
$durations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_durations");
$colors_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_colors");

// Get recently edited products (latest entries)
$recent_products = $wpdb->get_results(
    "SELECT id, name, product_title, default_image, shipping_provider, meta_title
       FROM {$wpdb->prefix}produkt_categories
       ORDER BY id DESC
       LIMIT 4"
);

// Get recent orders
$recent_orders = $wpdb->get_results(
    "SELECT o.*, c.name AS category_name, c.product_title, v.name AS variant_name
       FROM {$wpdb->prefix}produkt_orders o
       LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
       LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
      ORDER BY o.created_at DESC
      LIMIT 4"
);

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Kompakter Admin Header -->
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">
            🏠
        </div>
        <div class="produkt-admin-title-compact">
            <h1><?php echo esc_html($branding['plugin_name'] ?? 'H2 Concepts Rental Pro'); ?></h1>
            <p>Dashboard & Übersicht</p>
        </div>
    </div>
    
    <!-- Kompakte Statistiken -->
    <div class="produkt-stats-compact">
        <div class="stat-card">
            <div class="produkt-stat-number"><?php echo $categories_count; ?></div>
            <div class="produkt-stat-label">Produkte</div>
        </div>
        <div class="stat-card">
            <div class="produkt-stat-number"><?php echo $variants_count; ?></div>
            <div class="produkt-stat-label">Ausführungen</div>
        </div>
        <div class="stat-card">
            <div class="produkt-stat-number"><?php echo $extras_count; ?></div>
            <div class="produkt-stat-label">Extras</div>
        </div>
        <div class="stat-card">
            <div class="produkt-stat-number"><?php echo $durations_count; ?></div>
            <div class="produkt-stat-label">Mietdauern</div>
        </div>
        <div class="stat-card">
            <div class="produkt-stat-number"><?php echo $colors_count; ?></div>
            <div class="produkt-stat-label">Farben</div>
        </div>
    </div>
    
    <!-- Hauptnavigation -->
    <div class="produkt-main-nav">
        <h3>🧭 Hauptbereiche</h3>
        <div class="produkt-nav-cards">
            <a href="<?php echo admin_url('admin.php?page=produkt-categories'); ?>" class="produkt-nav-card">
                <div class="produkt-nav-icon">🏷️</div>
                <div class="produkt-nav-content">
                    <h4>Produkte</h4>
                    <p>Produkte & SEO-Einstellungen</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=produkt-variants'); ?>" class="produkt-nav-card">
                <div class="produkt-nav-icon">📦</div>
                <div class="produkt-nav-content">
                    <h4>Ausführungen</h4>
                    <p>Produktvarianten mit Bildern</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=produkt-extras'); ?>" class="produkt-nav-card">
                <div class="produkt-nav-icon">🎁</div>
                <div class="produkt-nav-content">
                    <h4>Extras</h4>
                    <p>Zusatzoptionen & Zubehör</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=produkt-durations'); ?>" class="produkt-nav-card">
                <div class="produkt-nav-icon">⏰</div>
                <div class="produkt-nav-content">
                    <h4>Mietdauern</h4>
                    <p>Laufzeiten & Rabatte</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=produkt-settings&tab=branding'); ?>" class="produkt-nav-card">
                <div class="produkt-nav-icon">🎨</div>
                <div class="produkt-nav-content">
                    <h4>Branding</h4>
                    <p>Design & Anpassungen</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Zuletzt bearbeitete Produkte -->
    <?php if (!empty($recent_products)): ?>
    <div class="produkt-recent-section">
        <h3>🛒 Letzte Produkte</h3>
        <div class="produkt-category-cards">
            <?php foreach ($recent_products as $prod): ?>
            <?php $prod_url = home_url('/shop/produkt/' . sanitize_title($prod->product_title)); ?>
            <div class="produkt-category-card">
                <?php if (!empty($prod->default_image)): ?>
                    <img src="<?php echo esc_url($prod->default_image); ?>" class="produkt-recent-image" alt="<?php echo esc_attr($prod->name); ?>">
                <?php endif; ?>
                <h4><?php echo esc_html($prod->name); ?></h4>
                <code><?php echo esc_url($prod_url); ?></code>
                <p class="produkt-seo-status">
                    <?php if (!empty($prod->meta_title)): ?>
                        <span class="badge badge-success">SEO konfiguriert</span>
                    <?php else: ?>
                        <span class="badge badge-warning">SEO fehlt</span>
                    <?php endif; ?>
                </p>
                <?php if (!empty($prod->shipping_provider)): ?>
                    <img src="<?php echo esc_url(PRODUKT_PLUGIN_URL . 'assets/shipping-icons/' . $prod->shipping_provider . '.svg'); ?>" class="produkt-shipping-icon" alt="<?php echo esc_attr(strtoupper($prod->shipping_provider)); ?>">
                <?php endif; ?>
                <div class="produkt-category-actions">
                    <a href="<?php echo esc_url($prod_url); ?>" class="button button-small" target="_blank">Seite ansehen</a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-categories&tab=edit&edit=' . $prod->id); ?>" class="button button-small">Bearbeiten</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Letzte Bestellungen -->
    <?php if (!empty($recent_orders)): ?>
    <div class="produkt-recent-section">
        <h3>📦 Letzte Bestellungen</h3>
        <div class="produkt-category-cards">
            <?php foreach ($recent_orders as $order): ?>
            <div class="produkt-category-card">
                <h4>#<?php echo $order->id; ?> – <?php echo esc_html($order->category_name); ?></h4>
                <p style="margin-bottom:5px;">
                    <?php echo date('d.m.Y', strtotime($order->created_at)); ?>,
                    <?php echo esc_html($order->customer_name); ?>
                    (<?php echo esc_html($order->customer_postal . ' ' . $order->customer_city); ?>)
                </p>
                <p style="margin-bottom:10px;">
                    Produkt: <?php echo esc_html($order->product_title); ?><br>
                    Variante: <?php echo esc_html($order->variant_name); ?> – <?php echo number_format($order->final_price, 2, ',', '.'); ?>€
                </p>
                <p>
                    <?php if ($order->status === 'offen'): ?>
                        <span class="badge badge-warning">Offen</span>
                    <?php elseif ($order->status === 'gekündigt'): ?>
                        <span class="badge badge-danger">Gekündigt</span>
                    <?php else: ?>
                        <span class="badge badge-success">Abgeschlossen</span>
                    <?php endif; ?>
                </p>
                <div class="produkt-category-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-orders&delete_order=' . $order->id); ?>" class="button button-small" style="color:#dc3232;" onclick="return confirm('Bestellung wirklich löschen?');">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Hilfe & Tipps -->
    <div class="produkt-help-section">
        <h3>💡 Erste Schritte</h3>
        <div class="produkt-help-cards">
            <div class="produkt-help-card">
                <h4>1. Produkt erstellen</h4>
                <p>Erstellen Sie ein neues Produkt mit SEO-Einstellungen</p>
                <a href="<?php echo admin_url('admin.php?page=produkt-categories'); ?>" class="button">Produkte →</a>
            </div>
            <div class="produkt-help-card">
                <h4>2. Ausführungen hinzufügen</h4>
                <p>Fügen Sie Produktvarianten mit Bildern hinzu</p>
                <a href="<?php echo admin_url('admin.php?page=produkt-variants'); ?>" class="button">Ausführungen →</a>
            </div>
        </div>
    </div>
</div>

<style>
.produkt-admin-header-compact {
    background: transparent;
    color: #3c434a;
    padding: 15px 20px;
    margin: 20px 0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid #ddd;
}

.produkt-admin-logo-compact {
    width: 50px;
    height: 50px;
    background: rgba(60, 67, 74, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #3c434a;
}

.produkt-admin-title-compact h1 {
    margin: 0;
    color: #3c434a;
    font-size: 24px;
}

.produkt-admin-title-compact p {
    margin: 5px 0 0 0;
    opacity: 0.7;
    font-size: 14px;
    color: #3c434a;
}

.produkt-stats-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.produkt-stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: var(--produkt-primary);
    margin-bottom: 5px;
}

.produkt-stat-label {
    font-size: 0.9rem;
    color: #666;
}

.produkt-main-nav {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.produkt-main-nav h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.produkt-nav-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.produkt-nav-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #3c434a;
    transition: all 0.2s ease;
}

.produkt-nav-card:hover {
    background: var(--produkt-primary);
    color: var(--produkt-text);
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.produkt-nav-icon {
    font-size: 2rem;
    min-width: 50px;
}

.produkt-nav-content h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.produkt-nav-content p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.8;
}

.produkt-recent-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin: 0 auto 30px;
}

.produkt-recent-section h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.produkt-category-cards {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin: 0 auto;
}

.produkt-category-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.produkt-category-card h4 {
    margin: 0 0 10px 0;
    color: var(--produkt-primary);
}

.produkt-category-card p {
    margin: 0 0 10px 0;
    font-size: 0.9rem;
    color: #666;
}

.produkt-category-card code {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    display: block;
    margin-bottom: 10px;
}
.produkt-recent-image {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 10px;
}
.produkt-shipping-icon {
    width: 32px;
    height: auto;
    margin-bottom: 10px;
}
.produkt-seo-status {
    margin-bottom: 10px;
}

.produkt-category-actions {
    display: flex;
    gap: 10px;
}

.produkt-help-section {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
}

.produkt-help-section h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.produkt-help-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.produkt-help-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.produkt-help-card h4 {
    margin: 0 0 10px 0;
    color: var(--produkt-primary);
}

.produkt-help-card p {
    margin: 0 0 15px 0;
    font-size: 0.9rem;
    color: #666;
}

@media (max-width: 768px) {
    .produkt-stats-compact {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .produkt-nav-cards {
        grid-template-columns: 1fr;
    }
    
    .produkt-nav-card {
        flex-direction: column;
        text-align: center;
    }
    
    .produkt-category-cards,
    .produkt-help-cards {
        grid-template-columns: 1fr;
    }
}
</style>
