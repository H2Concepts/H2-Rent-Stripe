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
        $extra_id = intval($_POST['id']);
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT name, price_sale, price_rent, stripe_product_id FROM $table_name WHERE id = %d",
            $extra_id
        ));

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
            array('id' => $extra_id),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d', '%d'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
            if ($existing) {
                $needs_price_update = ($existing->name !== $name);
                $old_price         = ($modus === 'kauf') ? floatval($existing->price_sale) : floatval($existing->price_rent);
                if ($old_price != $stripe_price) {
                    $needs_price_update = true;
                }

                if ($existing->stripe_product_id) {
                    if ($existing->name !== $name) {
                        \ProduktVerleih\StripeService::update_product_name($existing->stripe_product_id, $stripe_product_name);
                    }
                    if ($needs_price_update) {
                        $new_price = \ProduktVerleih\StripeService::create_price($existing->stripe_product_id, round($stripe_price * 100), $modus);
                        if (!is_wp_error($new_price)) {
                            $update = [
                                'stripe_price_id' => $new_price->id,
                                'price_sale'      => ($modus === 'kauf') ? $sale_price : 0,
                                'price_rent'      => ($modus === 'kauf') ? 0 : $price,
                            ];
                            if ($modus === 'kauf') {
                                $update['stripe_price_id_sale'] = $new_price->id;
                            } else {
                                $update['stripe_price_id_rent'] = $new_price->id;
                            }
                            $wpdb->update($table_name, $update, ['id' => $extra_id]);
                        }
                    }
                } else {
                    $needs_price_update = true; // new stripe product must be created
                }
            }
            if (! $existing || empty($existing->stripe_product_id)) {
                $res = \ProduktVerleih\StripeService::create_extra_price($extra_base_name, $stripe_price, $main_product_name, $modus);
                if (is_wp_error($res)) {
                    // handle Stripe error silently
                } elseif (!empty($res['price_id'])) {
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
                // handle Stripe error silently
            } elseif (!empty($res['price_id'])) {
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

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Extras verwalten</p>

<?php if ($active_tab === 'list'): ?>
    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card card-product-selector">
                <h2>Produkt auswählen</h2>
                <p class="card-subline">Für welches Produkt möchten Sie ein Extra bearbeiten?</p>
                <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                    <input type="hidden" name="page" value="produkt-extras">
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
                    <h2>Neues Extra</h2>
                    <p class="card-subline">Extra erstellen</p>
                    <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=add'); ?>" class="icon-btn add-product-btn" aria-label="Hinzufügen">
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
                            <a href="admin.php?page=produkt-variants&category=<?php echo $selected_category; ?>">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Ausführungen</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Extras</h2>
                        <p class="card-subline">Verfügbare Extras des Produkts</p>
                    </div>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Name</th>
                            <th>Preis</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $modus = get_option('produkt_betriebsmodus', 'miete'); ?>
                        <?php foreach ($extras as $extra): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($extra->image_url)): ?>
                                        <img src="<?php echo esc_url($extra->image_url); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:4px;" alt="<?php echo esc_attr($extra->name); ?>">
                                    <?php else: ?>
                                        <div style="width:60px;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;">🎁</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($extra->name); ?></td>
                                <td>
                                    <?php if ($modus === 'kauf'): ?>
                                        <?php echo number_format($extra->price_sale ?? 0, 2, ',', '.'); ?>€
                                    <?php else: ?>
                                        <?php echo number_format($extra->price_rent ?? $extra->price, 2, ',', '.'); ?>€
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-extras&category=<?php echo $selected_category; ?>&tab=edit&edit=<?php echo $extra->id; ?>'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                            <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                            <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-extras&category=<?php echo $selected_category; ?>&delete=<?php echo $extra->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
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
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-add-tab.php'; ?>
<?php elseif ($active_tab === 'edit' && $edit_item): ?>
    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/extras-edit-tab.php'; ?>
<?php endif; ?>
</div>
