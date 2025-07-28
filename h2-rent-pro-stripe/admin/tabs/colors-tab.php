<?php
// Colors Tab Content
$table_name = $wpdb->prefix . 'produkt_colors';

// Handle form submissions
if (isset($_POST['submit_color'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $color_code = sanitize_hex_color($_POST['color_code']);
    $color_type = sanitize_text_field($_POST['color_type']);
    $available = 1;
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'available' => $available,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Farbe erfolgreich aktualisiert!</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'available' => $available,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Farbe erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete_color'])) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete_color'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Farbe gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_color'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_color'])));
}

// Get all colors for selected category, separated by type
$product_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order, name", $selected_category));
$frame_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order, name", $selected_category));
?>

<div class="produkt-tab-section">
    <h3>🎨 Farben</h3>
    <p>Verwalten Sie Produkt- und Gestellfarben für Ihre Produkt.</p>
    
    <!-- Form -->
    <div class="produkt-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                <h4>Farbe bearbeiten</h4>
            <?php else: ?>
                <h4>Neue Farbe hinzufügen</h4>
            <?php endif; ?>
            
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Farbtyp *</label>
                    <select name="color_type" required>
                        <option value="product" <?php selected($edit_item ? $edit_item->color_type : 'product', 'product'); ?>>🎨 Produktfarbe</option>
                        <option value="frame" <?php selected($edit_item ? $edit_item->color_type : 'product', 'frame'); ?>>🖼️ Gestellfarbe</option>
                    </select>
                </div>
                
                <div class="produkt-form-group">
                    <label>Farbname *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <div class="produkt-form-group">
                    <label>Farbcode *</label>
                    <input type="color" name="color_code" value="<?php echo $edit_item ? esc_attr($edit_item->color_code) : '#FFFFFF'; ?>" required>
                </div>
                
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="produkt-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_color', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Lists -->
    <div class="produkt-colors-sections">
        <!-- Product Colors -->
        <div class="produkt-color-section">
            <h4>🎨 Produktfarben</h4>
            
            <?php if (empty($product_colors)): ?>
            <div class="produkt-empty-state">
                <p>Noch keine Produktfarben vorhanden.</p>
            </div>
            <?php else: ?>
            
            <div class="produkt-colors-grid">
                <?php foreach ($product_colors as $color): ?>
                <div class="produkt-color-item">
                    <div class="produkt-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                    <div class="produkt-color-info">
                        <h5><?php echo esc_html($color->name); ?></h5>
                        <code><?php echo esc_html($color->color_code); ?></code>
                        
                    </div>
                    <div class="produkt-color-actions">
                        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors&edit_color=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors&delete_color=' . $color->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- Frame Colors -->
        <div class="produkt-color-section">
            <h4>🖼️ Gestellfarben</h4>
            
            <?php if (empty($frame_colors)): ?>
            <div class="produkt-empty-state">
                <p>Noch keine Gestellfarben vorhanden.</p>
            </div>
            <?php else: ?>
            
            <div class="produkt-colors-grid">
                <?php foreach ($frame_colors as $color): ?>
                <div class="produkt-color-item">
                    <div class="produkt-color-preview" style="background-color: <?php echo esc_attr($color->color_code); ?>;"></div>
                    <div class="produkt-color-info">
                        <h5><?php echo esc_html($color->name); ?></h5>
                        <code><?php echo esc_html($color->color_code); ?></code>
                    </div>
                    <div class="produkt-color-actions">
                        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors&edit_color=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                        <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=colors&delete_color=' . $color->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</div>


