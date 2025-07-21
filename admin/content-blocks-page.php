<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$table_name = $wpdb->prefix . 'produkt_content_blocks';

$categories = \ProduktVerleih\Database::get_product_categories_tree();
array_unshift($categories, (object)['id' => 0, 'name' => 'Alle Kategorien']);
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : ($categories[0]->id ?? 0);
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

if (isset($_POST['save_block'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $data = [
        'category_id' => $selected_category,
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
    \ProduktVerleih\Database::clear_content_blocks_cache($selected_category);
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

$blocks = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY position", $selected_category));
?>
<div class="wrap">
    <h1>Content-Blöcke</h1>

    <form method="get" action="">
        <input type="hidden" name="page" value="produkt-content-blocks">
        <label for="cb-category-select"><strong>Kategorie:</strong></label>
        <select id="cb-category-select" name="category" onchange="this.form.submit()">
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat->id; ?>" <?php selected($selected_category, $cat->id); ?>><?php echo str_repeat('--', $cat->depth ?? 0) . ' ' . esc_html($cat->name); ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><input type="submit" value="Wechseln" class="button"></noscript>
    </form>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <h2><?php echo $action === 'edit' ? 'Block bearbeiten' : 'Neuen Block hinzufügen'; ?></h2>
        <form method="post" action="" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($block->id ?? ''); ?>">
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            <table class="form-table">
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
                <a href="<?php echo admin_url('admin.php?page=produkt-content-blocks&category=' . $selected_category); ?>" class="button">Abbrechen</a>
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
    <?php else: ?>
        <h2>Blöcke</h2>
        <p><a href="<?php echo admin_url('admin.php?page=produkt-content-blocks&category=' . $selected_category . '&action=add'); ?>" class="button button-primary">Neuen Block hinzufügen</a></p>
        <?php if (empty($blocks)): ?>
            <p>Noch keine Blöcke definiert.</p>
        <?php else: ?>
            <table class="widefat striped">
                <thead><tr><th>Desktop</th><th>Mobil</th><th>Titel</th><th>Aktionen</th></tr></thead>
                <tbody>
                    <?php foreach ($blocks as $b): ?>
                        <tr>
                            <td><?php echo intval($b->position); ?></td>
                            <td><?php echo intval($b->position_mobile); ?></td>
                            <td><?php echo esc_html($b->title); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=produkt-content-blocks&category=' . $selected_category . '&action=edit&edit=' . $b->id); ?>" class="button">Bearbeiten</a>
                                <a href="<?php echo admin_url('admin.php?page=produkt-content-blocks&category=' . $selected_category . '&delete=' . $b->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" class="button button-danger" onclick="return confirm('Wirklich löschen?')">Löschen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
