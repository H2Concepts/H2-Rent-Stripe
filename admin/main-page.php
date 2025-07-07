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

// Get recent categories
$recent_categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY id DESC LIMIT 3");

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
    
    <!-- Schnellzugriff -->
    <?php if (!empty($recent_categories)): ?>
    <div class="produkt-quick-access">
        <h3>⚡ Schnellzugriff</h3>
        <div class="produkt-category-cards">
            <?php foreach ($recent_categories as $category): ?>
            <div class="produkt-category-card">
                <h4><?php echo esc_html($category->name); ?></h4>
                <code>[produkt_product category="<?php echo esc_html($category->shortcode); ?>"]</code>
                <div class="produkt-category-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $category->id); ?>" class="button button-small">Ausführungen</a>
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

.produkt-quick-access {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.produkt-quick-access h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.produkt-category-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
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
