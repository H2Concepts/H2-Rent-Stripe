<?php
if (!defined('ABSPATH')) { exit; }

use ProduktVerleih\Admin;

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_colors';

$mode = get_option('produkt_betriebsmodus', 'miete');
$is_sale = ($mode === 'kauf');

// Ensure image_url column exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'image_url'");
if (empty($column_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN image_url TEXT AFTER color_type");
}

// Get all categories
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");

// Selected category
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : (isset($categories[0]) ? $categories[0]->id : 0);

// Variants for selected category
$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT id, name FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
    $selected_category
));

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

// Handle form submissions
if (isset($_POST['submit'])) {
    Admin::verify_admin_action();

    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $color_code = sanitize_hex_color($_POST['color_code']);
    $color_type = $is_sale ? 'product' : sanitize_text_field($_POST['color_type']);
    $image_url = esc_url_raw($_POST['image_url'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    $sort_order = intval($_POST['sort_order'] ?? 0);

    if (!empty($_POST['id'])) {
        $wpdb->update(
            $table_name,
            [
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'image_url' => $image_url,
                'active' => $active,
                'sort_order' => $sort_order
            ],
            ['id' => intval($_POST['id'])],
            ['%d','%s','%s','%s','%s','%d','%d'],
            ['%d']
        );
        $color_id = intval($_POST['id']);
    } else {
        $wpdb->insert(
            $table_name,
            [
                'category_id' => $category_id,
                'name' => $name,
                'color_code' => $color_code,
                'color_type' => $color_type,
                'image_url' => $image_url,
                'active' => $active,
                'sort_order' => $sort_order
            ],
            ['%d','%s','%s','%s','%s','%d','%d']
        );
        $color_id = $wpdb->insert_id;
    }

    // Handle availability per variant
    $variant_inputs = $_POST['variant_available'] ?? [];
    $table_variant_options = $wpdb->prefix . 'produkt_variant_options';
    $option_type = ($color_type === 'frame') ? 'frame_color' : 'product_color';
    $all_variants = $wpdb->get_results($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d",
        $category_id
    ));
    foreach ($all_variants as $v) {
        $available = isset($variant_inputs[$v->id]) ? 1 : 0;
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_variant_options WHERE variant_id = %d AND option_type = %s AND option_id = %d",
            $v->id, $option_type, $color_id
        ));
        if ($exists) {
            $wpdb->update($table_variant_options, ['available' => $available], ['id' => $exists], ['%d'], ['%d']);
        } else {
            $wpdb->insert($table_variant_options, [
                'variant_id' => $v->id,
                'option_type' => $option_type,
                'option_id' => $color_id,
                'available' => $available
            ], ['%d','%s','%d','%d']);
        }
    }

    $active_tab = 'list';
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $wpdb->delete($table_name, ['id' => intval($_GET['delete'])], ['%d']);
    $wpdb->delete($wpdb->prefix . 'produkt_variant_options', ['option_id' => intval($_GET['delete'])], ['%d']);
}

$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit'])));
    if ($edit_item) {
        $selected_category = $edit_item->category_id;
        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order, name",
            $selected_category
        ));
    }
}

$product_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'product' ORDER BY sort_order, name",
    $selected_category
));
$frame_colors = [];
if (!$is_sale) {
    $frame_colors = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE category_id = %d AND color_type = 'frame' ORDER BY sort_order, name",
        $selected_category
    ));
}
$total_variants = count($variants);
?>
<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Farben verwalten</p>
<?php if ($active_tab === 'list'): ?>
    <div class="dashboard-grid">
        <div class="dashboard-left">
            <div class="dashboard-card card-product-selector">
                <h2>Produkt auswählen</h2>
                <p class="card-subline">Für welches Produkt möchten Sie Farben bearbeiten?</p>
                <form method="get" action="" class="produkt-category-selector" style="background:none;border:none;padding:0;">
                    <input type="hidden" name="page" value="produkt-colors">
                    <select name="category" onchange="this.form.submit()">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>" <?php selected($selected_category, $category->id); ?>><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><input type="submit" value="Wechseln" class="button"></noscript>
                </form>
                <?php $current_category = null; foreach ($categories as $cat) { if ($cat->id == $selected_category) { $current_category = $cat; break; } } ?>
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
                    <h2>Neue Farbe</h2>
                    <p class="card-subline">Farbe hinzufügen</p>
                    <a href="#" id="add-color-btn" class="icon-btn add-product-btn" aria-label="Hinzufügen">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3"><path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/><path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/></svg>
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
                            <a href="admin.php?page=produkt-variants">
                                <div class="quicknav-inner">
                                    <div class="quicknav-icon-circle">🧩</div>
                                    <div class="quicknav-label">Ausführungen</div>
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
                        <h2>Produktfarben</h2>
                        <p class="card-subline">Vorhandene Farben des Produkts</p>
                    </div>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Farbname</th>
                            <th>Farbcode</th>
                            <th>Verfügbarkeit je Ausführung</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($product_colors)): ?>
                        <tr><td colspan="4">Keine Farben vorhanden.</td></tr>
                    <?php else: foreach ($product_colors as $color):
                        $available_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_variant_options WHERE option_type = 'product_color' AND option_id = %d AND available = 1",
                            $color->id
                        ));
                        $availability = $total_variants ? ($available_count . ' / ' . $total_variants) : '0';
                    ?>
                        <tr>
                            <td><?php echo esc_html($color->name); ?></td>
                            <td><span class="produkt-color-preview-circle" style="background-color:<?php echo esc_attr($color->color_code); ?>;"></span> <?php echo esc_html($color->color_code); ?></td>
                            <td><?php echo esc_html($availability); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=produkt-colors&category=' . $selected_category . '&tab=edit&edit=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                                <a href="<?php echo admin_url('admin.php?page=produkt-colors&category=' . $selected_category . '&tab=list&delete=' . $color->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$is_sale): ?>
            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Gestellfarben</h2>
                        <p class="card-subline">Vorhandene Gestellfarben</p>
                    </div>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Farbname</th>
                            <th>Farbcode</th>
                            <th>Verfügbarkeit je Ausführung</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($frame_colors)): ?>
                        <tr><td colspan="4">Keine Gestellfarben vorhanden.</td></tr>
                    <?php else: foreach ($frame_colors as $color):
                        $available_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_variant_options WHERE option_type = 'frame_color' AND option_id = %d AND available = 1",
                            $color->id
                        ));
                        $availability = $total_variants ? ($available_count . ' / ' . $total_variants) : '0';
                    ?>
                        <tr>
                            <td><?php echo esc_html($color->name); ?></td>
                            <td><span class="produkt-color-preview-circle" style="background-color:<?php echo esc_attr($color->color_code); ?>;"></span> <?php echo esc_html($color->color_code); ?></td>
                            <td><?php echo esc_html($availability); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=produkt-colors&category=' . $selected_category . '&tab=edit&edit=' . $color->id); ?>" class="button button-small">Bearbeiten</a>
                                <a href="<?php echo admin_url('admin.php?page=produkt-colors&category=' . $selected_category . '&tab=list&delete=' . $color->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <div id="color-modal" class="modal-overlay" data-open="<?php echo ($active_tab !== 'list') ? '1' : '0'; ?>">
        <div class="modal-content">
            <button type="button" class="modal-close">&times;</button>
            <h2><?php echo $active_tab === 'edit' ? 'Farbe bearbeiten' : 'Neue Farbe'; ?></h2>
            <form method="post" class="produkt-compact-form">
                <?php wp_nonce_field('produkt_admin_action', 'fw_nonce'); ?>
                <input type="hidden" name="category_id" value="<?php echo esc_attr($selected_category); ?>">
                <?php if ($active_tab === 'edit' && $edit_item): ?>
                    <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
                <?php endif; ?>
                <div class="produkt-form-group">
                    <label for="color-name">Farbname</label>
                    <input type="text" id="color-name" name="name" value="<?php echo esc_attr($edit_item->name ?? ''); ?>" required>
                </div>
                <div class="produkt-form-group">
                    <label for="color-code">Farbcode</label>
                    <div class="produkt-color-picker">
                        <div class="produkt-color-preview-circle" style="background-color:<?php echo esc_attr($edit_item->color_code ?? '#ffffff'); ?>"></div>
                        <input type="text" class="produkt-color-value" name="color_code" value="<?php echo esc_attr($edit_item->color_code ?? '#ffffff'); ?>">
                        <input type="color" class="produkt-color-input" value="<?php echo esc_attr($edit_item->color_code ?? '#ffffff'); ?>">
                    </div>
                </div>
                <?php if (!$is_sale): ?>
                <div class="produkt-form-group">
                    <label for="color-type">Typ</label>
                    <select name="color_type" id="color-type">
                        <option value="product" <?php selected($edit_item->color_type ?? '', 'product'); ?>>Produktfarbe</option>
                        <option value="frame" <?php selected($edit_item->color_type ?? '', 'frame'); ?>>Gestellfarbe</option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="color_type" value="product">
                <?php endif; ?>
                <div class="produkt-form-group">
                    <label>Bild</label>
                    <div class="image-field-row">
                        <div class="image-preview" style="<?php echo !empty($edit_item->image_url) ? 'background-image:url(' . esc_url($edit_item->image_url) . ');' : ''; ?>"></div>
                        <input type="hidden" name="image_url" value="<?php echo esc_url($edit_item->image_url ?? ''); ?>">
                        <button type="button" class="icon-btn image-select" aria-label="Bild auswählen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
                        </button>
                        <button type="button" class="icon-btn image-remove" aria-label="Bild entfernen">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18 .4.3 18.1.3 40s17.7 39.6 39.6 39.6 39.6-17.7 39.6-39.6S61.7.4 39.8.4ZM39.8 71.3c-17.1 0-31.2-14-31.2-31.2s14.2-31.2 31.2-31.2 31.2 14 31.2 31.2-14.2 31.2-31.2 31.2Z"/><path d="M53 26.9c-1.7-1.7-4.2-1.7-5.8 0l-7.3 7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8 0-1.7 1.7-1.7 4.2 0 5.8l7.3 7.3-7.3 7.3c-1.7 1.7-1.7 4.2 0 5.8.8.8 1.9 1.2 2.9 1.2s2.1-.4 2.9-1.2l7.3-7.3 7.3 7.3c.8.8 1.9 1.2 2.9 1.2s2.1-.4 2.9-1.2c1.7-1.7 1.7-4.2 0-5.8l-7.3-7.3 7.3-7.3c1.7-1.7 1.7-4.4 0-5.8Z"/></svg>
                        </button>
                    </div>
                </div>
                <div class="produkt-form-group full-width">
                    <label>Verfügbarkeit je Ausführung</label>
                    <div class="variant-availability-grid">
                        <?php foreach ($variants as $v):
                            $opt_type = (!$is_sale && $edit_item && $edit_item->color_type === 'frame') ? 'frame_color' : 'product_color';
                            $available = $wpdb->get_var($wpdb->prepare(
                                "SELECT available FROM {$wpdb->prefix}produkt_variant_options WHERE variant_id = %d AND option_type = %s AND option_id = %d",
                                $v->id, $opt_type, $edit_item->id ?? 0
                            )); ?>
                        <label class="produkt-toggle-label" style="min-width:160px;">
                            <input type="checkbox" name="variant_available[<?php echo $v->id; ?>]" <?php checked($available,1); ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span><?php echo esc_html($v->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p>
                    <button type="submit" name="submit" class="icon-btn" aria-label="Speichern">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>
