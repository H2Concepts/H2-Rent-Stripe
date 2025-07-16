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

// Prepare edit item variable early to avoid undefined notices
$edit_item = null;

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
    $show_badge = isset($_POST['show_badge']) ? 1 : 0;
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
                'discount' => 0,
                'show_badge' => $show_badge,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d'),
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
                'discount' => 0,
                'show_badge' => $show_badge,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%d')
        );
        
        $duration_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich hinzugefügt!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }

    if ($result !== false && isset($_POST['variant_custom_price']) && is_array($_POST['variant_custom_price'])) {
        foreach ($_POST['variant_custom_price'] as $v_id => $custom_price) {
            $v_id = intval($v_id);
            $custom_price  = floatval($custom_price);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_prices WHERE duration_id = %d AND variant_id = %d", $duration_id, $v_id));
            $data = [
                'duration_id'            => $duration_id,
                'variant_id'             => $v_id,
                'custom_price'           => $custom_price
            ];
            if ($exists) {
                $wpdb->update($table_prices, $data, ['id' => $exists]);
            } else {
                $wpdb->insert($table_prices, $data);
                $exists = $wpdb->insert_id;
            }

            $stripe_product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT stripe_product_id FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                $v_id
            ));
            if (!$stripe_product_id) {
                continue;
            }

            $duration_name = (isset($edit_item) && $edit_item) ? $edit_item->name : $name;
            $mode          = 'miete';

            $ids = $wpdb->get_row($wpdb->prepare("SELECT stripe_price_id FROM $table_prices WHERE id = %d", $exists));
            $current_price_id = $ids ? $ids->stripe_price_id : '';
            $needs_new_price  = true;

            if ($current_price_id) {
                $existing_amount = \ProduktVerleih\StripeService::get_price_amount($current_price_id);
                if (!is_wp_error($existing_amount) && floatval($existing_amount) == $custom_price) {
                    $needs_new_price = false;
                }
            }

            if ($needs_new_price) {
                $new_price = \ProduktVerleih\StripeService::create_price(
                    $stripe_product_id,
                    round($custom_price * 100),
                    $mode,
                    $duration_name,
                    [
                        'duration_label' => $duration_name,
                        'variant_id'     => $v_id,
                        'duration_id'    => $duration_id
                    ]
                );
                if (!is_wp_error($new_price)) {
                    $wpdb->update(
                        $table_prices,
                        [
                            'stripe_product_id' => $stripe_product_id,
                            'stripe_price_id'   => $new_price->id,
                        ],
                        ['id' => $exists],
                        ['%s', '%s'],
                        ['%d']
                    );
                }
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $duration_id = intval($_GET['delete']);
    $price_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT stripe_price_id FROM $table_prices WHERE duration_id = %d",
        $duration_id
    ));

    if ($price_rows) {
        require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';

        foreach ($price_rows as $row) {
            if (!empty($row->stripe_price_id)) {
                produkt_deactivate_stripe_price($row->stripe_price_id);
            }
        }
    }

    // Optional: zugehörige Preise aus DB löschen
    $wpdb->delete($table_prices, ['duration_id' => $duration_id], ['%d']);

    $result = $wpdb->delete($table_name, array('id' => $duration_id), array('%d'));
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
