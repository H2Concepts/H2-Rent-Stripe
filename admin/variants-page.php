<?php
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\StripeService;

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_variants';
$mode = get_option('produkt_betriebsmodus', 'miete');

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
            "SELECT name, mietpreis_monatlich, verkaufspreis_einmalig, weekend_price, stripe_product_id, stripe_price_id, stripe_weekend_price_id FROM $table_name WHERE id = %d",
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
    if ($mode === 'kauf') {
        $verkaufspreis_einmalig = isset($_POST['verkaufspreis_einmalig']) ? intval($_POST['verkaufspreis_einmalig']) / 100 : 0;
        $weekend_price = isset($_POST['weekend_price']) ? intval($_POST['weekend_price']) / 100 : 0;
    } else {
        $verkaufspreis_einmalig = isset($_POST['verkaufspreis_einmalig']) ? floatval($_POST['verkaufspreis_einmalig']) : 0;
        $weekend_price = isset($_POST['weekend_price']) ? floatval($_POST['weekend_price']) : 0;
    }
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
            'weekend_price'         => $weekend_price,
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
                array('%d','%s','%s','%f','%f','%f','%f','%d','%s','%s','%d','%d','%d','%d'),
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
                    $nickname = ($mode === 'kauf') ? 'Einmalverkauf' : 'Vermietung pro Monat';
                    $new_price = \ProduktVerleih\StripeService::create_price($product_id, round($amount * 100), $mode, $nickname);
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
            produkt_sync_weekend_price($variant_id, $weekend_price, $product_id);

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
            'weekend_price'         => $weekend_price,
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
                array('%d','%s','%s','%f','%f','%f','%f','%d','%s','%s','%d','%d','%d','%d'),
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
            produkt_sync_weekend_price($variant_id, $weekend_price, $product_id);

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
$subline_text = 'Ausführungen verwalten';
if ($active_tab === 'add') {
    $subline_text = 'Erstellen Sie eine neue Ausführung für das Produkt "' . ($current_category ? esc_html($current_category->name) : 'Unbekannt') . '"';
} elseif ($active_tab === 'edit' && $edit_item) {
    $subline_text = 'Bearbeiten Sie die Ausführung "' . esc_html($edit_item->name) . '" für das Produkt "' . ($current_category ? esc_html($current_category->name) : 'Unbekannt') . '"';
}
?>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline"><?php echo $subline_text; ?></p>

<?php if ($active_tab === 'list'): ?>
    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card card-product-selector">
                <h2>Produkt auswählen</h2>
                <p class="card-subline">Für welches Produkt möchten Sie eine Ausführung bearbeiten?</p>
                <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                    <input type="hidden" name="page" value="produkt-variants">
                    <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
                    <select name="category" id="category-select" onchange="this.form.submit()">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><input type="submit" value="Wechseln" class="button"></noscript>
                </form>
                <?php if ($current_category): ?>
                <div class="selected-product-preview">
                    <?php if (!empty($current_category->default_image)): ?>
                        <img src="<?php echo esc_url($current_category->default_image); ?>" alt="<?php echo esc_attr($current_category->name); ?>">
                    <?php else: ?>
                        <div class="placeholder-icon">🏷️</div>
                    <?php endif; ?>
                    <div class="tile-overlay"><span><?php echo esc_html($current_category->name); ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-right">
            <div class="dashboard-row">
                <div class="dashboard-card card-new-product">
                    <h2>Neue Ausführung</h2>
                    <p class="card-subline">Ausführung erstellen</p>
                    <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&tab=add'); ?>" class="icon-btn add-product-btn" aria-label="Hinzufügen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                            <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                            <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                        </svg>
                    </a>
                </div>
                <div class="dashboard-card card-quicknav">
                    <h2>Schnellnavigation</h2>
                    <p class="card-subline">Direkt zu wichtigen Listen</p>
                    <div class="quicknav-grid">
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-verleih">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🏠</div>
                                    <div class="quicknav-label">Dashboard</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-categories">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Kategorien</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-products">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🏷️</div>
                                    <div class="quicknav-label">Produkte</div>
                                </div>
                            </a>
                        </div>
                        <div class="quicknav-card">
                            <a href="admin.php?page=produkt-extras">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">✨</div>
                                    <div class="quicknav-label">Extras</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Ausführungen</h2>
                        <p class="card-subline">Vorhandene Varianten des Produkts</p>
                    </div>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Name</th>
                            <th>Verfügbar</th>
                            <th>Preis</th>
                            <th>Bilder</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $modus = get_option('produkt_betriebsmodus', 'miete'); ?>
                        <?php foreach ($variants as $variant): ?>
                            <?php
                                $image_count = 0;
                                $main_image = '';
                                for ($i = 1; $i <= 5; $i++) {
                                    $field = 'image_url_' . $i;
                                    if (!empty($variant->$field)) {
                                        $image_count++;
                                        if (!$main_image) {
                                            $main_image = $variant->$field;
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php if ($main_image): ?>
                                        <img src="<?php echo esc_url($main_image); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" alt="<?php echo esc_attr($variant->name); ?>">
                                    <?php else: ?>
                                        <div style="width:60px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;">📦</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($variant->name); ?></td>
                                <td><?php echo ($variant->available ?? 1) ? '✅' : '❌'; ?></td>
                                <td>
                                    <?php if ($modus === 'kauf'): ?>
                                        <?php echo number_format($variant->verkaufspreis_einmalig, 2, ',', '.'); ?>€
                                    <?php else: ?>
                                        <?php echo number_format($variant->mietpreis_monatlich, 2, ',', '.'); ?>€
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $image_count; ?></td>
                                <td>
                                    <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-variants&category=<?php echo $selected_category; ?>&tab=edit&edit=<?php echo $variant->id; ?>'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                            <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                            <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-variants&category=<?php echo $selected_category; ?>&delete=<?php echo $variant->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                            <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                            <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($active_tab === 'add'): ?>
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-add-tab.php'; ?>
<?php elseif ($active_tab === 'edit' && $edit_item): ?>
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/variants-edit-tab.php'; ?>
<?php endif; ?>
</div>
