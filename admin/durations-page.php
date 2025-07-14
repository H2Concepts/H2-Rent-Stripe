<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_durations';
$table_prices = $wpdb->prefix . 'produkt_duration_prices';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Ensure category_id column exists
$category_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
if (empty($category_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
}

// Handle form submissions
if (isset($_POST['submit'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $months_minimum = intval($_POST['months_minimum']);
    $discount = floatval($_POST['discount']) / 100; // Convert percentage to decimal
    $active = isset($_POST['active']) ? 1 : 0;
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
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%d', '%f', '%d', '%d'),
            array('%d')
        );
        
        $duration_id = intval($_POST['id']);
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich aktualisiert!</p></div>';
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
                'months_minimum' => $months_minimum,
                'discount' => $discount,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%d', '%f', '%d', '%d')
        );
        
        $duration_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }

    if ($result !== false && isset($_POST['variant_price_miete']) && is_array($_POST['variant_price_miete'])) {
        foreach ($_POST['variant_price_miete'] as $v_id => $price_miete) {
            $v_id = intval($v_id);
            $price_miete  = floatval($price_miete);
            $price_kauf = isset($_POST['variant_price_kauf'][$v_id]) ? floatval($_POST['variant_price_kauf'][$v_id]) : 0;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_prices WHERE duration_id = %d AND variant_id = %d", $duration_id, $v_id));
            $data = [
                'duration_id'            => $duration_id,
                'variant_id'             => $v_id,
                'mietpreis_monatlich'    => $price_miete,
                'verkaufspreis_einmalig' => $price_kauf
            ];
            if ($exists) {
                $wpdb->update($table_prices, $data, ['id' => $exists]);
            } else {
                $wpdb->insert($table_prices, $data);
                $exists = $wpdb->insert_id;
            }
            // Stripe product/price handling
            $variant_name    = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}produkt_variants WHERE id = %d", $v_id));
            $name_for_stripe = $variant_name . ' ' . ($edit_item ? $edit_item->name : $name);
            $mode            = get_option('produkt_betriebsmodus', 'miete');

            $ids = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id, stripe_price_id FROM $table_prices WHERE id = %d", $exists));
            if ($ids && $ids->stripe_product_id) {
                \ProduktVerleih\StripeService::update_product_name($ids->stripe_product_id, $name_for_stripe);
                $existing_amount = \ProduktVerleih\StripeService::get_price_amount($ids->stripe_price_id);
                if (!is_wp_error($existing_amount) && $existing_amount != $price_miete) {
                    $new_price = \ProduktVerleih\StripeService::create_price($ids->stripe_product_id, round($price_miete * 100), $mode);
                    if (!is_wp_error($new_price)) {
                        $wpdb->update($table_prices, ['stripe_price_id' => $new_price->id], ['id' => $exists], ['%s'], ['%d']);
                    }
                }
            } else {
                $result_ids = \ProduktVerleih\StripeService::create_or_update_product_and_price([
                    'name'              => $name_for_stripe,
                    'price'             => $price_miete,
                    'mode'              => $mode,
                    'plugin_product_id' => $exists,
                    'variant_id'        => $v_id,
                    'duration_id'       => $duration_id,
                ]);
                if (!is_wp_error($result_ids)) {
                    $wpdb->update($table_prices, [
                        'stripe_product_id' => $result_ids['stripe_product_id'],
                        'stripe_price_id'   => $result_ids['stripe_price_id'],
                    ], ['id' => $exists]);
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $result = $wpdb->delete($table_name, array('id' => intval($_GET['delete'])), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Mietdauer gelöscht!</p></div>';
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

// Get all durations for selected category
$durations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, months_minimum", $selected_category));
$variants = $wpdb->get_results($wpdb->prepare("SELECT id, name, stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order", $selected_category));
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">⏰</div>
        <div class="produkt-admin-title-compact">
            <h1>Mietdauern verwalten</h1>
            <p>Laufzeiten & Rabatte</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Mietdauern</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="produkt-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="produkt-durations">
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
        <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=list'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=add'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neue Mietdauer
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
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
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-edit-tab.php';
                } else {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-list-tab.php';
                }
                break;
            case 'list':
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-list-tab.php';
        }
        ?>
    </div>
</div>
