<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_durations';
$table_prices = $wpdb->prefix . 'produkt_duration_prices';

// Ensure stripe_archived column exists in price table
$archived_col = $wpdb->get_results("SHOW COLUMNS FROM $table_prices LIKE 'stripe_archived'");
if (empty($archived_col)) {
    $wpdb->query("ALTER TABLE $table_prices ADD COLUMN stripe_archived TINYINT(1) DEFAULT 0 AFTER stripe_price_id");
}

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
    $show_popular = isset($_POST['show_popular']) ? 1 : 0;
    $active = isset($_POST['active']) ? 1 : 0;
    $popular_gradient_start = isset($_POST['popular_gradient_start']) ? sanitize_hex_color($_POST['popular_gradient_start']) : '';
    $popular_gradient_end = isset($_POST['popular_gradient_end']) ? sanitize_hex_color($_POST['popular_gradient_end']) : '';
    $popular_text_color = isset($_POST['popular_text_color']) ? sanitize_hex_color($_POST['popular_text_color']) : '';
    $popular_gradient_start = $popular_gradient_start ?: '';
    $popular_gradient_end = $popular_gradient_end ?: '';
    $popular_text_color = $popular_text_color ?: '';
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
                'show_popular' => $show_popular,
                'popular_gradient_start' => $popular_gradient_start,
                'popular_gradient_end' => $popular_gradient_end,
                'popular_text_color' => $popular_text_color,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d'),
            array('%d')
        );
        
        $duration_id = intval($_POST['id']);
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich aktualisiert!</p></div>';
            \ProduktVerleih\StripeService::delete_lowest_price_cache_for_category($category_id);
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
                'show_popular' => $show_popular,
                'popular_gradient_start' => $popular_gradient_start,
                'popular_gradient_end' => $popular_gradient_end,
                'popular_text_color' => $popular_text_color,
                'active' => $active,
                'sort_order' => $sort_order
            ),
            array('%d', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d')
        );
        
        $duration_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Mietdauer erfolgreich hinzugefügt!</p></div>';
            \ProduktVerleih\StripeService::delete_lowest_price_cache_for_category($category_id);
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

$durations = $wpdb->get_results($wpdb->prepare("SELECT d.*, MAX(p.stripe_price_id) AS stripe_price_id, MAX(p.stripe_product_id) AS stripe_product_id, MAX(p.stripe_archived) AS stripe_archived FROM $table_name d LEFT JOIN $table_prices p ON p.duration_id = d.id WHERE d.category_id = %d GROUP BY d.id ORDER BY d.sort_order, d.months_minimum", $selected_category));
$variants = $wpdb->get_results($wpdb->prepare("SELECT id, name, stripe_price_id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order", $selected_category));
$subline_text = 'Verwalten Sie die Mietdauern Ihres ausgewählten Produkts.';
if ($active_tab === 'add') {
    $subline_text = 'Erstellen Sie eine neue Mietdauer für das ausgewählte Produkt.';
} elseif ($active_tab === 'edit' && $edit_item) {
    $subline_text = 'Bearbeiten Sie die Mietdauer "' . esc_html($edit_item->name) . '".';
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
                <p class="card-subline">Für welches Produkt möchten Sie eine Mietdauer verwalten?</p>
                <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                    <input type="hidden" name="page" value="produkt-durations">
                    <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
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
                <div class="selected-product-preview">
                    <?php if (!empty($current_category->default_image)): ?>
                        <img src="<?php echo esc_url($current_category->default_image); ?>" alt="<?php echo esc_attr($current_category->name); ?>">
                    <?php else: ?>
                        <div class="placeholder-icon">⏰</div>
                    <?php endif; ?>
                    <div class="tile-overlay"><span><?php echo esc_html($current_category->name); ?></span></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-right">
            <div class="dashboard-row">
                <div class="dashboard-card card-new-product">
                    <h2>Neue Mietdauer</h2>
                    <p class="card-subline">Mietdauer erstellen</p>
                    <a href="<?php echo admin_url('admin.php?page=produkt-durations&category=' . $selected_category . '&tab=add'); ?>" class="icon-btn add-product-btn" aria-label="Hinzufügen">
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
                        <h2>Mietdauern</h2>
                        <p class="card-subline">Verfügbare Mindestlaufzeiten</p>
                    </div>
                </div>
                <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-list-tab.php'; ?>
            </div>
        </div>
    </div>
    <?php elseif ($active_tab === 'add'): ?>
        <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-add-tab.php'; ?>
    <?php elseif ($active_tab === 'edit' && $edit_item): ?>
        <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-edit-tab.php'; ?>
    <?php else: ?>
        <div class="dashboard-grid">
            <div class="dashboard-left">
                <div class="dashboard-card card-product-selector">
                    <h2>Produkt auswählen</h2>
                    <p class="card-subline">Für welches Produkt möchten Sie eine Mietdauer verwalten?</p>
                    <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                        <input type="hidden" name="page" value="produkt-durations">
                        <input type="hidden" name="tab" value="list">
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
                    <div class="selected-product-preview">
                        <?php if (!empty($current_category->default_image)): ?>
                            <img src="<?php echo esc_url($current_category->default_image); ?>" alt="<?php echo esc_attr($current_category->name); ?>">
                        <?php else: ?>
                            <div class="placeholder-icon">⏰</div>
                        <?php endif; ?>
                        <div class="tile-overlay"><span><?php echo esc_html($current_category->name); ?></span></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-right">
                <div class="dashboard-card">
                    <?php include PRODUKT_PLUGIN_PATH . 'admin/tabs/durations-list-tab.php'; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewGroups = document.querySelectorAll('[data-popular-preview-root]');
    previewGroups.forEach(function (group) {
        const form = group.closest('form');
        if (!form) {
            return;
        }

        const startInput = form.querySelector('[data-popular-start]');
        const endInput = form.querySelector('[data-popular-end]');
        const textInput = form.querySelector('[data-popular-text]');
        const preview = group.querySelector('[data-popular-preview]');

        if (!startInput || !endInput || !textInput || !preview) {
            return;
        }

        const updatePreview = function () {
            const startColor = startInput.value || startInput.getAttribute('value') || '#ff8a3d';
            const endColor = endInput.value || endInput.getAttribute('value') || '#ff5b0f';
            const textColor = textInput.value || textInput.getAttribute('value') || '#ffffff';

            preview.style.setProperty('--popular-gradient-start', startColor);
            preview.style.setProperty('--popular-gradient-end', endColor);
            preview.style.setProperty('--popular-text-color', textColor);
        };

        ['input', 'change'].forEach(function (eventName) {
            startInput.addEventListener(eventName, updatePreview);
            endInput.addEventListener(eventName, updatePreview);
            textInput.addEventListener(eventName, updatePreview);
        });

        updatePreview();
    });
});
</script>
