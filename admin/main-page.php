<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get categories count
$categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_categories");
$variants_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_variants");
$extras_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_extras");
$durations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_durations");
$links_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}federwiegen_links");

// Get recent categories
$recent_categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY id DESC LIMIT 3");

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}federwiegen_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <!-- Kompakter Admin Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">
            🏠
        </div>
        <div class="federwiegen-admin-title-compact">
            <h1><?php echo esc_html($branding['plugin_name'] ?? 'H2 Concepts Rent Plugin'); ?></h1>
            <p>Dashboard & Übersicht</p>
        </div>
    </div>
    
    <!-- Kompakte Statistiken -->
    <div class="federwiegen-stats-compact">
        <div class="federwiegen-stat-item">
            <div class="federwiegen-stat-number"><?php echo $categories_count; ?></div>
            <div class="federwiegen-stat-label">Kategorien</div>
        </div>
        <div class="federwiegen-stat-item">
            <div class="federwiegen-stat-number"><?php echo $variants_count; ?></div>
            <div class="federwiegen-stat-label">Ausführungen</div>
        </div>
        <div class="federwiegen-stat-item">
            <div class="federwiegen-stat-number"><?php echo $extras_count; ?></div>
            <div class="federwiegen-stat-label">Extras</div>
        </div>
        <div class="federwiegen-stat-item">
            <div class="federwiegen-stat-number"><?php echo $durations_count; ?></div>
            <div class="federwiegen-stat-label">Mietdauern</div>
        </div>
        <div class="federwiegen-stat-item">
            <div class="federwiegen-stat-number"><?php echo $links_count; ?></div>
            <div class="federwiegen-stat-label">Stripe Links</div>
        </div>
    </div>
    
    <!-- Hauptnavigation -->
    <div class="federwiegen-main-nav">
        <h3>🧭 Hauptbereiche</h3>
        <div class="federwiegen-nav-cards">
            <a href="<?php echo admin_url('admin.php?page=federwiegen-categories'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">🏷️</div>
                <div class="federwiegen-nav-content">
                    <h4>Kategorien</h4>
                    <p>Produktkategorien & SEO-Einstellungen</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-variants'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">📦</div>
                <div class="federwiegen-nav-content">
                    <h4>Ausführungen</h4>
                    <p>Produktvarianten mit Bildern</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-extras'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">🎁</div>
                <div class="federwiegen-nav-content">
                    <h4>Extras</h4>
                    <p>Zusatzoptionen & Zubehör</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-durations'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">⏰</div>
                <div class="federwiegen-nav-content">
                    <h4>Mietdauern</h4>
                    <p>Laufzeiten & Rabatte</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-links'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">🔗</div>
                <div class="federwiegen-nav-content">
                    <h4>Stripe Links</h4>
                    <p>Zahlungsverknüpfungen</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-analytics'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">📊</div>
                <div class="federwiegen-nav-content">
                    <h4>Analytics</h4>
                    <p>Statistiken & Bestellungen</p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=federwiegen-branding'); ?>" class="federwiegen-nav-card">
                <div class="federwiegen-nav-icon">🎨</div>
                <div class="federwiegen-nav-content">
                    <h4>Branding</h4>
                    <p>Design & Anpassungen</p>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Schnellzugriff -->
    <?php if (!empty($recent_categories)): ?>
    <div class="federwiegen-quick-access">
        <h3>⚡ Schnellzugriff</h3>
        <div class="federwiegen-category-cards">
            <?php foreach ($recent_categories as $category): ?>
            <div class="federwiegen-category-card">
                <h4><?php echo esc_html($category->name); ?></h4>
                <code>[federwiegen_product category="<?php echo esc_html($category->shortcode); ?>"]</code>
                <div class="federwiegen-category-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-variants&category=' . $category->id); ?>" class="button button-small">Ausführungen</a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $category->id); ?>" class="button button-small">Stripe Links</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Hilfe & Tipps -->
    <div class="federwiegen-help-section">
        <h3>💡 Erste Schritte</h3>
        <div class="federwiegen-help-cards">
            <div class="federwiegen-help-card">
                <h4>1. Kategorie erstellen</h4>
                <p>Erstellen Sie eine neue Produktkategorie mit SEO-Einstellungen</p>
                <a href="<?php echo admin_url('admin.php?page=federwiegen-categories'); ?>" class="button">Kategorien →</a>
            </div>
            <div class="federwiegen-help-card">
                <h4>2. Ausführungen hinzufügen</h4>
                <p>Fügen Sie Produktvarianten mit Bildern hinzu</p>
                <a href="<?php echo admin_url('admin.php?page=federwiegen-variants'); ?>" class="button">Ausführungen →</a>
            </div>
            <div class="federwiegen-help-card">
                <h4>3. Stripe Links konfigurieren</h4>
                <p>Verknüpfen Sie Produktkombinationen mit Zahlungslinks</p>
                <a href="<?php echo admin_url('admin.php?page=federwiegen-links'); ?>" class="button">Stripe Links →</a>
            </div>
        </div>
    </div>
</div>

<style>
.federwiegen-admin-header-compact {
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

.federwiegen-admin-logo-compact {
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

.federwiegen-admin-title-compact h1 {
    margin: 0;
    color: #3c434a;
    font-size: 24px;
}

.federwiegen-admin-title-compact p {
    margin: 5px 0 0 0;
    opacity: 0.7;
    font-size: 14px;
    color: #3c434a;
}

.federwiegen-stats-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.federwiegen-stat-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.federwiegen-stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #5f7f5f;
    margin-bottom: 5px;
}

.federwiegen-stat-label {
    font-size: 0.9rem;
    color: #666;
}

.federwiegen-main-nav {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.federwiegen-main-nav h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.federwiegen-nav-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.federwiegen-nav-card {
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

.federwiegen-nav-card:hover {
    background: #5f7f5f;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.federwiegen-nav-icon {
    font-size: 2rem;
    min-width: 50px;
}

.federwiegen-nav-content h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.federwiegen-nav-content p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.8;
}

.federwiegen-quick-access {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.federwiegen-quick-access h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.federwiegen-category-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.federwiegen-category-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.federwiegen-category-card h4 {
    margin: 0 0 10px 0;
    color: #5f7f5f;
}

.federwiegen-category-card p {
    margin: 0 0 10px 0;
    font-size: 0.9rem;
    color: #666;
}

.federwiegen-category-card code {
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    display: block;
    margin-bottom: 10px;
}

.federwiegen-category-actions {
    display: flex;
    gap: 10px;
}

.federwiegen-help-section {
    background: #f0f8ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-help-section h3 {
    margin: 0 0 20px 0;
    color: #3c434a;
}

.federwiegen-help-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.federwiegen-help-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.federwiegen-help-card h4 {
    margin: 0 0 10px 0;
    color: #5f7f5f;
}

.federwiegen-help-card p {
    margin: 0 0 15px 0;
    font-size: 0.9rem;
    color: #666;
}

@media (max-width: 768px) {
    .federwiegen-stats-compact {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .federwiegen-nav-cards {
        grid-template-columns: 1fr;
    }
    
    .federwiegen-nav-card {
        flex-direction: column;
        text-align: center;
    }
    
    .federwiegen-category-cards,
    .federwiegen-help-cards {
        grid-template-columns: 1fr;
    }
}
</style>
