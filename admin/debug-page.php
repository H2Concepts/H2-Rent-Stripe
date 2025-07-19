<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Force database update if requested
if (isset($_POST['force_update'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $table_variants = $wpdb->prefix . 'produkt_variants';
    
    // Check if image_url column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_variants LIKE 'image_url'");
    
    if (empty($column_exists)) {
        $result = $wpdb->query("ALTER TABLE $table_variants ADD COLUMN image_url TEXT AFTER base_price");
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ image_url Spalte erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen der image_url Spalte: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-info"><p>ℹ️ image_url Spalte existiert bereits.</p></div>';
    }
    
}

// Manual cache clear for lowest prices
if (isset($_POST['clear_price_cache'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    \ProduktVerleih\StripeService::clear_all_lowest_price_caches();
    echo '<div class="notice notice-success"><p>✅ Preis-Cache wurde geleert.</p></div>';
}

// Get table structure
$table_variants = $wpdb->prefix . 'produkt_variants';

$variants_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_variants");

// Get sample data
$sample_variant = $wpdb->get_row("SELECT * FROM $table_variants LIMIT 1");
?>

<div class="wrap">
    <h1>🔧 Produkt Debug</h1>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>⚠️ Nur für Fehlerbehebung verwenden!</h3>
        <p>Diese Seite hilft bei der Diagnose von Datenbankproblemen.</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <p>
            <button type="submit" name="force_update" class="button button-primary" onclick="return confirm('Sind Sie sicher? Dies führt Datenbankänderungen durch.')">
                🔄 Datenbank reparieren
            </button>
        </p>
    </form>
    <form method="post" action="">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <p>
            <button type="submit" name="clear_price_cache" class="button button-secondary">
                🗑️ Preis-Cache löschen
            </button>
        </p>
    </form>
    
    <h2>📊 Datenbankstatus</h2>
    
    <h3>Variants Tabelle (<?php echo $table_variants; ?>)</h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Spaltenname</th>
                <th>Typ</th>
                <th>Null</th>
                <th>Standard</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($variants_columns as $column): ?>
            <tr>
                <td><strong><?php echo $column->Field; ?></strong></td>
                <td><?php echo $column->Type; ?></td>
                <td><?php echo $column->Null; ?></td>
                <td><?php echo $column->Default; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if ($sample_variant): ?>
    <h4>Beispiel-Datensatz:</h4>
    <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto;">
<?php print_r($sample_variant); ?>
    </pre>
    <?php endif; ?>

    
    <h2>🔍 Systeminfo</h2>
    <ul>
        <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
        <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
        <li><strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?></li>
        <li><strong>Plugin Version:</strong> <?php echo PRODUKT_VERSION; ?></li>
        <li><strong>Gespeicherte Version:</strong> <?php echo get_option('produkt_version', 'nicht gesetzt'); ?></li>
    </ul>
</div>
