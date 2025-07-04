<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}federwiegen_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Ensure all new columns exist in variant_options table
$links_table = $wpdb->prefix . 'federwiegen_variant_options';

// Handle form submissions
if (isset($_POST['submit'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $variant_id = intval($_POST['variant_id']);
    $option_type = sanitize_text_field($_POST['option_type']);
    $option_id = intval($_POST['option_id']);
    $available = isset($_POST['available']) ? 1 : 0;

    $table_name = $wpdb->prefix . 'federwiegen_variant_options';

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'variant_id' => $variant_id,
                'option_type' => $option_type,
                'option_id' => $option_id,
                'available' => $available
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Zuordnung erfolgreich aktualisiert!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'variant_id' => $variant_id,
                'option_type' => $option_type,
                'option_id' => $option_id,
                'available' => $available
            ),
            array('%d', '%s', '%d', '%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Zuordnung erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $result = $wpdb->delete($wpdb->prefix . 'federwiegen_variant_options', array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Zuordnung gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_variant_options WHERE id = %d", intval($_GET['edit'])));
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));

// Get all data for dropdowns (filtered by category)
$variants = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
$conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
$product_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order, name", $selected_category));
$frame_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order, name", $selected_category));
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d ORDER BY sort_order, name", $selected_category));

// Get all variant options with names (filtered by category)
$variant_options = $wpdb->get_results($wpdb->prepare("
    SELECT vo.*, v.name as variant_name,
           CASE
               WHEN vo.option_type = 'condition' THEN c.name
               WHEN vo.option_type = 'product_color' THEN pc.name
               WHEN vo.option_type = 'frame_color' THEN fc.name
               WHEN vo.option_type = 'extra' THEN e.name
           END as option_name
    FROM {$wpdb->prefix}federwiegen_variant_options vo
    LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON vo.variant_id = v.id
    LEFT JOIN {$wpdb->prefix}federwiegen_conditions c ON vo.option_type = 'condition' AND vo.option_id = c.id
    LEFT JOIN {$wpdb->prefix}federwiegen_colors pc ON vo.option_type = 'product_color' AND vo.option_id = pc.id
    LEFT JOIN {$wpdb->prefix}federwiegen_colors fc ON vo.option_type = 'frame_color' AND vo.option_id = fc.id
    LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON vo.option_type = 'extra' AND vo.option_id = e.id
    WHERE v.category_id = %d
    ORDER BY v.name, vo.option_type, option_name
", $selected_category));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🔗</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Ausführungs-Optionen</h1>
            <p>Zustände, Farben & Extras verknüpfen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Ausführungs-Optionen</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-variant-options">
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
        <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Zuordnung
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
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
                if (empty($variants)):
                ?>
                <div class="federwiegen-tab-section">
                    <h3>⚠️ Keine Ausführungen vorhanden</h3>
                    <p>Bevor Sie Optionen zuordnen können, müssen Sie erst Ausführungen für diese Kategorie erstellen.</p>
                    <a href="<?php echo admin_url('admin.php?page=federwiegen-variants&category=' . $selected_category); ?>" class="button button-primary">Ausführungen verwalten</a>
                </div>
                <?php
                else:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔗 Neue Zuordnung hinzufügen</h3>
                    <p>Verknüpfen Sie Zustände, Farben und Extras mit spezifischen Ausführungen.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Ausführung *</label>
                                    <select name="variant_id" required>
                                        <option value="">Bitte wählen...</option>
                                        <?php foreach ($variants as $variant): ?>
                                        <option value="<?php echo $variant->id; ?>">
                                            <?php echo esc_html($variant->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Option-Typ *</label>
                                    <select name="option_type" id="option_type" required onchange="updateOptionsList()">
                                        <option value="">Bitte wählen...</option>
                                        <?php if (!empty($conditions)): ?>
                                        <option value="condition">🔄 Zustand</option>
                                        <?php endif; ?>
                                        <?php if (!empty($product_colors)): ?>
                                        <option value="product_color">🎨 Produktfarbe</option>
                                        <?php endif; ?>
                                        <?php if (!empty($frame_colors)): ?>
                                        <option value="frame_color">🖼️ Gestellfarbe</option>
                                        <?php endif; ?>
                                        <?php if (!empty($extras)): ?>
                                        <option value="extra">➕ Extra</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Option *</label>
                                    <select name="option_id" id="option_id" required>
                                        <option value="">Erst Option-Typ wählen...</option>
                                    </select>
                                </div>

                                <div class="federwiegen-form-group">
                                    <label class="federwiegen-toggle-label">
                                        <input type="checkbox" name="available" value="1" checked>
                                        <span class="federwiegen-toggle-slider"></span>
                                        <span>Verfügbar</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Hinzufügen', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                endif;
                break;
                
            case 'edit':
                if ($edit_item):
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔗 Zuordnung bearbeiten</h3>
                    <p>Bearbeiten Sie die Verknüpfung zwischen Ausführung und Option (Zustand/Farbe/Extra).</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <input type="hidden" name="id" value="<?php echo $edit_item->id; ?>">
                            
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Ausführung *</label>
                                    <select name="variant_id" required>
                                        <option value="">Bitte wählen...</option>
                                        <?php foreach ($variants as $variant): ?>
                                        <option value="<?php echo $variant->id; ?>" <?php selected($edit_item->variant_id, $variant->id); ?>>
                                            <?php echo esc_html($variant->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Option-Typ *</label>
                                    <select name="option_type" id="option_type" required onchange="updateOptionsList()">
                                        <option value="">Bitte wählen...</option>
                                        <?php if (!empty($conditions)): ?>
                                        <option value="condition" <?php selected($edit_item->option_type, 'condition'); ?>>🔄 Zustand</option>
                                        <?php endif; ?>
                                        <?php if (!empty($product_colors)): ?>
                                        <option value="product_color" <?php selected($edit_item->option_type, 'product_color'); ?>>🎨 Produktfarbe</option>
                                        <?php endif; ?>
                                        <?php if (!empty($frame_colors)): ?>
                                        <option value="frame_color" <?php selected($edit_item->option_type, 'frame_color'); ?>>🖼️ Gestellfarbe</option>
                                        <?php endif; ?>
                                        <?php if (!empty($extras)): ?>
                                        <option value="extra" <?php selected($edit_item->option_type, 'extra'); ?>>➕ Extra</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Option *</label>
                                    <select name="option_id" id="option_id" required>
                                        <option value="">Erst Option-Typ wählen...</option>
                                    </select>
                                </div>

                                <div class="federwiegen-form-group">
                                    <label class="federwiegen-toggle-label">
                                        <input type="checkbox" name="available" value="1" <?php echo ($edit_item->available ?? 1) ? 'checked' : ''; ?>>
                                        <span class="federwiegen-toggle-slider"></span>
                                        <span>Verfügbar</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Aktualisieren', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                else:
                    echo '<div class="federwiegen-tab-section"><p>Zuordnung nicht gefunden.</p></div>';
                endif;
                break;
                
            case 'list':
            default:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔗 Ausführungs-Optionen</h3>
                    <p>Verknüpfen Sie Zustände, Farben und Extras mit spezifischen Ausführungen.</p>
                    
                    <div class="federwiegen-list-card">
                        <h4>Zuordnungen für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h4>
                        
                        <?php if (empty($variant_options)): ?>
                        <div class="federwiegen-empty-state">
                            <p>Noch keine Zuordnungen für diese Kategorie vorhanden.</p>
                            <?php if (!empty($variants)): ?>
                            <p><strong>Tipp:</strong> Fügen Sie eine neue Zuordnung hinzu!</p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        
                        <?php 
                        // Group by variant
                        $grouped_options = array();
                        foreach ($variant_options as $option) {
                            $grouped_options[$option->variant_name][] = $option;
                        }
                        ?>
                        
                        <?php foreach ($grouped_options as $variant_name => $options): ?>
                        <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                            <h5>📦 <?php echo esc_html($variant_name); ?></h5>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px;">
                                <?php foreach ($options as $option): ?>
                                <div style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 15px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <strong>
                                                <?php
                                                switch ($option->option_type) {
                                                    case 'condition':
                                                        echo '🔄 ' . esc_html($option->option_name);
                                                        break;
                                                    case 'product_color':
                                                        echo '🎨 ' . esc_html($option->option_name);
                                                        break;
                                                    case 'frame_color':
                                                        echo '🖼️ ' . esc_html($option->option_name);
                                                        break;
                                                    case 'extra':
                                                        echo '➕ ' . esc_html($option->option_name);
                                                        break;
                                                }
                                                ?>
                                            </strong>
                                            <?php if ($option->available ?? 1): ?>
                                                <span class="federwiegen-status-badge available">✅ Verfügbar</span>
                                            <?php else: ?>
                                                <span class="federwiegen-status-badge unavailable">❌ Nicht verfügbar</span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=edit&edit=' . $option->id); ?>" class="button button-small">✏️</a>
                                            <a href="<?php echo admin_url('admin.php?page=federwiegen-variant-options&category=' . $selected_category . '&tab=list&delete=' . $option->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">🗑️</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
                <?php
        }
        ?>
    </div>
</div>

<style>
.federwiegen-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.federwiegen-status-badge.available {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.federwiegen-status-badge.unavailable {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
// Options data from PHP
const conditionsData = <?php echo json_encode($conditions); ?>;
const productColorsData = <?php echo json_encode($product_colors); ?>;
const frameColorsData = <?php echo json_encode($frame_colors); ?>;
const extrasData = <?php echo json_encode($extras); ?>;
const editItem = <?php echo json_encode($edit_item); ?>;

function updateOptionsList() {
    const optionType = document.getElementById('option_type').value;
    const optionSelect = document.getElementById('option_id');
    
    // Clear current options
    optionSelect.innerHTML = '<option value="">Bitte wählen...</option>';
    
    let optionsData = [];
    
    switch (optionType) {
        case 'condition':
            optionsData = conditionsData;
            break;
        case 'product_color':
            optionsData = productColorsData;
            break;
        case 'frame_color':
            optionsData = frameColorsData;
            break;
        case 'extra':
            optionsData = extrasData;
            break;
    }
    
    // Add options
    optionsData.forEach(function(option) {
        const optionElement = document.createElement('option');
        optionElement.value = option.id;
        optionElement.textContent = option.name;
        
        // Select if editing
        if (editItem && editItem.option_type === optionType && editItem.option_id == option.id) {
            optionElement.selected = true;
        }
        
        optionSelect.appendChild(optionElement);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (editItem) {
        updateOptionsList();
    }
});
</script>
