<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'federwiegen_conditions';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Handle form submissions
if (isset($_POST['submit'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $price_modifier = floatval($_POST['price_modifier']) / 100; // Convert percentage to decimal
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
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
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
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Zustand gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
    }
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));

// Get all conditions for selected category
$conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🔄</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Zustände verwalten</h1>
            <p>Produktzustände & Preisanpassungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Zustände</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-conditions">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Kategorie:</strong></label>
            <select name="category" id="category-select" onchange="this.form.submit()">
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>>
                    <?php echo esc_html($category->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <noscript><input type="submit" value="Wechseln" class="button"></noscript>
        </form>
        
        <?php if ($current_category): ?>
        <div class="federwiegen-category-info">
            <code>[federwiegen_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab Navigation -->
    <div class="federwiegen-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neuer Zustand
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
            ✏️ Bearbeiten
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="federwiegen-tab-content">
        <?php
        switch ($active_tab) {
            case 'add':
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔄 Neuen Zustand hinzufügen</h3>
                    <p>Erstellen Sie einen neuen Produktzustand mit individueller Preisanpassung.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Name *</label>
                                    <input type="text" name="name" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Preisanpassung (%)</label>
                                    <input type="number" name="price_modifier" value="0" step="0.01" min="-100" max="100">
                                    <small>z.B. -20 für 20% Rabatt, +10 für 10% Aufschlag</small>
                                </div>
                                
                                <div class="federwiegen-form-group full-width">
                                    <label>Beschreibung</label>
                                    <textarea name="description" rows="3"></textarea>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Sortierung</label>
                                    <input type="number" name="sort_order" value="0" min="0">
                                </div>
                                
            
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Hinzufügen', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                break;
                
            case 'edit':
                if ($edit_item):
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔄 Zustand bearbeiten</h3>
                    <p>Bearbeiten Sie die Eigenschaften des Zustands.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                            
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Name *</label>
                                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Preisanpassung (%)</label>
                                    <input type="number" name="price_modifier" value="<?php echo ($edit_item->price_modifier * 100); ?>" step="0.01" min="-100" max="100">
                                    <small>z.B. -20 für 20% Rabatt, +10 für 10% Aufschlag</small>
                                </div>
                                
                                <div class="federwiegen-form-group full-width">
                                    <label>Beschreibung</label>
                                    <textarea name="description" rows="3"><?php echo esc_textarea($edit_item->description); ?></textarea>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Sortierung</label>
                                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                                </div>
                                
                               
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Aktualisieren', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                else:
                    echo '<div class="federwiegen-tab-section"><p>Zustand nicht gefunden.</p></div>';
                endif;
                break;
                
            case 'list':
            default:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔄 Zustände</h3>
                    <p>Verwalten Sie Produktzustände (Neu/Aufbereitet) mit individuellen Preisanpassungen.</p>
                    
                    <div class="federwiegen-list-card">
                        <h4>Zustände für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h4>
                        
                        <?php if (empty($conditions)): ?>
                        <div class="federwiegen-empty-state">
                            <p>Noch keine Zustände für diese Kategorie vorhanden.</p>
                            <p><strong>Tipp:</strong> Fügen Sie einen neuen Zustand hinzu!</p>
                        </div>
                        <?php else: ?>
                        
                        <div class="federwiegen-items-grid">
                            <?php foreach ($conditions as $condition): ?>
                            <div class="federwiegen-item-card">
                                <div class="federwiegen-item-content">
                                    <h5><?php echo esc_html($condition->name); ?></h5>
                                    <p><?php echo esc_html($condition->description); ?></p>
                                    <div class="federwiegen-item-meta">
                                        <span class="federwiegen-price">
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
                                
                                <div class="federwiegen-item-actions">
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=edit&edit=' . $condition->id); ?>" class="button button-small">Bearbeiten</a>
                                    <a href="<?php echo admin_url('admin.php?page=federwiegen-conditions&category=' . $selected_category . '&tab=list&delete=' . $condition->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                <?php
        }
        ?>
    </div>
</div>
