<?php
// Variants Edit Tab Content
$verkaufspreis_einmalig = floatval($edit_item->verkaufspreis_einmalig);
$modus = get_option('produkt_betriebsmodus', 'miete');
$mietpreis_monatlich = number_format((float)$edit_item->mietpreis_monatlich, 2, '.', '');
$verkaufspreis_formatted = number_format((float)$verkaufspreis_einmalig, 2, '.', '');
$weekend_price = floatval($edit_item->weekend_price);
$weekend_price_formatted = number_format((float)$weekend_price, 2, '.', '');
$sale_enabled = intval($edit_item->sale_enabled ?? 0);

global $wpdb;
$sale_conditions = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, COALESCE(vo.sale_available, 0) AS sale_available FROM {$wpdb->prefix}produkt_conditions c " .
    "LEFT JOIN {$wpdb->prefix}produkt_variant_options vo ON vo.variant_id = %d AND vo.option_type = 'condition' AND vo.option_id = c.id " .
    "WHERE c.category_id = %d ORDER BY c.sort_order, c.name",
    $edit_item->id,
    $selected_category
));
$sale_product_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, COALESCE(vo.sale_available, 0) AS sale_available FROM {$wpdb->prefix}produkt_colors c " .
    "LEFT JOIN {$wpdb->prefix}produkt_variant_options vo ON vo.variant_id = %d AND vo.option_type = 'product_color' AND vo.option_id = c.id " .
    "WHERE c.category_id = %d AND c.color_type = 'product' ORDER BY c.sort_order, c.name",
    $edit_item->id,
    $selected_category
));
$sale_frame_colors = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, COALESCE(vo.sale_available, 0) AS sale_available FROM {$wpdb->prefix}produkt_colors c " .
    "LEFT JOIN {$wpdb->prefix}produkt_variant_options vo ON vo.variant_id = %d AND vo.option_type = 'frame_color' AND vo.option_id = c.id " .
    "WHERE c.category_id = %d AND c.color_type = 'frame' ORDER BY c.sort_order, c.name",
    $edit_item->id,
    $selected_category
));
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
           onclick="return confirm('Bist du sicher das du Löschen möchtest?')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.7 90">
                <path fill-rule="evenodd" d="m39.8 24.3h-29.9l4.5 57.2v0.2c0 1.1 0.4 1.9 1 2.5 0.7 0.7 1.8 1.2 3.2 1.4h21.2s21.2 0 21.2 0c1.5-0.3 2.5-0.7 3.2-1.4 0.6-0.6 1-1.5 1-2.7v-0.1l4.5-57.2h-30zm-17.8 14.1c0-1.1 0.7-2 1.8-2.1 1.1 0 2 0.7 2.1 1.8l2.7 33.6c0 1.1-0.7 2-1.8 2.1-1.1 0-2-0.7-2.1-1.8l-2.7-33.6zm31.8-0.3c0-1.1 1-1.9 2.1-1.8 1.1 0 1.9 1 1.8 2.1l-2.7 33.6c0 1.1-1 1.9-2.1 1.8-1.1 0-1.9-1-1.8-2.1l2.7-33.6zm-15.9 0.1c0-1.1 0.9-1.9 1.9-1.9s1.9 0.9 1.9 1.9v33.6c0 1.1-0.9 1.9-1.9 1.9s-1.9-0.9-1.9-1.9v-33.6zm22.7-23.6h-53.1c-0.9 0-1.8 0.3-2.3 0.9-0.6 0.5-0.9 1.3-0.9 2v2.9h35.6s35.6 0 35.6 0v-2.9c0-0.8-0.4-1.5-0.9-2-0.6-0.6-1.4-0.9-2.3-0.9h-11.5zm-53.1-3.9h19.4v-0.8c0-2.4 0.5-4.6 1.3-6.2 1-2 2.5-3.3 4.4-3.3h14.6c1.8 0 3.4 1.3 4.4 3.3 0.8 1.6 1.3 3.8 1.3 6.2v0.8h19.4c1.9 0 3.7 0.8 5 2s2.1 2.9 2.1 4.8v4.9c0 1.1-0.9 1.9-1.9 1.9h-3.7l-4.6 57.5c0 2.1-0.8 3.9-2.1 5.2s-3 2.1-5.3 2.5h-0.5-21.3s-21.3 0-21.3 0h-0.3c-2.4-0.4-4.2-1.2-5.5-2.5s-2-3-2.1-5.2l-4.6-57.5h-3.7c-1.1 0-1.9-0.9-1.9-1.9v-4.9c0-1.9 0.8-3.6 2.1-4.8s3.1-2 5-2zm23.3 0h18.2v-0.8c0-1.8-0.3-3.4-0.9-4.5-0.3-0.7-0.7-1.1-0.9-1.1h-14.6c-0.2 0-0.6 0.4-0.9 1.1-0.6 1.1-0.9 2.7-0.9 4.5v0.8z"/>
            </svg>
        </a>

        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name und Beschreibung</p>
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
                </div>
                <div class="produkt-form-group full-width">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3"><?php echo esc_textarea($edit_item->description); ?></textarea>
                </div>
            </div>

            <?php if ($modus !== 'kauf'): ?>
            <div class="dashboard-card produkt-sale-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Produkt-Verkauf</h2>
                        <p class="card-subline">Einmaliger Verkauf</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="sale_enabled" value="1" class="sale-toggle" <?php checked($sale_enabled, 1); ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Verkauf aktiv</span>
                    </label>
                </div>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Einmaliger Verkaufspreis</label>
                        <input type="number" step="0.01" name="verkaufspreis_einmalig" value="<?php echo esc_attr($verkaufspreis_formatted); ?>" class="sale-dependent">
                    </div>
                </div>
                <?php if (!empty($sale_conditions)): ?>
                <div class="produkt-form-group full-width">
                    <label>Zustände für Verkauf</label>
                    <div class="sale-option-grid">
                        <?php foreach ($sale_conditions as $condition): ?>
                        <label class="produkt-toggle-label sale-option-toggle">
                            <input type="checkbox" name="sale_conditions[]" value="<?php echo esc_attr($condition->id); ?>" class="sale-dependent" <?php checked(intval($condition->sale_available ?? 0), 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span><?php echo esc_html($condition->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($sale_product_colors)): ?>
                <div class="produkt-form-group full-width">
                    <label>Produktfarben für Verkauf</label>
                    <div class="sale-option-grid">
                        <?php foreach ($sale_product_colors as $color): ?>
                        <label class="produkt-toggle-label sale-option-toggle">
                            <input type="checkbox" name="sale_product_colors[]" value="<?php echo esc_attr($color->id); ?>" class="sale-dependent" <?php checked(intval($color->sale_available ?? 0), 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span><?php echo esc_html($color->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($sale_frame_colors)): ?>
                <div class="produkt-form-group full-width">
                    <label>Gestellfarben für Verkauf</label>
                    <div class="sale-option-grid">
                        <?php foreach ($sale_frame_colors as $color): ?>
                        <label class="produkt-toggle-label sale-option-toggle">
                            <input type="checkbox" name="sale_frame_colors[]" value="<?php echo esc_attr($color->id); ?>" class="sale-dependent" <?php checked(intval($color->sale_available ?? 0), 1); ?>>
                            <span class="produkt-toggle-slider"></span>
                            <span><?php echo esc_html($color->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($modus === 'kauf'): ?>
            <div class="dashboard-card">
                <h2>Preise</h2>
                <p class="card-subline">Tarife</p>
                <div class="price-cards">
                  <div class="price-card" data-field="verkaufspreis_einmalig">
                    <div class="price-card-head">
                      <div class="price-title">Standardpreis</div>
                      <span class="price-badge">EUR</span>
                    </div>
                    <div class="price-display">
                      <input type="text" class="price-input" placeholder="0,00" aria-label="Standardpreis in Euro" />
                      <span class="price-suffix">€</span>
                      <input type="hidden" name="verkaufspreis_einmalig" class="price-hidden" value="<?php echo intval(round($verkaufspreis_einmalig * 100)); ?>">
                    </div>
                    <div class="price-buttons">
                      <button type="button" class="price-btn" data-step="-5">−5</button>
                      <button type="button" class="price-btn" data-step="-1">−1</button>
                      <button type="button" class="price-btn" data-step="1">+1</button>
                      <button type="button" class="price-btn" data-step="5">+5</button>
                    </div>
                    <div class="price-chips">
                      <button type="button" class="price-chip" data-value="1990">19,90 €</button>
                      <button type="button" class="price-chip" data-value="2990">29,90 €</button>
                      <button type="button" class="price-chip" data-value="4990">49,90 €</button>
                      <button type="button" class="price-chip" data-value="6990">69,90 €</button>
                    </div>
                  </div>

                  <div class="price-card" data-field="weekend_price">
                    <div class="price-card-head">
                      <div class="price-title">Wochenendpreis <span class="price-sub">(Fr–So)</span></div>
                      <span class="price-badge">EUR</span>
                    </div>
                    <div class="price-display">
                      <input type="text" class="price-input" placeholder="0,00" aria-label="Wochenendpreis in Euro" />
                      <span class="price-suffix">€</span>
                      <input type="hidden" name="weekend_price" class="price-hidden" value="<?php echo intval(round($weekend_price * 100)); ?>">
                    </div>
                    <div class="price-buttons">
                      <button type="button" class="price-btn" data-step="-5">−5</button>
                      <button type="button" class="price-btn" data-step="-1">−1</button>
                      <button type="button" class="price-btn" data-step="1">+1</button>
                      <button type="button" class="price-btn" data-step="5">+5</button>
                    </div>
                    <div class="price-chips">
                      <button type="button" class="price-chip" data-value="1490">14,90 €</button>
                      <button type="button" class="price-chip" data-value="1990">19,90 €</button>
                      <button type="button" class="price-chip" data-value="2990">29,90 €</button>
                      <button type="button" class="price-chip" data-value="3990">39,90 €</button>
                    </div>
                  </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Verfügbarkeit</h2>
                        <p class="card-subline">Buchbarkeit</p>
                    </div>
                    <label class="produkt-toggle-label">
                        <input type="checkbox" name="available" value="1" <?php echo ($edit_item->available ?? 1) ? 'checked' : ''; ?>>
                        <span class="produkt-toggle-slider"></span>
                        <span>Verfügbar</span>
                    </label>
                </div>
                <div class="form-grid">
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
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
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

    const saleToggle = document.querySelector('.produkt-sale-card .sale-toggle');
    const saleCard = document.querySelector('.produkt-sale-card');
    const saleDependentFields = document.querySelectorAll('.produkt-sale-card .sale-dependent');

    function handleSaleToggle() {
        if (!saleCard || !saleToggle) return;
        const enabled = saleToggle.checked;
        saleCard.classList.toggle('sale-disabled', !enabled);
        saleDependentFields.forEach(function(field) {
            field.disabled = !enabled;
        });
    }

    if (saleToggle) {
        saleToggle.addEventListener('change', handleSaleToggle);
        handleSaleToggle();
    }
});
</script>

