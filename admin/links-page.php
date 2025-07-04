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

// Ensure all new columns exist in links table
$links_table = $wpdb->prefix . 'federwiegen_links';
$new_columns = array(
    'condition_id' => 'mediumint(9)',
    'product_color_id' => 'mediumint(9)',
    'frame_color_id' => 'mediumint(9)'
);

foreach ($new_columns as $column => $type) {
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $links_table LIKE '$column'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $links_table ADD COLUMN $column $type AFTER duration_id");
    }
}

// Handle form submissions
if (isset($_POST['submit'])) {
    \FederwiegenVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $variant_id = intval($_POST['variant_id']);
    $extra_ids_raw = isset($_POST['extra_ids']) ? sanitize_text_field($_POST['extra_ids']) : '';
    $extra_ids_array = array_filter(array_map('intval', explode(',', $extra_ids_raw)));
    sort($extra_ids_array);
    $extra_ids_raw = implode(',', $extra_ids_array);
    $extra_id = !empty($extra_ids_array) ? $extra_ids_array[0] : 0;
    $duration_id = intval($_POST['duration_id']);
    $condition_id = !empty($_POST['condition_id']) ? intval($_POST['condition_id']) : null;
    $product_color_id = !empty($_POST['product_color_id']) ? intval($_POST['product_color_id']) : null;
    $frame_color_id = !empty($_POST['frame_color_id']) ? intval($_POST['frame_color_id']) : null;
    $stripe_link = esc_url_raw($_POST['stripe_link']);

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $links_table,
            array(
                'category_id' => $category_id,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'stripe_link' => $stripe_link
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Stripe Link erfolgreich aktualisiert!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $links_table,
            array(
                'category_id' => $category_id,
                'variant_id' => $variant_id,
                'extra_id' => $extra_id,
                'extra_ids' => $extra_ids_raw,
                'duration_id' => $duration_id,
                'condition_id' => $condition_id,
                'product_color_id' => $product_color_id,
                'frame_color_id' => $frame_color_id,
                'stripe_link' => $stripe_link
            ),
            array('%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s')
        );
        
        if ($result === false) {
            echo '<div class="notice notice-error"><p>❌ Fehler: Diese Kombination existiert bereits für diese Kategorie!</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>✅ Stripe Link erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $result = $wpdb->delete($links_table, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Stripe Link gelöscht!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
    }
}

// Handle duplicate - prefill add form with existing values
$duplicate_item = null;
if (isset($_GET['duplicate']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'federwiegen_admin_action')) {
    $duplicate_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $links_table WHERE id = %d", intval($_GET['duplicate'])));
    if ($duplicate_item) {
        $selected_category = $duplicate_item->category_id;
        $active_tab = 'add';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $links_table WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
    }
}

// Get current category info
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_categories WHERE id = %d", $selected_category));

// Get all data for dropdowns (filtered by category)
$variants = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_variants WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_extras WHERE category_id = %d ORDER BY sort_order, name", $selected_category));

// Build all extra combinations
$extra_combinations = array();
if (!empty($extras)) {
    $ids = array_map(function($e) { return $e->id; }, $extras);
    $names = array_column($extras, 'name', 'id');
    $count = count($ids);
    for ($i = 1; $i < (1 << $count); $i++) {
        $combo_ids = array();
        $combo_names = array();
        for ($j = 0; $j < $count; $j++) {
            if ($i & (1 << $j)) {
                $combo_ids[] = $ids[$j];
                $combo_names[] = $names[$ids[$j]];
            }
        }
        sort($combo_ids);
        $extra_combinations[] = array(
            'ids' => implode(',', $combo_ids),
            'name' => implode(' + ', $combo_names)
        );
    }
}
$durations = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_durations WHERE category_id = %d ORDER BY sort_order, months_minimum", $selected_category));
$conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_conditions WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
$product_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order, name", $selected_category));
$frame_colors = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}federwiegen_colors WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order, name", $selected_category));

// Get all links with names (filtered by category)
$links = $wpdb->get_results($wpdb->prepare(
    "SELECT l.*, v.name as variant_name,
            GROUP_CONCAT(e.name ORDER BY e.name SEPARATOR ' + ') AS extras_names,
            d.name as duration_name,
            cond.name as condition_name,
            pc.name as product_color_name,
            fc.name as frame_color_name
     FROM $links_table l
     LEFT JOIN {$wpdb->prefix}federwiegen_variants v ON l.variant_id = v.id
     LEFT JOIN {$wpdb->prefix}federwiegen_extras e ON FIND_IN_SET(e.id, l.extra_ids)
     LEFT JOIN {$wpdb->prefix}federwiegen_durations d ON l.duration_id = d.id
     LEFT JOIN {$wpdb->prefix}federwiegen_conditions cond ON l.condition_id = cond.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors pc ON l.product_color_id = pc.id
     LEFT JOIN {$wpdb->prefix}federwiegen_colors fc ON l.frame_color_id = fc.id
     WHERE l.category_id = %d
     GROUP BY l.id
     ORDER BY v.name, extras_names, d.name",
    $selected_category
));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="federwiegen-admin-header-compact">
        <div class="federwiegen-admin-logo-compact">🔗</div>
        <div class="federwiegen-admin-title-compact">
            <h1>Stripe Links verwalten</h1>
            <p>Zahlungsverknüpfungen</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="federwiegen-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=federwiegen-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Stripe Links</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="federwiegen-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="federwiegen-links">
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
        <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=list'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=add'); ?>" 
           class="federwiegen-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neuer Link
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
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
                if (empty($variants) || empty($durations)):
                ?>
                <div class="federwiegen-tab-section">
                    <h3>⚠️ Kategorie noch nicht vollständig konfiguriert</h3>
                    <p>Bevor Sie Stripe Links erstellen können, müssen Sie für diese Kategorie folgende Daten hinterlegen:</p>
                    <ul>
                        <?php if (empty($variants)): ?><li>❌ <a href="<?php echo admin_url('admin.php?page=federwiegen-variants&category=' . $selected_category); ?>">Ausführungen hinzufügen</a></li><?php endif; ?>
                        <?php if (empty($durations)): ?><li>❌ <a href="<?php echo admin_url('admin.php?page=federwiegen-durations&category=' . $selected_category); ?>">Mietdauern hinzufügen</a></li><?php endif; ?>
                    </ul>
                </div>
                <?php
                else:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔗 Neuen Stripe Link hinzufügen</h3>
                    <p>Verknüpfen Sie alle Produktkombinationen mit den entsprechenden Stripe-Zahlungslinks.</p>
                    
                    <div class="federwiegen-form-card">
                        <form method="post" action="">
                            <?php wp_nonce_field('federwiegen_admin_action', 'federwiegen_admin_nonce'); ?>
                            <div class="federwiegen-form-grid">
                                <div class="federwiegen-form-group">
                                    <label>Ausführung *</label>
                                    <select name="variant_id" required>
                                        <option value="">Bitte wählen...</option>
                                        <?php foreach ($variants as $variant): ?>
                                        <option value="<?php echo $variant->id; ?>" <?php echo ($duplicate_item && $duplicate_item->variant_id == $variant->id) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($variant->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Extra</label>
                                    <select name="extra_ids">
                                        <option value="">Kein Extra</option>
                                        <?php foreach ($extra_combinations as $combo): ?>
                                        <option value="<?php echo esc_attr($combo['ids']); ?>" <?php echo ($duplicate_item && $duplicate_item->extra_ids == $combo['ids']) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($combo['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Mietdauer *</label>
                                    <select name="duration_id" required>
                                        <option value="">Bitte wählen...</option>
                                        <?php foreach ($durations as $duration): ?>
                                        <option value="<?php echo $duration->id; ?>" <?php echo ($duplicate_item && $duplicate_item->duration_id == $duration->id) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($duration->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (!empty($conditions)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Zustand</label>
                                    <select name="condition_id">
                                        <option value="">Alle Zustände</option>
                                        <?php foreach ($conditions as $condition): ?>
                                        <option value="<?php echo $condition->id; ?>" <?php echo ($duplicate_item && $duplicate_item->condition_id == $condition->id) ? 'selected' : ''; ?> >
                                            <?php echo esc_html($condition->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifischer Zustand</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product_colors)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Produktfarbe</label>
                                    <select name="product_color_id">
                                        <option value="">Alle Produktfarben</option>
                                        <?php foreach ($product_colors as $color): ?>
                                        <option value="<?php echo $color->id; ?>" <?php echo ($duplicate_item && $duplicate_item->product_color_id == $color->id) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($color->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifische Produktfarbe</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($frame_colors)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Gestellfarbe</label>
                                    <select name="frame_color_id">
                                        <option value="">Alle Gestellfarben</option>
                                        <?php foreach ($frame_colors as $color): ?>
                                        <option value="<?php echo $color->id; ?>" <?php echo ($duplicate_item && $duplicate_item->frame_color_id == $color->id) ? 'selected' : ''; ?>>
                                            <?php echo esc_html($color->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifische Gestellfarbe</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="federwiegen-form-group full-width">
                                    <label>Stripe Link *</label>
                                    <input type="url" name="stripe_link" value="<?php echo $duplicate_item ? esc_attr($duplicate_item->stripe_link) : ''; ?>" required placeholder="https://buy.stripe.com/...">
                                    <small>Vollständiger Stripe-Link</small>
                                </div>
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Hinzufügen', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
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
                    <h3>🔗 Stripe Link bearbeiten</h3>
                    <p>Bearbeiten Sie die Verknüpfung zwischen Produktkombination und Stripe-Link.</p>
                    
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
                                    <label>Extra</label>
                                    <select name="extra_ids">
                                        <option value="">Kein Extra</option>
                                        <?php foreach ($extra_combinations as $combo): ?>
                                        <option value="<?php echo esc_attr($combo['ids']); ?>" <?php selected($edit_item->extra_ids, $combo['ids']); ?>>
                                            <?php echo esc_html($combo['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="federwiegen-form-group">
                                    <label>Mietdauer *</label>
                                    <select name="duration_id" required>
                                        <option value="">Bitte wählen...</option>
                                        <?php foreach ($durations as $duration): ?>
                                        <option value="<?php echo $duration->id; ?>" <?php selected($edit_item->duration_id, $duration->id); ?>>
                                            <?php echo esc_html($duration->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (!empty($conditions)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Zustand</label>
                                    <select name="condition_id">
                                        <option value="">Alle Zustände</option>
                                        <?php foreach ($conditions as $condition): ?>
                                        <option value="<?php echo $condition->id; ?>" <?php selected($edit_item->condition_id, $condition->id); ?>>
                                            <?php echo esc_html($condition->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifischer Zustand</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product_colors)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Produktfarbe</label>
                                    <select name="product_color_id">
                                        <option value="">Alle Produktfarben</option>
                                        <?php foreach ($product_colors as $color): ?>
                                        <option value="<?php echo $color->id; ?>" <?php selected($edit_item->product_color_id, $color->id); ?>>
                                            <?php echo esc_html($color->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifische Produktfarbe</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($frame_colors)): ?>
                                <div class="federwiegen-form-group">
                                    <label>Gestellfarbe</label>
                                    <select name="frame_color_id">
                                        <option value="">Alle Gestellfarben</option>
                                        <?php foreach ($frame_colors as $color): ?>
                                        <option value="<?php echo $color->id; ?>" <?php selected($edit_item->frame_color_id, $color->id); ?>>
                                            <?php echo esc_html($color->name); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Optional: Spezifische Gestellfarbe</small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="federwiegen-form-group full-width">
                                    <label>Stripe Link *</label>
                                    <input type="url" name="stripe_link" value="<?php echo esc_attr($edit_item->stripe_link); ?>" required placeholder="https://buy.stripe.com/...">
                                    <small>Vollständiger Stripe-Link</small>
                                </div>
                            </div>
                            
                            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
                            
                            <div class="federwiegen-form-actions">
                                <?php submit_button('Aktualisieren', 'primary', 'submit', false); ?>
                                <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=list'); ?>" class="button">Abbrechen</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                else:
                    echo '<div class="federwiegen-tab-section"><p>Stripe Link nicht gefunden.</p></div>';
                endif;
                break;
                
            case 'list':
            default:
                ?>
                <div class="federwiegen-tab-section">
                    <h3>🔗 Stripe Links</h3>
                    <p>Verknüpfen Sie alle Produktkombinationen mit den entsprechenden Stripe-Zahlungslinks.</p>
                    
                    <div class="federwiegen-list-card">
                        <h4>Stripe Links für: <?php echo $current_category ? esc_html($current_category->name) : 'Unbekannte Kategorie'; ?></h4>
                        
                        <?php if (empty($links)): ?>
                        <div class="federwiegen-empty-state">
                            <p>Noch keine Stripe Links für diese Kategorie konfiguriert.</p>
                            <?php if (!empty($variants) && !empty($extras) && !empty($durations)): ?>
                            <p><strong>Tipp:</strong> Fügen Sie einen neuen Stripe Link hinzu!</p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        
                        <div style="overflow-x: auto;">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Ausführung</th>
                                        <th>Extra</th>
                                        <th>Mietdauer</th>
                                        <th>Zustand</th>
                                        <th>Produktfarbe</th>
                                        <th>Gestellfarbe</th>
                                        <th>Stripe Link</th>
                                        <th>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td><?php echo esc_html($link->variant_name); ?></td>
                                        <td><?php echo $link->extras_names ? esc_html($link->extras_names) : '<em>Kein Extra</em>'; ?></td>
                                        <td><?php echo esc_html($link->duration_name); ?></td>
                                        <td><?php echo $link->condition_name ? esc_html($link->condition_name) : '<em>Alle</em>'; ?></td>
                                        <td><?php echo $link->product_color_name ? esc_html($link->product_color_name) : '<em>Alle</em>'; ?></td>
                                        <td><?php echo $link->frame_color_name ? esc_html($link->frame_color_name) : '<em>Alle</em>'; ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($link->stripe_link); ?>" target="_blank" title="Link öffnen">
                                                <?php echo esc_html(substr($link->stripe_link, 0, 30)) . (strlen($link->stripe_link) > 30 ? '...' : ''); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=edit&edit=' . $link->id); ?>" class="button button-small">Bearbeiten</a>
                                            <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=add&duplicate=' . $link->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small">Duplizieren</a>
                                            <a href="<?php echo admin_url('admin.php?page=federwiegen-links&category=' . $selected_category . '&tab=list&delete=' . $link->id . '&fw_nonce=' . wp_create_nonce('federwiegen_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                <?php
        }
        ?>
    </div>
</div>
