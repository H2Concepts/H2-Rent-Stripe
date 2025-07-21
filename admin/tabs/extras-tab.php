<?php
use ProduktVerleih\StripeService;
require_once plugin_dir_path(__FILE__) . '/../../includes/stripe-sync.php';
// Extras Tab Content
$table_name = $wpdb->prefix . 'produkt_extras';
// Ensure necessary columns exist
$product_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_product_id'");
if (empty($product_id_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_product_id VARCHAR(255) DEFAULT NULL AFTER name");
}
$price_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id'");
if (empty($price_id_exists)) {
    $after = $product_id_exists ? 'stripe_product_id' : 'name';
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id VARCHAR(255) DEFAULT NULL AFTER $after");
}

// Handle form submissions
if (isset($_POST['submit_extra'])) {
    $category_id = intval($_POST['category_id']);
    $name        = sanitize_text_field($_POST['name']);
    $price       = floatval($_POST['price']);
    $image_url   = esc_url_raw($_POST['image_url']);
    $sort_order  = intval($_POST['sort_order']);

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
                'image_url'   => $image_url,
                'sort_order'  => $sort_order
            ),
            array('id' => intval($_POST['id'])),
            array('%d', '%s', '%f', '%s', '%d'),
            array('%d')
        );

        $extra_id = intval($_POST['id']);
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
            $mode          = get_option('produkt_betriebsmodus', 'miete');
            $ids           = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id FROM $table_name WHERE id = %d", $extra_id));

            if ($ids && $ids->stripe_product_id) {
                \ProduktVerleih\StripeService::update_product_name($ids->stripe_product_id, $stripe_product_name);
                $new_price = \ProduktVerleih\StripeService::create_price($ids->stripe_product_id, round($price * 100), 'miete');
                if (!is_wp_error($new_price)) {
                    $wpdb->update($table_name, ['stripe_price_id' => $new_price->id], ['id' => $extra_id], ['%s'], ['%d']);
                }
            } else {
                $res = \ProduktVerleih\StripeService::create_extra_price($extra_base_name, $price, $main_product_name);
                if (!is_wp_error($res)) {
                    $wpdb->update($table_name, [
                        'stripe_product_id' => $res['product_id'],
                        'stripe_price_id'   => $res['price_id'],
                    ], ['id' => $extra_id]);
                }
            }
        }
    } else {
        // Insert
        $result = $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'name'        => $name,
                'price'       => $price,
                'image_url'   => $image_url,
                'sort_order'  => $sort_order
            ),
            array('%d', '%s', '%f', '%s', '%d')
        );

        $extra_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich hinzugefügt!</p></div>';
            $mode         = get_option('produkt_betriebsmodus', 'miete');
            $res = \ProduktVerleih\StripeService::create_extra_price($extra_base_name, $price, $main_product_name);
            if (!is_wp_error($res)) {
                $wpdb->update($table_name, [
                    'stripe_product_id' => $res['product_id'],
                    'stripe_price_id'   => $res['price_id'],
                ], ['id' => $extra_id]);
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete_extra'])) {
    $del_id = intval($_GET['delete_extra']);
    $row = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id FROM $table_name WHERE id = %d", $del_id));
    if ($row && $row->stripe_product_id) {
        produkt_delete_or_archive_stripe_product($row->stripe_product_id, $del_id, 'produkt_extras');
    }
    $result = $wpdb->delete($table_name, array('id' => $del_id), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Extra gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_extra'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_extra'])));
}

// Get all extras for selected category
$extras = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="produkt-tab-section">
    <h3>🎁 Extras mit Bildern</h3>
    <p>Verwalten Sie Zusatzoptionen mit Bildern, die über dem Hauptbild angezeigt werden.</p>
    
    <!-- Form -->
    <div class="produkt-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
                <h4>Extra bearbeiten</h4>
            <?php else: ?>
                <h4>Neues Extra hinzufügen</h4>
            <?php endif; ?>
            
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <div class="produkt-form-group">
                    <label>Preis (EUR) *</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $edit_item ? esc_attr($edit_item->price) : ''; ?>" placeholder="0.00" required>
                </div>
                
                <div class="produkt-form-group full-width">
                    <label>📸 Extra-Bild</label>
                    <div class="produkt-media-upload">
                        <input type="url" name="image_url" id="image_url" value="<?php echo $edit_item ? esc_attr($edit_item->image_url ?? '') : ''; ?>" placeholder="https://example.com/extra-bild.jpg">
                        <button type="button" class="button produkt-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                    </div>
                    <small>Wird als Overlay über dem Hauptbild angezeigt</small>
                    <?php if ($edit_item && !empty($edit_item->image_url)): ?>
                        <div class="produkt-image-preview">
                            <img src="<?php echo esc_url($edit_item->image_url); ?>" alt="Extra-Bild">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="produkt-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_extra', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=extras'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="produkt-list-card">
        <h4>Vorhandene Extras</h4>
        
        <?php if (empty($extras)): ?>
        <div class="produkt-empty-state">
            <p>Noch keine Extras für dieses Produkt vorhanden.</p>
            <p><strong>Tipp:</strong> Fügen Sie oben ein neues Extra hinzu!</p>
        </div>
        <?php else: ?>
        
        <div class="produkt-items-grid">
            <?php foreach ($extras as $extra): ?>
            <div class="produkt-item-card">
                <div class="produkt-item-images">
                    <?php 
                    $image_url = isset($extra->image_url) ? $extra->image_url : '';
                    if (!empty($image_url)): 
                    ?>
                        <img src="<?php echo esc_url($image_url); ?>" class="produkt-main-image" alt="<?php echo esc_attr($extra->name); ?>">
                    <?php else: ?>
                        <div class="produkt-placeholder">🎁</div>
                    <?php endif; ?>
                </div>
                
                <div class="produkt-item-content">
                    <h5><?php echo esc_html($extra->name); ?></h5>
                    <div class="produkt-item-meta">
                        <?php
                        $display_price = $extra->price;
                        if (!empty($extra->stripe_price_id)) {
                            $p = \ProduktVerleih\StripeService::get_price_amount($extra->stripe_price_id);
                            if (!is_wp_error($p)) {
                                $display_price = $p;
                            }
                        }
                        if ($display_price > 0) {
                            echo '<span class="produkt-price">' . number_format($display_price, 2, ',', '.') . '€</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="produkt-item-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=extras&edit_extra=' . $extra->id); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=extras&delete_extra=' . $extra->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>
