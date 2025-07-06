<?php
// Extras Tab Content
$table_name = $wpdb->prefix . 'federwiegen_extras';
// Ensure stripe_price_id column exists
$price_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id'");
if (empty($price_id_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT '' AFTER name");
}

// Handle form submissions
if (isset($_POST['submit_extra'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $stripe_price_id = sanitize_text_field($_POST['stripe_price_id']);
    $image_url = esc_url_raw($_POST['image_url']);
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'stripe_price_id' => $stripe_price_id,
                'image_url' => $image_url,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%s', '%s', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'stripe_price_id' => $stripe_price_id,
                'image_url' => $image_url,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete_extra'])) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete_extra'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Extra gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_extra'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_extra'])));
}

// Get all extras for selected category
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="federwiegen-tab-section">
    <h3>🎁 Extras mit Bildern</h3>
    <p>Verwalten Sie Zusatzoptionen mit Bildern, die über dem Hauptbild angezeigt werden.</p>
    
    <!-- Form -->
    <div class="federwiegen-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
                <h4>Extra bearbeiten</h4>
            <?php else: ?>
                <h4>Neues Extra hinzufügen</h4>
            <?php endif; ?>
            
            <div class="federwiegen-form-grid">
                <div class="federwiegen-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <div class="federwiegen-form-group">
                    <label>Stripe Preis ID *</label>
                    <input type="text" name="stripe_price_id" value="<?php echo $edit_item ? esc_attr($edit_item->stripe_price_id) : ''; ?>" required>
                </div>
                
                <div class="federwiegen-form-group full-width">
                    <label>📸 Extra-Bild</label>
                    <div class="federwiegen-media-upload">
                        <input type="url" name="image_url" id="image_url" value="<?php echo $edit_item ? esc_attr($edit_item->image_url ?? '') : ''; ?>" placeholder="https://example.com/extra-bild.jpg">
                        <button type="button" class="button federwiegen-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                    </div>
                    <small>Wird als Overlay über dem Hauptbild angezeigt</small>
                    <?php if ($edit_item && !empty($edit_item->image_url)): ?>
                        <div class="federwiegen-image-preview">
                            <img src="<?php echo esc_url($edit_item->image_url); ?>" alt="Extra-Bild">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="federwiegen-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="federwiegen-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_extra', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-products&category=' . $selected_category . '&tab=extras'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="federwiegen-list-card">
        <h4>Vorhandene Extras</h4>
        
        <?php if (empty($extras)): ?>
        <div class="federwiegen-empty-state">
            <p>Noch keine Extras für diese Kategorie vorhanden.</p>
            <p><strong>Tipp:</strong> Fügen Sie oben ein neues Extra hinzu!</p>
        </div>
        <?php else: ?>
        
        <div class="federwiegen-items-grid">
            <?php foreach ($extras as $extra): ?>
            <div class="federwiegen-item-card">
                <div class="federwiegen-item-images">
                    <?php 
                    $image_url = isset($extra->image_url) ? $extra->image_url : '';
                    if (!empty($image_url)): 
                    ?>
                        <img src="<?php echo esc_url($image_url); ?>" class="federwiegen-main-image" alt="<?php echo esc_attr($extra->name); ?>">
                    <?php else: ?>
                        <div class="federwiegen-placeholder">🎁</div>
                    <?php endif; ?>
                </div>
                
                <div class="federwiegen-item-content">
                    <h5><?php echo esc_html($extra->name); ?></h5>
                    <div class="federwiegen-item-meta">
                        <?php if (!empty($extra->stripe_price_id)) {
                            $p = \FederwiegenVerleih\StripeService::get_price_amount($extra->stripe_price_id);
                            if (!is_wp_error($p)) {
                                echo '<span class="federwiegen-price">' . number_format($p, 2, ',', '.') . '€</span>';
                            }
                        } ?>
                    </div>
                </div>
                
                <div class="federwiegen-item-actions">
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-products&category=' . $selected_category . '&tab=extras&edit_extra=' . $extra->id); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-products&category=' . $selected_category . '&tab=extras&delete_extra=' . $extra->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>
