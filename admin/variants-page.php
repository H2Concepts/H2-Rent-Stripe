<?php
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_variants';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Verkaufspreis-Feld ergänzen
$verkaufspreis_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'verkaufspreis_einmalig'");
if (empty($verkaufspreis_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN verkaufspreis_einmalig FLOAT DEFAULT 0 AFTER mietpreis_monatlich");
}

// Ensure all image columns exist
$image_columns = array('image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5');
foreach ($image_columns as $column) {
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column TEXT AFTER base_price");
    }
}

// Ensure stripe_price_id column exists
$price_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id'");
if (empty($price_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT '' AFTER name");
}

// Ensure stripe_archived column exists
$archived_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_archived'");
if (empty($archived_column_exists)) {
    $after = !empty($price_column_exists) ? 'stripe_price_id' : 'name';
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER $after");
}

// Ensure category_id column exists
$category_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
if (empty($category_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
}

// Ensure availability columns exist
$availability_columns = array('available', 'availability_note', 'weekend_only', 'min_rental_days');
foreach ($availability_columns as $column) {
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$column'");
    if (empty($column_exists)) {
        if ($column === 'available') {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column TINYINT(1) DEFAULT 1 AFTER image_url_5");
        } elseif ($column === 'weekend_only') {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column TINYINT(1) DEFAULT 0 AFTER stock_rented");
        } elseif ($column === 'min_rental_days') {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column INT DEFAULT 0 AFTER weekend_only");
        } else {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column VARCHAR(255) DEFAULT '' AFTER available");
        }
    }
}

// Handle form submissions
if (isset($_POST['submit'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $stripe_product_id = '';
    $stripe_price_id   = '';
    if (!empty($_POST['id'])) {
        $existing_variant = $wpdb->get_row($wpdb->prepare(
            "SELECT name, mietpreis_monatlich, verkaufspreis_einmalig, stripe_product_id, stripe_price_id FROM $table_name WHERE id = %d",
            intval($_POST['id'])
        ));
        if ($existing_variant) {
            $stripe_product_id = $existing_variant->stripe_product_id;
            $stripe_price_id   = $existing_variant->stripe_price_id;
            if ($stripe_product_id && $existing_variant->name !== $name) {
                StripeService::update_product_name($stripe_product_id, $name);
            }
        } else {
            $stripe_product_id = '';
            $stripe_price_id   = '';
        }
    } else {
        $existing_variant = null;
    }
    $description = sanitize_textarea_field($_POST['description']);
    $mietpreis_monatlich    = floatval($_POST['mietpreis_monatlich']);
    $verkaufspreis_einmalig = isset($_POST['verkaufspreis_einmalig']) ? floatval($_POST['verkaufspreis_einmalig']) : 0;
    $available = isset($_POST['available']) ? 1 : 0;
    $availability_note = sanitize_text_field($_POST['availability_note']);
    $delivery_time    = sanitize_text_field(trim($_POST['delivery_time'] ?? ''));
    $weekend_only     = isset($_POST['weekend_only']) ? 1 : 0;
    $min_rental_days  = isset($_POST['min_rental_days']) ? intval($_POST['min_rental_days']) : 0;
    $active           = isset($_POST['active']) ? 1 : 0;
    $sort_order       = intval($_POST['sort_order']);
    
    // Handle multiple images
    $image_data = array();
    for ($i = 1; $i <= 5; $i++) {
        $image_raw = $_POST['image_url_' . $i] ?? '';
        $image_data['image_url_' . $i] = (is_string($image_raw) && filter_var($image_raw, FILTER_VALIDATE_URL))
            ? esc_url_raw($image_raw) : '';
    }

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $update_data = array_merge(array(
            'category_id'            => $category_id,
            'name'                   => $name,
            'description'            => $description,
            'mietpreis_monatlich'    => $mietpreis_monatlich,
            'verkaufspreis_einmalig' => $verkaufspreis_einmalig,
            'base_price'             => $mietpreis_monatlich,
            'available'              => $available,
            'availability_note'      => $availability_note,
            'delivery_time'          => $delivery_time,
            'weekend_only'           => $weekend_only,
            'min_rental_days'        => $min_rental_days,
            'active'                 => $active,
            'sort_order'             => $sort_order
        ), $image_data);
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => intval($_POST['id'])),
            array_merge(
                array('%d','%s','%s','%f','%f','%f','%d','%s','%s','%d','%d','%d','%d'),
                array_fill(0, 5, '%s')
            ),
            array('%d')
        );
        
        $variant_id = intval($_POST['id']);
        if ($result !== false) {
            if ($result === 0) {
                echo '<div class="notice notice-warning"><p>⚠️ Keine Änderungen erkannt.</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>✅ Ausführung erfolgreich aktualisiert!</p></div>';
            }
            $mode = get_option('produkt_betriebsmodus', 'miete');
            $product_id = $stripe_product_id;
            $price_id   = $stripe_price_id;

            $needs_price_update = false;
            if ($existing_variant) {
                $current_price = ($mode === 'kauf')
                    ? floatval($existing_variant->verkaufspreis_einmalig)
                    : floatval($existing_variant->mietpreis_monatlich);

                $new_price = ($mode === 'kauf')
                    ? $verkaufspreis_einmalig
                    : $mietpreis_monatlich;

                if ($existing_variant->name !== $name || $current_price != $new_price) {
                    $needs_price_update = true;
                }
            }

            if ($product_id) {
                if ($needs_price_update) {
                    $amount = ($mode === 'kauf') ? $verkaufspreis_einmalig : $mietpreis_monatlich;
                    $new_price = \ProduktVerleih\StripeService::create_price($product_id, round($amount * 100), $mode);
                    if (!is_wp_error($new_price)) {
                        $wpdb->update($table_name, ['stripe_price_id' => $new_price->id], ['id' => $variant_id], ['%s'], ['%d']);
                        $price_id = $new_price->id;
                    }
                }
            } else {
                $res = \ProduktVerleih\StripeService::create_or_update_product_and_price([
                    'plugin_product_id' => $variant_id,
                    'variant_id'        => $variant_id,
                    'duration_id'       => null,
                    'name'              => $name,
                    'price'             => ($mode === 'kauf') ? $verkaufspreis_einmalig : $mietpreis_monatlich,
                    'mode'              => $mode,
                ]);
                if (!is_wp_error($res)) {
                    $product_id = $res['stripe_product_id'];
                    $price_id   = $res['stripe_price_id'];
                    $wpdb->update($table_name, [
                        'stripe_product_id' => $product_id,
                        'stripe_price_id'   => $price_id,
                    ], ['id' => $variant_id], ['%s', '%s'], ['%d']);
                }
            }

            require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';
            produkt_sync_sale_price($variant_id, $verkaufspreis_einmalig, $product_id, $mode);

            \ProduktVerleih\StripeService::delete_lowest_price_cache_for_category($category_id);
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $insert_data = array_merge(array(
            'category_id'            => $category_id,
            'name'                   => $name,
            'description'            => $description,
            'mietpreis_monatlich'    => $mietpreis_monatlich,
            'verkaufspreis_einmalig' => $verkaufspreis_einmalig,
            'base_price'             => $mietpreis_monatlich,
            'available'              => $available,
            'availability_note'      => $availability_note,
            'delivery_time'          => $delivery_time,
            'weekend_only'           => $weekend_only,
            'min_rental_days'        => $min_rental_days,
            'active'                 => $active,
            'sort_order'             => $sort_order
        ), $image_data);
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array_merge(
                array('%d','%s','%s','%f','%f','%f','%d','%s','%s','%d','%d','%d','%d'),
                array_fill(0, 5, '%s')
            )
        );

        $variant_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Ausführung erfolgreich hinzugefügt!</p></div>';
            $mode = get_option('produkt_betriebsmodus', 'miete');
            $res = \ProduktVerleih\StripeService::create_or_update_product_and_price([
                'plugin_product_id' => $variant_id,
                'variant_id'        => $variant_id,
                'duration_id'       => null,
                'name'              => $name,
                'price'             => ($mode === 'kauf') ? $verkaufspreis_einmalig : $mietpreis_monatlich,
                'mode'              => $mode,
            ]);
            if (!is_wp_error($res)) {
                $wpdb->update($table_name, [
                    'stripe_product_id' => $res['stripe_product_id'],
                    'stripe_price_id'   => $res['stripe_price_id'],
                ], ['id' => $variant_id], ['%s', '%s'], ['%d']);
                $product_id = $res['stripe_product_id'];
            } else {
                $product_id = '';
            }

            require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';
            produkt_sync_sale_price($variant_id, $verkaufspreis_einmalig, $product_id, $mode);

            \ProduktVerleih\StripeService::delete_lowest_price_cache_for_category($category_id);
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $variant_id = intval($_GET['delete']);
    $stripe_product_id = $wpdb->get_var($wpdb->prepare(
        "SELECT stripe_product_id FROM $table_name WHERE id = %d",
        $variant_id
    ));

    if (!empty($stripe_product_id)) {
        require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';
        produkt_delete_or_archive_stripe_product($stripe_product_id);
    }

    $result = $wpdb->delete($table_name, array('id' => $variant_id), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Ausführung gelöscht!</p></div>';
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
$current_category = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_categories WHERE id = %d", $selected_category));

// Get all variants for selected category
$variants = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));

// Get branding settings
$branding = array();
$branding_results = $wpdb->get_results("SELECT setting_key, setting_value FROM {$wpdb->prefix}produkt_branding");
foreach ($branding_results as $result) {
    $branding[$result->setting_key] = $result->setting_value;
}
?>

<div class="wrap">
    <div class="produkt-admin-card">
        <!-- Kompakter Header -->
        <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">🖼️</div>
        <div class="produkt-admin-title-compact">
            <h1>Ausführungen verwalten</h1>
            <p>Produktvarianten mit Bildergalerie</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Ausführungen</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="produkt-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="produkt-variants">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <label for="category-select"><strong>🏷️ Produkt:</strong></label>
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
        <div class="produkt-category-info">
            <code>[produkt_product category="<?php echo esc_html($current_category->shortcode); ?>"]</code>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab Navigation -->
    <div class="produkt-tab-nav">
        <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=list'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=add'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Ausführung
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
           class="produkt-tab <?php echo $active_tab === 'edit' ? 'active' : ''; ?>">
            ✏️ Bearbeiten
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="produkt-tab-content">
        <?php
        switch ($active_tab) {
            case 'add':
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-edit-tab.php';
                } else {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-list-tab.php';
                }
                break;
            case 'list':
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-list-tab.php';
        }
        ?>
    </div>
    </div>
</div>
