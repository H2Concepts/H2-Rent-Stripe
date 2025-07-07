<?php
// Conditions Tab Content
$table_name = $wpdb->prefix . 'produkt_conditions';

// Handle form submissions
if (isset($_POST['submit_condition'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $price_modifier = floatval($_POST['price_modifier']) / 100; // Convert percentage to decimal
    $available = 1;
    $sort_order = intval($_POST['sort_order']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price_modifier' => $price_modifier,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%s', '%f', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Zustand erfolgreich aktualisiert!</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name' => $name,
                'description' => $description,
                'price_modifier' => $price_modifier,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%s', '%f', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Zustand erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete_condition'])) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete_condition'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Zustand gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_condition'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_condition'])));
}

// Get all conditions for selected category
$conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="produkt-tab-section">
    <h3>🔄 Zustände</h3>
    <p>Verwalten Sie Produktzustände (Neu/Aufbereitet) mit individuellen Preisanpassungen.</p>
    
    <!-- Form -->
    <div class="produkt-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                <h4>Zustand bearbeiten</h4>
            <?php else: ?>
                <h4>Neuen Zustand hinzufügen</h4>
            <?php endif; ?>
            
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <div class="produkt-form-group">
                    <label>Preisanpassung (%)</label>
                    <input type="number" name="price_modifier" value="<?php echo $edit_item ? ($edit_item->price_modifier * 100) : '0'; ?>" step="0.01" min="-100" max="100">
                    <small>z.B. -20 für 20% Rabatt, +10 für 10% Aufschlag</small>
                </div>
                
                <div class="produkt-form-group full-width">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3"><?php echo $edit_item ? esc_textarea($edit_item->description) : ''; ?></textarea>
                </div>
                
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="produkt-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_condition', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=conditions'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="produkt-list-card">
        <h4>Vorhandene Zustände</h4>
        
        <?php if (empty($conditions)): ?>
        <div class="produkt-empty-state">
            <p>Noch keine Zustände für diese Kategorie vorhanden.</p>
            <p><strong>Tipp:</strong> Fügen Sie oben einen neuen Zustand hinzu!</p>
        </div>
        <?php else: ?>
        
        <div class="produkt-simple-list">
            <?php foreach ($conditions as $condition): ?>
            <div class="produkt-simple-item">
                <div class="produkt-simple-content">
                    <h5><?php echo esc_html($condition->name); ?></h5>
                    <p><?php echo esc_html($condition->description); ?></p>
                    <div class="produkt-simple-meta">
                        <span class="produkt-price-modifier">
                            <?php 
                            $modifier = round($condition->price_modifier * 100, 2);
                            if ($modifier > 0) {
                                echo '<span style="color: #dc3232;">+' . $modifier . '%</span>';
                            } elseif ($modifier < 0) {
                                echo '<span style="color: #46b450;">' . $modifier . '%</span>';
                            } else {
                                echo '<span style="color: #666;">±0%</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="produkt-simple-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=conditions&edit_condition=' . $condition->id); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=conditions&delete_condition=' . $condition->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<style>
.produkt-simple-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.produkt-simple-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.produkt-simple-content h5 {
    margin: 0 0 8px 0;
    color: #3c434a;
}

.produkt-simple-content p {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.produkt-simple-meta {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.produkt-price-modifier {
    font-weight: 600;
}

.produkt-simple-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .produkt-simple-item {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .produkt-simple-actions {
        justify-content: center;
    }
}
</style>
