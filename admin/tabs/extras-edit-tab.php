<?php
// Extras Edit Tab Content
?>

<div class="produkt-edit-extra">
    <div class="produkt-form-header">
        <h3>✏️ Extra bearbeiten</h3>
        <p>Bearbeiten Sie das Extra "<?php echo esc_html($edit_item->name); ?>" für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>
    
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        
        <!-- Grunddaten -->
        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <?php
                  $modus       = get_option('produkt_betriebsmodus', 'miete');
                  $sale_price  = '';
                  $price_value = '';

                  if (!empty($edit_item->stripe_price_id_rent)) {
                      $p = \ProduktVerleih\StripeService::get_price_amount($edit_item->stripe_price_id_rent);
                      if (!is_wp_error($p)) {
                          $price_value = number_format((float) $p, 2, ',', '.');
                      }
                  } elseif ($edit_item->price !== '') {
                      $price_value = number_format((float) $edit_item->price, 2, ',', '.');
                  }

                  if ($modus === 'kauf' && !empty($edit_item->stripe_price_id_sale)) {
                      $p = \ProduktVerleih\StripeService::get_price_amount($edit_item->stripe_price_id_sale);
                      if (!is_wp_error($p)) {
                          $sale_price = number_format((float) $p, 2, ',', '.');
                      }
                  }
            ?>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                </div>
                <div class="produkt-form-group">
                    <label>Preis (EUR)<?php echo $modus === 'kauf' ? '' : ' *'; ?></label>
                    <input type="number" step="0.01" name="price" value="<?php echo esc_attr($price_value); ?>" <?php echo $modus === 'kauf' ? '' : 'required'; ?>>
                </div>
                <?php if ($modus === 'kauf'): ?>
                <div class="produkt-form-group">
                    <label>Einmalpreis (EUR) *</label>
                    <input type="number" step="0.01" name="sale_price" value="<?php echo esc_attr($sale_price); ?>" required>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Bild -->
        <div class="produkt-form-section">
            <h4>📸 Extra-Bild</h4>
            <div class="produkt-form-group">
                <label>Extra-Bild</label>
                <div class="produkt-upload-area">
                    <input type="url" name="image_url" id="image_url" value="<?php echo esc_attr($edit_item->image_url ?? ''); ?>">
                    <button type="button" class="button produkt-media-button" data-target="image_url">📁 Aus Mediathek wählen</button>
                </div>
                <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
                
                <?php if (!empty($edit_item->image_url)): ?>
                <div class="produkt-image-preview">
                    <img src="<?php echo esc_url($edit_item->image_url); ?>" alt="Extra-Bild">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Einstellungen -->
        <div class="produkt-form-section">
            <h4>⚙️ Einstellungen</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                </div>
            </div>
        </div>

        <?php if (!empty($variants)): ?>
        <div class="produkt-form-section">
            <h4>🚀 Verfügbarkeit je Ausführung</h4>
            <div class="produkt-form-row" style="flex-wrap:wrap;gap:15px;">
                <?php foreach ($variants as $v): ?>
                <?php $checked = isset($variant_availability[$v->id]) ? $variant_availability[$v->id] : 1; ?>
                <label class="produkt-toggle-label" style="min-width:160px;">
                    <input type="checkbox" name="variant_available[<?php echo $v->id; ?>]" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                    <span class="produkt-toggle-slider"></span>
                    <span><?php echo esc_html($v->name); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">
                ✅ Änderungen speichern
            </button>
            <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">
                ❌ Abbrechen
            </a>
            <a href="<?php echo admin_url('admin.php?page=produkt-extras&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
               class="button button-large produkt-delete-button"
               onclick="return confirm('Sind Sie sicher, dass Sie dieses Extra löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')"
               style="margin-left: auto;">
                🗑️ Löschen
            </a>
        </div>
    </form>
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
