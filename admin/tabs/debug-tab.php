<?php
// Debug Tab Content

// Force database update if requested
if (isset($_POST['force_update'])) {
    $table_variants = $wpdb->prefix . 'federwiegen_variants';
    
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

// Get table structure
$table_variants = $wpdb->prefix . 'federwiegen_variants';

$variants_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_variants");

// Get sample data
$sample_variant = $wpdb->get_row("SELECT * FROM $table_variants LIMIT 1");
?>

<div class="federwiegen-debug-tab">
    <div class="federwiegen-debug-warning">
        <h3>⚠️ Nur für Fehlerbehebung verwenden!</h3>
        <p>Diese Seite hilft bei der Diagnose von Datenbankproblemen und sollte nur von Administratoren verwendet werden.</p>
    </div>
    
    <div class="federwiegen-debug-actions">
        <form method="post" action="">
            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
            <button type="submit" name="force_update" class="button button-primary" onclick="return confirm('Sind Sie sicher? Dies führt Datenbankänderungen durch.')">
                🔄 Datenbank reparieren
            </button>
        </form>
    </div>
    
    <div class="federwiegen-debug-sections">
        <!-- Variants Table -->
        <div class="federwiegen-debug-section">
            <h4>📊 Variants Tabelle (<?php echo $table_variants; ?>)</h4>
            <div class="federwiegen-debug-table">
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
            </div>
            
            <?php if ($sample_variant): ?>
            <div class="federwiegen-debug-sample">
                <h5>Beispiel-Datensatz:</h5>
                <pre><?php print_r($sample_variant); ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Settings Table removed: table not used anymore -->
        
        <!-- System Info -->
        <div class="federwiegen-debug-section">
            <h4>🔍 Systeminfo</h4>
            <div class="federwiegen-debug-info">
                <ul>
                    <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?></li>
                    <li><strong>Plugin Version:</strong> <?php echo defined('FEDERWIEGEN_VERSION') ? FEDERWIEGEN_VERSION : 'Unbekannt'; ?></li>
                    <li><strong>Gespeicherte Version:</strong> <?php echo get_option('federwiegen_version', 'nicht gesetzt'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.federwiegen-debug-tab {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.federwiegen-debug-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 15px;
}

.federwiegen-debug-warning h3 {
    margin: 0 0 10px 0;
    color: #856404;
}

.federwiegen-debug-actions {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
}

.federwiegen-debug-sections {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.federwiegen-debug-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.federwiegen-debug-section h4 {
    margin: 0 0 15px 0;
    color: #3c434a;
}

.federwiegen-debug-table {
    margin-bottom: 15px;
}

.federwiegen-debug-sample {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 15px;
}

.federwiegen-debug-sample h5 {
    margin: 0 0 10px 0;
    color: #3c434a;
}

.federwiegen-debug-sample pre {
    background: #f1f1f1;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}

.federwiegen-debug-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 15px;
}

.federwiegen-debug-info ul {
    margin: 0;
    padding-left: 20px;
}

.federwiegen-debug-info li {
    margin-bottom: 5px;
}
</style>
