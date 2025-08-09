<?php
// Variants Edit Tab Content
$verkaufspreis_einmalig = floatval($edit_item->verkaufspreis_einmalig);
$modus = get_option('produkt_betriebsmodus', 'miete');
$mietpreis_monatlich = number_format((float)$edit_item->mietpreis_monatlich, 2, '.', '');
$verkaufspreis_formatted = number_format((float)$verkaufspreis_einmalig, 2, '.', '');
?>

<div class="produkt-edit-variant">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="id" value="<?php echo esc_attr($edit_item->id); ?>">
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        <button type="submit" name="submit" class="icon-btn variants-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>
        <a href="<?php echo admin_url('admin.php?page=produkt-variants&category=' . $selected_category . '&delete=' . $edit_item->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>"
           class="icon-btn variants-delete-btn"
           aria-label="Löschen"
           onclick="return confirm('Sind Sie sicher, dass Sie diese Ausführung löschen möchten?\n\n\"<?php echo esc_js($edit_item->name); ?>\" wird unwiderruflich gelöscht!')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.7 90">
                <path fill-rule="evenodd" d="m39.8 24.3h-29.9l4.5 57.2v0.2c0 1.1 0.4 1.9 1 2.5 0.7 0.7 1.8 1.2 3.2 1.4h21.2s21.2 0 21.2 0c1.5-0.3 2.5-0.7 3.2-1.4 0.6-0.6 1-1.5 1-2.7v-0.1l4.5-57.2h-30zm-17.8 14.1c0-1.1 0.7-2 1.8-2.1 1.1 0 2 0.7 2.1 1.8l2.7 33.6c0 1.1-0.7 2-1.8 2.1-1.1 0-2-0.7-2.1-1.8l-2.7-33.6zm31.8-0.3c0-1.1 1-1.9 2.1-1.8 1.1 0 1.9 1 1.8 2.1l-2.7 33.6c0 1.1-1 1.9-2.1 1.8-1.1 0-1.9-1-1.8-2.1l2.7-33.6zm-15.9 0.1c0-1.1 0.9-1.9 1.9-1.9s1.9 0.9 1.9 1.9v33.6c0 1.1-0.9 1.9-1.9 1.9s-1.9-0.9-1.9-1.9v-33.6zm22.7-23.6h-53.1c-0.9 0-1.8 0.3-2.3 0.9-0.6 0.5-0.9 1.3-0.9 2v2.9h35.6s35.6 0 35.6 0v-2.9c0-0.8-0.4-1.5-0.9-2-0.6-0.6-1.4-0.9-2.3-0.9h-11.5zm-53.1-3.9h19.4v-0.8c0-2.4 0.5-4.6 1.3-6.2 1-2 2.5-3.3 4.4-3.3h14.6c1.8 0 3.4 1.3 4.4 3.3 0.8 1.6 1.3 3.8 1.3 6.2v0.8h19.4c1.9 0 3.7 0.8 5 2s2.1 2.9 2.1 4.8v4.9c0 1.1-0.9 1.9-1.9 1.9h-3.7l-4.6 57.5c0 2.1-0.8 3.9-2.1 5.2s-3 2.1-5.3 2.5h-0.5-21.3s-21.3 0-21.3 0h-0.3c-2.4-0.4-4.2-1.2-5.5-2.5s-2-3-2.1-5.2l-4.6-57.5h-3.7c-1.1 0-1.9-0.9-1.9-1.9v-4.9c0-1.9 0.8-3.6 2.1-4.8s3.1-2 5-2zm23.3 0h18.2v-0.8c0-1.8-0.3-3.4-0.9-4.5-0.3-0.7-0.7-1.1-0.9-1.1h-14.6c-0.2 0-0.6 0.4-0.9 1.1-0.6 1.1-0.9 2.7-0.9 4.5v0.8z"/>
            </svg>
        </a>

        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name und Preis</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Name *</label>
                        <input type="text" name="name" value="<?php echo esc_attr($edit_item->name); ?>" required>
                    </div>
                    <?php if ($modus !== 'kauf'): ?>
                    <div class="produkt-form-group">
                        <label>Monatlicher Mietpreis *</label>
                        <input type="number" step="0.01" name="mietpreis_monatlich" value="<?php echo esc_attr($mietpreis_monatlich); ?>" required>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="mietpreis_monatlich" value="0">
                    <?php endif; ?>
                    <div class="produkt-form-group">
                        <label><?php echo ($modus === 'kauf') ? 'Preis / Tag (EUR) *' : 'Einmaliger Verkaufspreis'; ?></label>
                        <input type="number" step="0.01" name="verkaufspreis_einmalig" value="<?php echo esc_attr($verkaufspreis_formatted); ?>">
                    </div>
                </div>
                <div class="produkt-form-group full-width">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3"><?php echo esc_textarea($edit_item->description); ?></textarea>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Verfügbarkeit</h2>
                <p class="card-subline">Buchbarkeit</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="available" value="1" <?php echo ($edit_item->available ?? 1) ? 'checked' : ''; ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span>Verfügbar</span>
                        </label>
                    </div>
                    <div class="produkt-form-group">
                        <label>Text wenn nicht verfügbar</label>
                        <input type="text" name="availability_note" value="<?php echo esc_attr($edit_item->availability_note ?? ''); ?>">
                    </div>
                    <div class="produkt-form-group">
                        <label>Lieferzeit-Text</label>
                        <input type="text" name="delivery_time" value="<?php echo esc_attr($edit_item->delivery_time ?? '3-5 Werktage'); ?>">
                    </div>
                    <?php if ($modus === 'kauf'): ?>
                    <div class="produkt-form-group">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="weekend_only" value="1" <?php echo ($edit_item->weekend_only ?? 0) ? 'checked' : ''; ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span>Nur Wochenende buchbar?</span>
                        </label>
                    </div>
                    <div class="produkt-form-group">
                        <label>Mindestmiettage</label>
                        <input type="number" name="min_rental_days" value="<?php echo intval($edit_item->min_rental_days ?? 0); ?>" min="0">
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Produktbilder</h2>
                <p class="card-subline">Vorschau</p>
                <div class="produkt-images-grid">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php $field = 'image_url_' . $i; ?>
                    <div class="produkt-image-upload">
                        <label><?php echo $i === 1 ? 'Hauptbild' : 'Bild ' . $i; ?></label>
                        <div class="image-field-row">
                            <div id="<?php echo $field; ?>_preview" class="image-preview">
                                <?php if (!empty($edit_item->$field)) : ?>
                                    <img src="<?php echo esc_url($edit_item->$field); ?>" alt="">
                                <?php else: ?>
                                    <span>Noch kein Bild vorhanden</span>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="<?php echo $field; ?>" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="<?php echo $field; ?>" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="<?php echo $field; ?>" id="<?php echo $field; ?>" value="<?php echo esc_attr($edit_item->$field ?? ''); ?>">
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

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

