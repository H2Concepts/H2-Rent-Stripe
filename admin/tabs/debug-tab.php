<?php
// Debug Tab Content

// Force database update if requested
if (isset($_POST['force_update'])) {
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

// Uninstall plugin if requested
if (isset($_POST['plugin_uninstall'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    \ProduktVerleih\Plugin::uninstall_plugin();
    echo '<div class="notice notice-success"><p>✅ Plugin-Daten wurden entfernt. Bitte deaktivieren Sie das Plugin manuell.</p></div>';
}

// Manual Stripe sync
if (isset($_POST['manual_stripe_sync'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $res = \ProduktVerleih\StripeService::sync_all();
    if (is_wp_error($res)) {
        echo '<div class="notice notice-error"><p>❌ Sync Fehler: ' . esc_html($res->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>✅ Stripe Sync abgeschlossen</p></div>';
    }
}

// Clear Stripe status cache
if (isset($_POST['clear_stripe_cache']) && check_admin_referer('clear_stripe_cache_action')) {
    \ProduktVerleih\StripeService::clear_stripe_archive_cache();
    echo '<div class="notice notice-success"><p>✅ Stripe-Caches wurden geleert.</p></div>';
}

// Clear price transients
if (isset($_POST['clear_price_cache']) && check_admin_referer('clear_price_cache_action')) {
    \ProduktVerleih\StripeService::clear_price_cache();
    echo '<div class="notice notice-success"><p>✅ Preis-Cache geleert.</p></div>';
}

// Cleanup orphaned products
if (isset($_POST['run_cleanup']) && check_admin_referer('cleanup_action')) {
    $cleanup_tables = [
        'produkt_variants',
        'produkt_extras',
        'produkt_durations',
    ];

    foreach ($cleanup_tables as $tbl) {
        $table = $wpdb->prefix . $tbl;
        $wpdb->query(
            "DELETE FROM {$table} WHERE category_id NOT IN (
                SELECT id FROM {$wpdb->prefix}produkt_categories
            )"
        );
    }

    echo '<div class="notice notice-success"><p>✅ Verwaiste Produkte bereinigt.</p></div>';
}

if (isset($_POST['run_hard_cleanup']) && check_admin_referer('produkt_cleanup_action')) {
    global $wpdb;
    $prefix = $wpdb->prefix;

    $wpdb->query("DELETE FROM {$prefix}produkt_product_to_category WHERE produkt_id NOT IN (SELECT id FROM {$prefix}produkt_categories)");

    $wpdb->query(
        "DELETE FROM {$prefix}produkt_duration_prices WHERE duration_id NOT IN (
            SELECT id FROM {$prefix}produkt_durations
        ) OR variant_id NOT IN (
            SELECT id FROM {$prefix}produkt_variants
        )"
    );

    $wpdb->query("DELETE FROM {$prefix}produkt_categories WHERE id NOT IN (SELECT produkt_id FROM {$prefix}produkt_product_to_category)");

    echo '<div class="notice notice-success"><p>✅ Verwaiste Einträge erfolgreich bereinigt.</p></div>';
}

// Get table structure
$table_variants = $wpdb->prefix . 'produkt_variants';

$variants_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_variants");

// Get sample data
$sample_variant = $wpdb->get_row("SELECT * FROM $table_variants LIMIT 1");
?>

<div class="produkt-debug-tab">
    <div class="produkt-debug-warning">
        <h3>⚠️ Nur für Fehlerbehebung verwenden!</h3>
        <p>Diese Seite hilft bei der Diagnose von Datenbankproblemen und sollte nur von Administratoren verwendet werden.</p>
    </div>
    
    <div class="produkt-debug-actions">
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <button type="submit" name="force_update" class="button button-primary" onclick="return confirm('Sind Sie sicher? Dies führt Datenbankänderungen durch.')">
                🔄 Datenbank reparieren
            </button>
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <button type="submit" name="plugin_uninstall" class="button button-secondary" onclick="return confirm('Plugin und alle Daten wirklich löschen?')">
                🗑️ Plugin-Daten löschen
            </button>
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <button type="submit" name="manual_stripe_sync" class="button button-primary">
                🔁 Stripe Sync starten
            </button>
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('clear_stripe_cache_action'); ?>
            <p><strong>Stripe-Status-Cache leeren:</strong> Dies erzwingt eine erneute Prüfung der Stripe-Archivierung für Produkte und Preise (Mietdauer, Extras, Ausführungen).</p>
            <input type="submit" name="clear_stripe_cache" class="button button-secondary" value="Stripe-Status neu prüfen">
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('clear_price_cache_action'); ?>
            <p><strong>Preis-Cache leeren:</strong> Entfernt alle gespeicherten Transient-Werte für Preise.</p>
            <input type="submit" name="clear_price_cache" class="button button-secondary" value="Preis-Cache löschen">
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('cleanup_action'); ?>
            <input type="submit" name="run_cleanup" class="button button-secondary" value="🧹 Cleanup nicht mehr verknüpfter Datensätze">
        </form>
        <form method="post" action="">
            <?php wp_nonce_field('produkt_cleanup_action'); ?>
            <p><strong>🧹 Verwaiste Daten bereinigen:</strong> Entfernt z. B. Kategorie-Zuordnungen gelöschter Produkte.</p>
            <input type="submit" name="run_hard_cleanup" class="button button-secondary" value="Jetzt bereinigen">
        </form>
        <?php if (wp_next_scheduled('produkt_stripe_status_cron')): ?>
            <p>Nächster automatischer Stripe-Archiv-Check: <?php echo date('d.m.Y H:i:s', wp_next_scheduled('produkt_stripe_status_cron')); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="produkt-debug-sections">
        <!-- Variants Table -->
        <div class="produkt-debug-section">
            <h4>📊 Variants Tabelle (<?php echo $table_variants; ?>)</h4>
            <div class="produkt-debug-table">
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
            <div class="produkt-debug-sample">
                <h5>Beispiel-Datensatz:</h5>
                <pre><?php print_r($sample_variant); ?></pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- Settings Table removed: table not used anymore -->
        
        <!-- System Info -->
        <div class="produkt-debug-section">
            <h4>🔍 Systeminfo</h4>
            <div class="produkt-debug-info">
                <ul>
                    <li><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong>MySQL Version:</strong> <?php echo $wpdb->db_version(); ?></li>
                    <li><strong>Plugin Version:</strong> <?php echo defined('PRODUKT_VERSION') ? PRODUKT_VERSION : 'Unbekannt'; ?></li>
                    <li><strong>Gespeicherte Version:</strong> <?php echo get_option('produkt_version', 'nicht gesetzt'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>


