<?php
use ProduktVerleih\StripeService;
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_extras';

// Get all categories for dropdown
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Get selected category from URL parameter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 1);

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Get variants for toggles
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT id, name FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
    $selected_category
));

// Ensure image_url column exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'image_url'");
if (empty($column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN image_url TEXT AFTER price");
}
// Ensure stripe_price_id column exists
$price_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id'");
if (empty($price_id_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT '' AFTER name");
}
$rent_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id_rent'");
if (empty($rent_id_exists)) {
    $after = !empty($price_id_exists) ? 'stripe_price_id' : 'name';
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id_rent VARCHAR(255) DEFAULT NULL AFTER $after");
}
$sale_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id_sale'");
if (empty($sale_id_exists)) {
    $after = !empty($rent_id_exists) ? 'stripe_price_id_rent' : (!empty($price_id_exists) ? 'stripe_price_id' : 'name');
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id_sale VARCHAR(255) DEFAULT NULL AFTER $after");
}

// Ensure stripe_archived column exists
$archived_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_archived'");
if (empty($archived_column_exists)) {
    $after = !empty($price_id_exists) ? 'stripe_price_id' : 'name';
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER $after");
}

// Ensure category_id column exists
$category_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'category_id'");
if (empty($category_column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN category_id mediumint(9) DEFAULT 1 AFTER id");
}

// Handle form submissions
if (isset($_POST['submit'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $name        = sanitize_text_field($_POST['name']);
    $modus       = get_option('produkt_betriebsmodus', 'miete');
    $sale_price  = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
    $price       = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $price_input = $modus === 'kauf' ? ($_POST['sale_price'] ?? '') : ($_POST['price'] ?? '');
    $stripe_price = floatval($price_input);
    $image_url = esc_url_raw($_POST['image_url']);
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order']);

    $main_product_name = '';
    if ($category_id) {
        $main_product_name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT name FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                $category_id
            )
        );
    }
    $extra_base_name     = trim($name);
    $stripe_product_name = $extra_base_name;
    if (!empty($main_product_name)) {
        $stripe_product_name .= ' – ' . $main_product_name;
    }

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $result = $wpdb->update(
            $table_name,
            array(
                'category_id' => $category_id,
                'name'        => $name,
                'price'       => $price,
                'price_sale'  => ($modus === 'kauf') ? $sale_price : 0,
                'price_rent'  => ($modus === 'kauf') ? 0 : $price,
                'image_url'   => $image_url,
                'active'      => $active,
                'sort_order'  => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            $extra_id = intval($_POST['id']);
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
            $ids = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id FROM $table_name WHERE id = %d", $extra_id));
            if ($ids && $ids->stripe_product_id) {
                \ProduktVerleih\StripeService::update_product_name($ids->stripe_product_id, $stripe_product_name);
                $new_price = \ProduktVerleih\StripeService::create_price($ids->stripe_product_id, round($stripe_price * 100), $modus);
                if (!is_wp_error($new_price)) {
                    $update = [
                        'stripe_price_id'  => $new_price->id,
                        'price_sale'       => ($modus === 'kauf') ? $sale_price : 0,
                        'price_rent'       => ($modus === 'kauf') ? 0 : $price,
                    ];
                    if ($modus === 'kauf') {
                        $update['stripe_price_id_sale'] = $new_price->id;
                    } else {
                        $update['stripe_price_id_rent'] = $new_price->id;
                    }
                    $wpdb->update($table_name, $update, ['id' => $extra_id], null, ['%d']);
                }
            } else {
                $res = \ProduktVerleih\StripeService::create_extra_price($extra_base_name, $stripe_price, $main_product_name, $modus);
                if (is_wp_error($res)) {
                    error_log('❌ Fehler beim Stripe Extra-Preis: ' . $res->get_error_message());
                } elseif (!empty($res['price_id'])) {
                    error_log('✅ Extra-Preis erfolgreich erstellt: ' . $res['price_id']);
                    $update = [
                        'stripe_product_id' => $res['product_id'],
                        'stripe_price_id'   => $res['price_id'],
                        'price_sale'        => ($modus === 'kauf') ? $sale_price : 0,
                        'price_rent'        => ($modus === 'kauf') ? 0 : $price,
                    ];
                    if ($modus === 'kauf') {
                        $update['stripe_price_id_sale'] = $res['price_id'];
                    } else {
                        $update['stripe_price_id_rent'] = $res['price_id'];
                    }
                    $wpdb->update($table_name, $update, ['id' => $extra_id]);
                } else {
                    error_log('⚠️ Keine Fehler, aber auch kein Preis erstellt.');
                }
            }
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Aktualisieren: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name'        => $name,
                'price'       => $price,
                'price_sale'  => ($modus === 'kauf') ? $sale_price : 0,
                'price_rent'  => ($modus === 'kauf') ? 0 : $price,
                'image_url'   => $image_url,
                'active'      => $active,
                'sort_order'  => $sort_order
            ),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d', '%d')
        );
        
        if ($result !== false) {
            $extra_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich hinzugefügt!</p></div>';
            $res = \ProduktVerleih\StripeService::create_extra_price($extra_base_name, $stripe_price, $main_product_name, $modus);
            if (is_wp_error($res)) {
                error_log('❌ Fehler beim Stripe Extra-Preis: ' . $res->get_error_message());
            } elseif (!empty($res['price_id'])) {
                error_log('✅ Extra-Preis erfolgreich erstellt: ' . $res['price_id']);
                $update = [
                    'stripe_product_id' => $res['product_id'],
                    'stripe_price_id'   => $res['price_id'],
                ];
                if ($modus === 'kauf') {
                    $update['stripe_price_id_sale'] = $res['price_id'];
                } else {
                    $update['stripe_price_id_rent'] = $res['price_id'];
                }
                $wpdb->update($table_name, $update, ['id' => $extra_id]);
            } else {
                error_log('⚠️ Keine Fehler, aber auch kein Preis erstellt.');
            }
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Hinzufügen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }

    if (isset($extra_id)) {
        $variant_inputs = $_POST['variant_available'] ?? array();
        $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
        $all_variants = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
            $category_id
        ));
        foreach ($all_variants as $v) {
            $available = isset($variant_inputs[$v->id]) ? 1 : 0;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_variant_options WHERE variant_id = %d AND option_type = 'extra' AND option_id = %d",
                $v->id,
                $extra_id
            ));
            if ($exists) {
                $wpdb->update($table_variant_options, ['available' => $available], ['id' => $exists], ['%d'], ['%d']);
            } else {
                $wpdb->insert($table_variant_options, [
                    'variant_id' => $v->id,
                    'option_type' => 'extra',
                    'option_id' => $extra_id,
                    'available' => $available
                ], ['%d','%s','%d','%d']);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $extra_id = intval($_GET['delete']);
    $stripe_product_id = $wpdb->get_var($wpdb->prepare(
        "SELECT stripe_product_id FROM $table_name WHERE id = %d",
        $extra_id
    ));

    if (!empty($stripe_product_id)) {
        require_once PRODUKT_PLUGIN_PATH . 'includes/stripe-sync.php';
        produkt_delete_or_archive_stripe_product($stripe_product_id, $extra_id, 'produkt_extras');
    }

    $result = $wpdb->delete($table_name, array('id' => $extra_id), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Extra gelöscht!</p></div>';
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

// Get all extras for selected category
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));

$variant_availability = array();
if ($edit_item) {
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT variant_id, available FROM {$wpdb->prefix}produkt_variant_options WHERE option_type = 'extra' AND option_id = %d",
        $edit_item->id
    ));
    foreach ($rows as $row) {
        $variant_availability[$row->variant_id] = intval($row->available);
    }
}
?>

<div class="wrap">
    <!-- Kompakter Header -->
    <div class="produkt-admin-header-compact">
        <div class="produkt-admin-logo-compact">🎁</div>
        <div class="produkt-admin-title-compact">
            <h1>Extras verwalten</h1>
            <p>Zusatzoptionen mit Bildern</p>
        </div>
    </div>
    
    <!-- Breadcrumb Navigation -->
    <div class="produkt-breadcrumb">
        <a href="<?php echo admin_url('admin.php?page=produkt-verleih'); ?>">Dashboard</a> 
        <span>→</span> 
        <strong>Extras</strong>
    </div>
    
    <!-- Category Selection -->
    <div class="produkt-category-selector">
        <form method="get" action="">
            <input type="hidden" name="page" value="produkt-extras">
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
        <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=list'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>">
            📋 Übersicht
        </a>
        <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=add'); ?>" 
           class="produkt-tab <?php echo $active_tab === 'add' ? 'active' : ''; ?>">
            ➕ Neues Extra
        </a>
        <?php if ($edit_item): ?>
        <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=edit&edit=' . $edit_item->id); ?>" 
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
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-add-tab.php';
                break;
            case 'edit':
                if ($edit_item) {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-edit-tab.php';
                } else {
                    include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-list-tab.php';
                }
                break;
            case 'list':
            default:
                include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-list-tab.php';
        }
        ?>
    </div>
</div>
