<?php
// Variants Tab Content
$modus = get_option('produkt_betriebsmodus', 'miete');
$table_name = $wpdb->prefix . 'produkt_variants';
require_once plugin_dir_path(__FILE__) . '/../../includes/stripe-sync.php';

// Handle form submissions
if (isset($_POST['submit_variant'])) {
    $category_id = intval($_POST['category_id']);
    $name = sanitize_text_field($_POST['name']);
    $description = sanitize_textarea_field($_POST['description']);
    $mietpreis_monatlich    = isset($_POST['mietpreis_monatlich']) ? floatval($_POST['mietpreis_monatlich']) : 0;
    $verkaufspreis_einmalig = isset($_POST['verkaufspreis_einmalig']) ? floatval($_POST['verkaufspreis_einmalig']) : 0;
    $available = isset($_POST['available']) ? 1 : 0;
    $availability_note = sanitize_text_field($_POST['availability_note']);
    $sort_order = intval($_POST['sort_order']);
    
    // Handle multiple images
    $image_data = array();
    for ($i = 1; $i <= 5; $i++) {
        $image_data['image_url_' . $i] = esc_url_raw($_POST['image_url_' . $i] ?? '');
    }

    if (isset($_POST['id']) && $_POST['id']) {
        // Update
        $update_data = array_merge(array(
            'category_id'            => $category_id,
            'name'                   => $name,
            'description'            => $description,
            'mietpreis_monatlich'    => $mietpreis_monatlich,
            'verkaufspreis_einmalig' => $verkaufspreis_einmalig,
            'available'              => $available,
            'availability_note'      => $availability_note,
            'sort_order'             => $sort_order
        ), $image_data);
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => intval($_POST['id'])),
            array_merge(array('%d', '%s', '%s', '%f', '%f', '%d', '%s', '%d'), array_fill(0, 5, '%s')),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Ausführung erfolgreich aktualisiert!</p></div>';
        }
    } else {
        // Insert
        $insert_data = array_merge(array(
            'category_id'            => $category_id,
            'name'                   => $name,
            'description'            => $description,
            'mietpreis_monatlich'    => $mietpreis_monatlich,
            'verkaufspreis_einmalig' => $verkaufspreis_einmalig,
            'available'              => $available,
            'availability_note'      => $availability_note,
            'sort_order'             => $sort_order
        ), $image_data);
        
        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            array_merge(array('%d', '%s', '%s', '%f', '%f', '%d', '%s', '%d'), array_fill(0, 5, '%s'))
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Ausführung erfolgreich hinzugefügt!</p></div>';
        }
    }
}

// Handle delete
if (isset($_GET['delete_variant'])) {
    $del_id = intval($_GET['delete_variant']);
    $row = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id FROM $table_name WHERE id = %d", $del_id));
    if ($row && $row->stripe_product_id) {
        produkt_delete_or_archive_stripe_product($row->stripe_product_id);
    }
    $result = $wpdb->delete($table_name, array('id' => $del_id), array('%d'));
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Ausführung gelöscht!</p></div>';
    }
}

// Get item for editing
$edit_item = null;
if (isset($_GET['edit_variant'])) {
    $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($_GET['edit_variant'])));
}

// Get all variants for selected category
$variants = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name", $selected_category));
?>

<div class="produkt-tab-section">
    <h3>🖼️ Ausführungen mit Bildergalerie</h3>
    <p>Verwalten Sie Produktausführungen mit bis zu 5 Bildern pro Ausführung und Verfügbarkeitsstatus.</p>
    
    <!-- Form -->
    <div class="produkt-form-card">
        <form method="post" action="">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
                <h4>Ausführung bearbeiten</h4>
            <?php else: ?>
                <h4>Neue Ausführung hinzufügen</h4>
            <?php endif; ?>
            
            <div class="produkt-form-grid">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo $edit_item ? esc_attr($edit_item->name) : ''; ?>" required>
                </div>
                
                <?php if ($modus !== 'kauf'): ?>
                <div class="produkt-form-group">
                    <label>Monatlicher Mietpreis *</label>
                    <input type="number" step="0.01" name="mietpreis_monatlich" value="<?php echo $edit_item ? esc_attr($edit_item->mietpreis_monatlich) : ''; ?>" required>
                </div>
                <?php else: ?>
                    <input type="hidden" name="mietpreis_monatlich" value="0">
                <?php endif; ?>
                <div class="produkt-form-group">
                    <label><?php echo ($modus === 'kauf') ? 'Preis pro Tag' : 'Einmaliger Verkaufspreis'; ?></label>
                    <input type="number" step="0.01" name="verkaufspreis_einmalig" value="<?php echo $edit_item ? esc_attr($edit_item->verkaufspreis_einmalig) : ''; ?>">
                </div>
                
                <div class="produkt-form-group full-width">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3"><?php echo $edit_item ? esc_textarea($edit_item->description) : ''; ?></textarea>
                </div>
                
                <div class="produkt-form-group">
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="available" value="1" <?php echo (!$edit_item || $edit_item->available) ? 'checked' : ''; ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Verfügbar</span>
                    </label>
                </div>
                
                <div class="produkt-form-group">
                    <label>Verfügbarkeits-Hinweis</label>
                    <input type="text" name="availability_note" value="<?php echo $edit_item ? esc_attr($edit_item->availability_note ?? '') : ''; ?>" placeholder="z.B. 'Wieder verfügbar ab 15.03.2024'">
                </div>
                
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item ? $edit_item->sort_order : '0'; ?>" min="0">
                </div>
                
            </div>
            
            <!-- Images Section -->
            <h5>📸 Produktbilder (bis zu 5 Bilder)</h5>
            <div class="produkt-images-grid">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="produkt-image-upload">
                    <label><?php echo $i === 1 ? '🌟 Hauptbild' : 'Bild ' . $i; ?></label>
                    <div class="produkt-media-upload">
                        <input type="url" name="image_url_<?php echo $i; ?>" id="image_url_<?php echo $i; ?>" value="<?php echo $edit_item ? esc_attr($edit_item->{'image_url_' . $i} ?? '') : ''; ?>" placeholder="https://example.com/bild<?php echo $i; ?>.jpg">
                        <button type="button" class="button produkt-media-button" data-target="image_url_<?php echo $i; ?>">📁 Wählen</button>
                    </div>
                    <?php if ($edit_item && !empty($edit_item->{'image_url_' . $i})): ?>
                        <div class="produkt-image-preview">
                            <img src="<?php echo esc_url($edit_item->{'image_url_' . $i}); ?>" alt="Bild <?php echo $i; ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            
            <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
            
            <div class="produkt-form-actions">
                <?php submit_button($edit_item ? 'Aktualisieren' : 'Hinzufügen', 'primary', 'submit_variant', false); ?>
                <?php if ($edit_item): ?>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=variants'); ?>" class="button">Abbrechen</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- List -->
    <div class="produkt-list-card">
        <h4>Vorhandene Ausführungen</h4>
        
        <?php if (empty($variants)): ?>
        <div class="produkt-empty-state">
            <p>Noch keine Ausführungen für dieses Produkt vorhanden.</p>
            <p><strong>Tipp:</strong> Fügen Sie oben eine neue Ausführung hinzu!</p>
        </div>
        <?php else: ?>
        
        <div class="produkt-items-grid">
            <?php foreach ($variants as $variant): ?>
            <div class="produkt-item-card">
                <div class="produkt-item-images">
                    <?php 
                    $image_count = 0;
                    for ($i = 1; $i <= 5; $i++): 
                        $image_field = 'image_url_' . $i;
                        $image_url = isset($variant->$image_field) ? $variant->$image_field : '';
                        if (!empty($image_url)): 
                            $image_count++;
                            if ($i === 1): // Show main image larger
                    ?>
                                <img src="<?php echo esc_url($image_url); ?>" class="produkt-main-image" alt="Hauptbild">
                    <?php 
                            endif;
                        endif;
                    endfor; 
                    
                    if ($image_count === 0):
                    ?>
                        <div class="produkt-placeholder">👶</div>
                    <?php else: ?>
                        <div class="produkt-image-count"><?php echo $image_count; ?> Bild<?php echo $image_count > 1 ? 'er' : ''; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="produkt-item-content">
                    <h5><?php echo esc_html($variant->name); ?></h5>
                    <p><?php echo esc_html($variant->description); ?></p>
                    <div class="produkt-item-meta">
                        <?php
                            $price = 0;
                            if (!empty($variant->stripe_price_id)) {
                                $p = \ProduktVerleih\StripeService::get_price_amount($variant->stripe_price_id);
                                if (!is_wp_error($p)) {
                                    $price = $p;
                                }
                            }
                        ?>
                        <span class="produkt-price"><?php echo number_format($price, 2, ',', '.'); ?>€</span>
                        <span class="produkt-status <?php echo $variant->available ? 'available' : 'unavailable'; ?>">
                            <?php echo $variant->available ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="produkt-item-actions">
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=variants&edit_variant=' . $variant->id); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo admin_url('admin.php?page=produkt-products&category=' . $selected_category . '&tab=variants&delete_variant=' . $variant->id); ?>" class="button button-small" onclick="return confirm('Sind Sie sicher?')">Löschen</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // WordPress Media Library Integration
    document.querySelectorAll('.produkt-media-button').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            
            if (!targetInput) return;
            
            const mediaUploader = wp.media({
                title: 'Bild auswählen',
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.value = attachment.url;
            });
            
            mediaUploader.open();
        });
    });
});
</script>
