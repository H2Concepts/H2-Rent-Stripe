<?php
// Extras Edit Tab Content
?>

<div class="produkt-edit-extra">
    <div class="produkt-form-header">
        <h3>Extra bearbeiten</h3>
        <p>Bearbeiten Sie das Extra "<?php echo esc_html($edit_item->name); ?>" für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>"</p>
    </div>

    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">

        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name und Preis</p>
                <?php $modus = get_option('produkt_betriebsmodus', 'miete');
                      $sale_price = 0;
                      if ($modus === 'kauf' && !empty($edit_item->stripe_price_id_sale)) {
                          $p = \ProduktVerleih\StripeService::get_price_amount($edit_item->stripe_price_id_sale);
                          if (!is_wp_error($p)) {
                              $sale_price = $p;
                          }
                      }
                ?>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Name *</label>
                        <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                    </div>
                    <?php if ($modus === 'kauf'): ?>
                    <div class="produkt-form-group">
                        <label>Preis / Tag (EUR) *</label>
                        <input type="number" step="0.01" name="sale_price" value="<?php echo esc_attr($sale_price); ?>" required>
                    </div>
                    <?php else: ?>
                    <div class="produkt-form-group">
                        <label>Preis (EUR) *</label>
                        <input type="number" step="0.01" name="price" value="<?php echo esc_attr($edit_item->price); ?>" required>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Extra-Bild</h2>
                <p class="card-subline">Vorschau</p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Extra-Bild</label>
                        <div class="image-field-row">
                            <div id="image_url_preview" class="image-preview">
                                <?php if (!empty($edit_item->image_url)) : ?>
                                    <img src="<?php echo esc_url($edit_item->image_url); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="image_url" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 5h-1c-.552 0-1 .448-1 1v13c0 .552.448 1 1 1h18c.552 0 1-.448 1-1v-13c0-.552-.448-1-1-1h-1"/><path d="M4 5l2-3h12l2 3"/><circle cx="12" cy="13" r="4"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="image_url" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="image_url" id="image_url" value="<?php echo esc_attr($edit_item->image_url ?? ''); ?>">
                        <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
                    </div>
                </div>
            </div>

            <?php if (!empty($variants)): ?>
            <div class="dashboard-card">
                <h2>Verfügbarkeit je Ausführung</h2>
                <p class="card-subline">Extras pro Variante aktivieren</p>
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

            <div class="dashboard-card">
                <h2>Sortierung</h2>
                <p class="card-subline">Reihenfolge im Shop</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Sortierung</label>
                        <input type="number" name="sort_order" value="<?php echo $edit_item->sort_order; ?>" min="0">
                    </div>
                </div>
            </div>
        </div>

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
    document.querySelectorAll('.produkt-media-button').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            const frame = wp.media({title: 'Bild auswählen', button: {text: 'Bild verwenden'}, multiple: false});
            frame.on('select', function(){
                const attachment = frame.state().get('selection').first().toJSON();
                if (target) target.value = attachment.url;
                if (preview) preview.innerHTML = '<img src="'+attachment.url+'" alt="">';
            });
            frame.open();
        });
    });
    document.querySelectorAll('.produkt-remove-image').forEach(function(btn){
        btn.addEventListener('click', function(){
            const target = document.getElementById(this.dataset.target);
            const preview = document.getElementById(this.dataset.target + '_preview');
            if (target) target.value = '';
            if (preview) preview.innerHTML = '<span>Noch kein Bild vorhanden</span>';
        });
    });
});
</script>
