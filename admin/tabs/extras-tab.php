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
$rent_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id_rent'");
if (empty($rent_id_exists)) {
    $after = !empty($price_id_exists) ? 'stripe_price_id' : ($product_id_exists ? 'stripe_product_id' : 'name');
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id_rent VARCHAR(255) DEFAULT NULL AFTER $after");
}
$sale_id_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stripe_price_id_sale'");
if (empty($sale_id_exists)) {
    $after = !empty($rent_id_exists) ? 'stripe_price_id_rent' : (!empty($price_id_exists) ? 'stripe_price_id' : ($product_id_exists ? 'stripe_product_id' : 'name'));
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stripe_price_id_sale VARCHAR(255) DEFAULT NULL AFTER $after");
}

// Handle form submissions
if (isset($_POST['submit_extra'])) {
    $category_id = intval($_POST['category_id']);
    $name        = sanitize_text_field($_POST['name']);
    $modus       = get_option('produkt_betriebsmodus', 'miete');
    $sale_price  = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : 0;
    $price       = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $price_input = $modus === 'kauf' ? ($_POST['sale_price'] ?? '') : ($_POST['price'] ?? '');
    $stripe_price = floatval($price_input);
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
                'sort_order'  => $sort_order
            ),
            array('id' => $extra_id),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich aktualisiert!</p></div>';
            $mode = get_option('produkt_betriebsmodus', 'miete');

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
                    $needs_price_update = true;
                }
            }
            if (!$existing || empty($existing->stripe_product_id)) {
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
                'price_sale'  => ($modus === 'kauf') ? $sale_price : 0,
                'price_rent'  => ($modus === 'kauf') ? 0 : $price,
                'image_url'   => $image_url,
                'sort_order'  => $sort_order
            ),
            array('%d', '%s', '%f', '%f', '%f', '%s', '%d')
        );

        $extra_id = $wpdb->insert_id;
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Extra erfolgreich hinzugefügt!</p></div>';
            $mode         = get_option('produkt_betriebsmodus', 'miete');
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
            
            <?php $modus = get_option('produkt_betriebsmodus', 'miete');
                  $sale_price = 0;
                  if ($modus === 'kauf') {
                      if ($edit_item && !empty($edit_item->stripe_price_id_sale)) {
                          $p = \ProduktVerleih\StripeService::get_price_amount($edit_item->stripe_price_id_sale);
                          if (!is_wp_error($p)) {
                              $sale_price = $p;
                          } elseif (!empty($edit_item->price_sale)) {
                              $sale_price = $edit_item->price_sale;
                          }
                      } elseif ($edit_item && isset($edit_item->price_sale)) {
                          $sale_price = $edit_item->price_sale;
                      }
                  }
            ?>
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>

                <?php if ($modus === 'kauf'): ?>
                <div class="produkt-form-group">
                    <label>Preis / Tag (EUR) *</label>
                    <input type="number" step="0.01" name="sale_price" value="<?php echo esc_attr($sale_price); ?>" placeholder="0.00" required>
                </div>
                <?php else: ?>
                <div class="produkt-form-group">
                    <label>Preis (EUR) *</label>
                    <input type="number" step="0.01" name="price" value="<?php echo $edit_item ? esc_attr($edit_item->price_rent ?? $edit_item->price) : ''; ?>" placeholder="0.00" required>
                </div>
                <?php endif; ?>
                
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
                        $display_price = ($modus === 'kauf') ? ($extra->price_sale ?? $extra->price) : ($extra->price_rent ?? $extra->price);
                        $price_col = ($modus === 'kauf') ? ($extra->stripe_price_id_sale ?? '') : ($extra->stripe_price_id_rent ?? '');
                        if (!empty($price_col)) {
                            $p = \ProduktVerleih\StripeService::get_price_amount($price_col);
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
