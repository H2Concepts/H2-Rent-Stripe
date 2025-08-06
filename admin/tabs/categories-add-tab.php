<?php
// Categories Add Tab Content
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$filter_groups = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_filter_groups ORDER BY name");
$filters_by_group = [];
foreach ($filter_groups as $g) {
    $filters_by_group[$g->id] = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_filters WHERE group_id = %d ORDER BY name",
            $g->id
        )
    );
}
?>

<div class="produkt-add-category">
    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <button type="submit" name="submit" class="icon-btn categories-save-btn" aria-label="Speichern">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3"><path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/><path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/></svg>
        </button>

        <div class="produkt-form-sections">
            <div class="dashboard-card">
                <h2>Grunddaten</h2>
                <p class="card-subline">Name und Shortcode</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label>Produkt-Name *</label>
                        <input type="text" name="name" required placeholder="z.B. Nonomo Produkt">
                    </div>
                    <div class="produkt-form-group">
                        <label>Shortcode-Bezeichnung *</label>
                        <input type="text" name="shortcode" required pattern="[a-z0-9_-]+" placeholder="z.B. nonomo-premium">
                        <small>Nur Kleinbuchstaben, Zahlen, _ und -</small>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Produktbild</h2>
                <p class="card-subline">Vorschau</p>
                <div class="form-grid">
                    <div class="produkt-form-group full-width">
                        <label>Standard-Produktbild</label>
                        <div class="image-field-row">
                            <div id="default_image_preview" class="image-preview">
                                <span>Noch kein Bild vorhanden</span>
                            </div>
                            <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="default_image" aria-label="Bild auswählen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                            </button>
                            <button type="button" class="icon-btn produkt-remove-image" data-target="default_image" aria-label="Bild entfernen">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8Z"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="default_image" id="default_image" value="">
                        <small>Fallback-Bild wenn kein spezifisches Bild vorhanden ist</small>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Produktbewertungen</h2>
                <p class="card-subline">Anzeige</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="show_reviews" value="1">
                            <span class="produkt-toggle-slider"></span>
                            <span>Produktbewertungen anzeigen</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Features-Sektion</h2>
                <p class="card-subline">Anzeige</p>
                <div class="form-grid">
                    <div class="produkt-form-group">
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="show_features" value="1" checked>
                            <span class="produkt-toggle-slider"></span>
                            <span>Features-Sektion anzeigen</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="feature-cards-grid">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="dashboard-card">
                    <h2>Feature <?php echo $i; ?></h2>
                    <p class="card-subline">Bild &amp; Text</p>
                    <div class="form-grid">
                        <div class="produkt-form-group full-width">
                            <label>Bild</label>
                            <div class="image-field-row">
                                <div id="feature_<?php echo $i; ?>_icon_preview" class="image-preview"><span>Noch kein Bild vorhanden</span></div>
                                <button type="button" class="icon-btn icon-btn-media produkt-media-button" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild auswählen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 26.2"><path d="M16,7c-3.9,0-7,3.1-7,7s3.1,7,7,7,7-3.1,7-7-3.1-7-7-7ZM16,19c-2.8,0-5-2.2-5-5s2.2-5,5-5,5,2.2,5,5-2.2,5-5,5ZM29,4h-4c-1,0-3-4-4-4h-10c-1.1,0-3.1,4-4,4H3c-1.7,0-3,1.3-3,3v16c0,1.7,1.3,3,3,3h26c1.7,0,3-1.3,3-3V7c0-1.7-1.3-3-3-3ZM30,22c0,1.1-.9,2-2,2H4c-1.1,0-2-.9-2-2v-14c0-1.1.9-2,2-2h4c.9,0,2.9-4,4-4h8c1,0,3,4,3.9,4h4.1c1.1,0,2,.9,2,2v14Z"/></svg>
                                </button>
                                <button type="button" class="icon-btn produkt-remove-image" data-target="feature_<?php echo $i; ?>_icon" aria-label="Bild entfernen">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1"><path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/><path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8Z"/></svg>
                                </button>
                            </div>
                            <input type="hidden" name="feature_<?php echo $i; ?>_icon" id="feature_<?php echo $i; ?>_icon" value="">
                        </div>
                        <div class="produkt-form-group full-width">
                            <label>Beschreibung</label>
                            <textarea name="feature_<?php echo $i; ?>_description" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="dashboard-card">
                <div class="card-header-flex">
                    <div>
                        <h2>Filter</h2>
                        <p class="card-subline">Dem Produkt zuordnen</p>
                    </div>
                    <form class="produkt-filter-form product-search-bar" onsubmit="return false;">
                        <div class="search-input-wrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon"><path d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg>
                            <input type="text" id="filter-search" placeholder="Filter suchen">
                        </div>
                    </form>
                </div>
                <div id="filter-list" class="produkt-form-group">
                    <?php foreach ($filter_groups as $g): ?>
                        <h4><?php echo esc_html($g->name); ?></h4>
                        <?php foreach ($filters_by_group[$g->id] as $f): ?>
                        <label class="produkt-filter-item">
                            <input type="checkbox" name="filters[]" value="<?php echo $f->id; ?>">
                            <span><?php echo esc_html($f->name); ?></span>
                        </label>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

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
    const filterSearch = document.getElementById('filter-search');
    if (filterSearch) {
        filterSearch.addEventListener('input', function(){
            const term = this.value.toLowerCase();
            document.querySelectorAll('#filter-list .produkt-filter-item').forEach(function(el){
                el.style.display = el.textContent.toLowerCase().includes(term) ? 'block' : 'none';
            });
        });
    }
});
</script>
