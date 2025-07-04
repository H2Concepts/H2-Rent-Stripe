<?php
// Durations Tab Content
$table_name = $wpdb->prefix . 'federwiegen_durations';

// Ensure category_id column exists
$category_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
if (empty($category_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
}

// Handle form submissions
if (isset($_POST['submit_duration'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $months_minimum = intval($_POST['months_minimum']);
    $discount = floatval($_POST['discount']) / 100; // Convert percentage to decimal
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'months_minimum' => $months_minimum,
                'discount' => $discount,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%d', '%f', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich aktualisiert!</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'months_minimum' => $months_minimum,
                'discount' => $discount,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%d', '%f', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete_duration'])) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete_duration'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Mietdauer gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_duration'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_duration'])));
}

// Get all durations for selected category
$durations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, months_minimum", $selected_category));
?>

<div class="federwiegen-tab-section">
    <h3>⏰ Mietdauern</h3>
    <p>Definieren Sie verschiedene Mietdauern mit automatischen Rabatten bei längeren Laufzeiten.</p>
    
    <!-- Form -->
    <div class="federwiegen-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                <h4>Mietdauer bearbeiten</h4>
            <?php else: ?>
                <h4>Neue Mietdauer hinzufügen</h4>
            <?php endif; ?>
            
            <div class="federwiegen-form-grid">
                <div class="federwiegen-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <div class="federwiegen-form-group">
                    <label>Mindestmonate *</label>
                    <input type="number" name="months_minimum" value="<?php echo $edit_item ? $edit_item->months_minimum : ''; ?>" min="1" required>
                </div>
                
                <div class="federwiegen-form-group">
                    <label>Rabatt (%)</label>
                    <input type="number" name="discount" value="<?php echo $edit_item ? ($edit_item->discount * 100) : ''; ?>" step="0.01" min="0" max="100">
                    <small>z.B. 10 für 10% Rabatt</small>
                </div>
                
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="federwiegen-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_duration', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-pricing&category=' . $selected_category . '&tab=durations'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="federwiegen-list-card">
        <h4>Vorhandene Mietdauern</h4>
        
        <?php if (empty($durations)): ?>
        <div class="federwiegen-empty-state">
            <p>Noch keine Mietdauern für diese Kategorie vorhanden.</p>
            <p><strong>Tipp:</strong> Fügen Sie oben eine neue Mietdauer hinzu!</p>
        </div>
        <?php else: ?>
        
        <div class="federwiegen-simple-list">
            <?php foreach ($durations as $duration): ?>
            <div class="federwiegen-simple-item">
                <div class="federwiegen-simple-content">
                    <h5><?php echo esc_html($duration->name); ?></h5>
                    <div class="federwiegen-simple-meta">
                        <span>Mindestlaufzeit: <?php echo $duration->months_minimum; ?> Monat<?php echo $duration->months_minimum > 1 ? 'e' : ''; ?></span>
                        <?php if ($duration->discount > 0): ?>
                            <span class="federwiegen-discount-badge">-<?php echo round($duration->discount * 100); ?>% Rabatt</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="federwiegen-simple-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-pricing&category=' . $selected_category . '&tab=durations&edit_duration=' . $duration->id); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-pricing&category=' . $selected_category . '&tab=durations&delete_duration=' . $duration->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<style>
.federwiegen-discount-badge {
    background: #e3e8e3;
    color: #4a674a;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 500;
}
</style>
