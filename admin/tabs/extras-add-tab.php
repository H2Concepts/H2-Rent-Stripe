<?php
// Extras Add Tab Content
$modus = get_option('produkt_betriebsmodus', 'miete');
?>

<div class="produkt-add-extra">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">
        <button type="submit" name="submit" class="icon-btn extras-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
            </svg>
        </button>

        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Name *</label>
                        <input type="text" name="name" required placeholder="z.B. Himmel, Zubehör-Set">
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Preis</h2>
                <p class="card-subline">Tarif</p>
                <div class="price-cards">
                  <div class="price-card" data-field="<?php echo $modus === 'kauf' ? 'sale_price' : 'price'; ?>">
                    <div class="price-card-head">
                      <div class="price-title">Standardpreis</div>
                      <span class="price-badge">EUR</span>
                    </div>
                    <div class="price-display">
                      <input type="text" class="price-input" placeholder="0,00" aria-label="Preis in Euro" />
                      <span class="price-suffix">€</span>
                      <input type="hidden" name="<?php echo $modus === 'kauf' ? 'sale_price' : 'price'; ?>" class="price-hidden" value="">
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
                                <span>Noch kein Bild vorhanden</span>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="image_url" aria-label="Bild auswählen">
                                <svg id="Ebene_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.3 82.6"><path d="M74.5.6H7.8C3.8.6.6,3.9.5,7.9v66.7c0,4,3.3,7.3,7.3,7.3h66.7c4,0,7.3-3.3,7.3-7.3V7.9c0-4-3.3-7.3-7.3-7.3ZM7.8,6.8h66.7c.3,0,.5.1.7.3.2.2.3.5.3.7v43.5l-13.2-10.6c-2.6-2-6.3-2-8.9,0l-11.9,8.8-11.8-11.8c-2.9-2.8-7.4-2.8-10.3,0l-12.5,12.5V7.9c0-.6.4-1,1-1h0ZM74.5,75.6H7.8c-.6,0-1-.5-1-1v-15.4l17-17c.2-.2.5-.3.8-.3s.6.1.8.3l17.9,17.9c1.2,1.2,3.2,1.2,4.4,0s1.2-3.2,0-4.4l-1.6-1.6,11.2-8.3c.4-.3.9-.3,1.3,0l17.1,13.7v15.1c0,.6-.5,1-1,1h0ZM45.3,36c4.6,0,8.8-2.8,10.6-7.1,1.8-4.3.8-9.2-2.5-12.5-3.3-3.3-8.2-4.3-12.5-2.5-4.3,1.8-7.1,6-7.1,10.6s5.1,11.5,11.5,11.5h0ZM45.3,19.3c2.1,0,4,1.3,4.8,3.2.8,1.9.4,4.2-1.1,5.7-1.5,1.5-3.7,1.9-5.7,1.1-1.9-.8-3.2-2.7-3.2-4.8s2.3-5.2,5.2-5.2Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="image_url" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="image_url" id="image_url" value="">
                        <small>Wird als Overlay über dem Hauptbild angezeigt (empfohlen: 400x400 Pixel)</small>
                    </div>
                </div>
            </div>

            <?php if (!empty($variants)): ?>
            <div class="dashboard-card">
                <h2>Verfügbarkeit je Ausführung</h2>
                <p class="card-subline">Extras pro Variante aktivieren</p>
                <div class="variant-availability-grid">
                    <?php foreach ($variants as $v): ?>
                    <div class="variant-availability-card">
                        <div class="availability-card-head">
                            <div class="availability-card-title"><?php echo esc_html($v->name); ?></div>
                            <label class="produkt-toggle-label">
                                <input type="checkbox" name="variant_available[<?php echo $v->id; ?>]" value="1" checked>
                                <span class="produkt-toggle-slider"></span>
                            </label>
                        </div>
                        <?php if (!empty($v->image_url_1)): ?>
                        <div class="availability-image"><img src="<?php echo esc_url($v->image_url_1); ?>" alt=""></div>
                        <?php endif; ?>
                        <?php if (!empty($v->description)): ?>
                        <div class="availability-desc"><?php echo esc_html($v->description); ?></div>
                        <?php endif; ?>
                        <div class="availability-prices">
                            <?php $std_price = ($modus === 'kauf') ? $v->verkaufspreis_einmalig : $v->mietpreis_monatlich; ?>
                            <?php if ($std_price > 0): ?>
                            <div>Standardpreis: <?php echo number_format($std_price, 2, ',', '.'); ?>€</div>
                            <?php endif; ?>
                            <?php if ($modus === 'kauf' && !empty($v->weekend_price)): ?>
                            <div>Wochenendpreis: <?php echo number_format($v->weekend_price, 2, ',', '.'); ?>€</div>
                            <?php endif; ?>
                        </div>
                    </div>
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
                        <input type="number" name="sort_order" value="0" min="0">
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
