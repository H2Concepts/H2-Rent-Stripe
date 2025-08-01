<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_content_blocks';

$categories = \ProduktVerleih\Database::get_product_categories_tree();
array_unshift($categories, (object) ['id' => 0, 'name' => 'Alle Kategorien']);
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_term       = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$action            = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$edit_id           = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

// Statistik Werte
$total_blocks   = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$category_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}produkt_categories");
$wide_count     = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE style='wide'");
$compact_count  = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE style='compact'");

if (isset($_POST['save_block'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $category_id = intval($_POST['category_id']);
    $data = [
        'category_id'      => $category_id,
        'style'        => sanitize_text_field($_POST['style']),
        'position'     => intval($_POST['position']),
        'position_mobile' => intval($_POST['position_mobile']),
        'title'        => sanitize_text_field($_POST['title']),
        'content'      => wp_kses_post($_POST['content']),
        'image_url'    => esc_url_raw($_POST['image_url']),
        'button_text'  => sanitize_text_field($_POST['button_text']),
        'button_url'   => esc_url_raw($_POST['button_url']),
        'background_color' => sanitize_hex_color($_POST['background_color']),
        'badge_text'  => sanitize_text_field($_POST['badge_text']),
    ];
    if (!empty($_POST['id'])) {
        $wpdb->update($table_name, $data, ['id' => intval($_POST['id'])]);
    } else {
        $wpdb->insert($table_name, $data);
    }
    \ProduktVerleih\Database::clear_content_blocks_cache($category_id);
    $action = 'list';
}

if (isset($_GET['delete']) && isset($_GET['fw_nonce']) && wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    $wpdb->delete($table_name, ['id' => intval($_GET['delete'])]);
    \ProduktVerleih\Database::clear_content_blocks_cache($selected_category);
}

$block = null;
if ($action === 'edit' && $edit_id) {
    $block = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
    if (!$block) { $action = 'list'; }
}

$sql_blocks = "SELECT * FROM $table_name";
$params = [];
$clauses = [];
if ($selected_category > 0) {
    $clauses[] = "category_id = %d";
    $params[] = $selected_category;
}
if ($search_term !== '') {
    $clauses[] = "title LIKE %s";
    $params[] = '%' . $wpdb->esc_like($search_term) . '%';
}
if ($clauses) {
    $sql_blocks .= ' WHERE ' . implode(' AND ', $clauses);
}
$sql_blocks .= ' ORDER BY position';
$blocks = !empty($params) ? $wpdb->get_results($wpdb->prepare($sql_blocks, ...$params)) : $wpdb->get_results($sql_blocks);
?>
<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting">Hallo, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Content-Blöcke verwalten</p>

<?php if ($action === 'list'): ?>
    <div class="product-info-grid cols-4">
        <div class="product-info-box bg-pastell-gelb">
            <span class="label">Blöcke</span>
            <strong class="value"><?php echo intval($total_blocks); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-gruen">
            <span class="label">Kategorien</span>
            <strong class="value"><?php echo intval($category_count); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-mint">
            <span class="label">Weit</span>
            <strong class="value"><?php echo intval($wide_count); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-orange">
            <span class="label">Kompakt</span>
            <strong class="value"><?php echo intval($compact_count); ?></strong>
        </div>
    </div>

    <div class="h2-rental-card">
        <div class="card-header-flex">
            <div>
                <h2>Content Blöcke</h2>
                <p class="card-subline">Blöcke verwalten</p>
            </div>
            <div class="card-header-actions">
                <form method="get" class="produkt-filter-form product-search-bar">
                    <input type="hidden" name="page" value="produkt-content-blocks">
                    <div class="search-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon">
                            <path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/>
                        </svg>
                        <input type="text" name="s" placeholder="Suchen" value="<?php echo esc_attr($search_term); ?>">
                    </div>
                    <select name="category">
                        <option value="0">Alle Kategorien</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php selected($selected_category, $cat->id); ?>><?php echo str_repeat('--', $cat->depth ?? 0) . ' ' . esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="icon-btn filter-submit-btn" aria-label="Filtern">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                            <path d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z"/>
                        </svg>
                    </button>
                </form>
                <a id="add-category-btn" href="<?php echo admin_url('admin.php?page=produkt-content-blocks&action=add'); ?>" class="icon-btn add-category-btn" aria-label="Hinzufügen">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                        <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                        <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                    </svg>
                </a>
            </div>
        </div>
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Titel</th>
                    <th>Layout</th>
                    <th>Badge-Text</th>
                    <th>Position Desktop</th>
                    <th>Position Mobil</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocks as $b): ?>
                    <tr>
                        <td><?php echo esc_html($b->title); ?></td>
                        <td><?php echo esc_html($b->style); ?></td>
                        <td><?php echo esc_html($b->badge_text); ?></td>
                        <td><?php echo intval($b->position); ?></td>
                        <td><?php echo intval($b->position_mobile); ?></td>
                        <td>
                            <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-content-blocks&action=edit&edit=<?php echo $b->id; ?>'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                    <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                    <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                </svg>
                            </button>
                            <button type="button" class="icon-btn" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-content-blocks&delete=<?php echo $b->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}" aria-label="Löschen">
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
<?php else: ?>
    <h2><?php echo $action === 'edit' ? 'Block bearbeiten' : 'Neuen Block hinzufügen'; ?></h2>
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($block->id ?? ''); ?>">
        <table class="form-table">
            <tr>
                <th><label>Kategorie *</label></th>
                <td>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php selected(($block->category_id ?? $selected_category), $cat->id); ?>><?php echo str_repeat('--', $cat->depth ?? 0) . ' ' . esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Layout</label></th>
                <td>
                    <select name="style">
                        <option value="compact" <?php selected($block->style ?? 'wide', 'compact'); ?>>Kompakt</option>
                        <option value="wide" <?php selected($block->style ?? 'wide', 'wide'); ?>>Weit</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Position Desktop *</label></th>
                <td><input type="number" name="position" required value="<?php echo esc_attr($block->position ?? 9); ?>"></td>
            </tr>
            <tr>
                <th><label>Position Mobil *</label></th>
                <td><input type="number" name="position_mobile" required value="<?php echo esc_attr($block->position_mobile ?? 6); ?>"></td>
            </tr>
            <tr>
                <th><label>Überschrift *</label></th>
                <td><input type="text" name="title" required value="<?php echo esc_attr($block->title ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label>Text *</label></th>
                <td><textarea name="content" rows="4" required><?php echo esc_textarea($block->content ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label>Bild</label></th>
                <td>
                    <input type="url" name="image_url" id="image_url" value="<?php echo esc_attr($block->image_url ?? ''); ?>">
                    <button type="button" class="button produkt-media-button" data-target="image_url">📁</button>
                </td>
            </tr>
            <tr>
                <th><label>Button-Text</label></th>
                <td><input type="text" name="button_text" value="<?php echo esc_attr($block->button_text ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label>Button-Link</label></th>
                <td><input type="url" name="button_url" value="<?php echo esc_attr($block->button_url ?? ''); ?>"></td>
            </tr>
            <tr>
                <th><label>Hintergrundfarbe</label></th>
                <td><input type="color" name="background_color" value="<?php echo esc_attr($block->background_color ?? '#ffffff'); ?>"></td>
            </tr>
            <tr>
                <th><label>Badge-Text</label></th>
                <td><input type="text" name="badge_text" value="<?php echo esc_attr($block->badge_text ?? ''); ?>"></td>
            </tr>
        </table>
        <p>
            <button type="submit" name="save_block" class="button button-primary">Speichern</button>
            <a href="<?php echo admin_url('admin.php?page=produkt-content-blocks'); ?>" class="button">Abbrechen</a>
        </p>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.produkt-media-button').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const field = document.getElementById(targetId);
                if (!field) return;
                const frame = wp.media({ title: 'Bild auswählen', button: { text: 'Bild verwenden' }, multiple: false });
                frame.on('select', function() {
                    const att = frame.state().get('selection').first().toJSON();
                    field.value = att.url;
                });
                frame.open();
            });
        });
    });
    </script>
<?php endif; ?>
</div>
