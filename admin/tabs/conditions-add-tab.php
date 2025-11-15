<?php
// Conditions Add Tab Content
?>

<div class="produkt-add-condition">
    <div class="produkt-form-header">
        <h3>➕ Neuen Zustand hinzufügen</h3>
        <p>Erstellen Sie einen neuen Zustand für das Produkt "<?php echo $current_category ? esc_html($current_category->name) : 'Unbekannt'; ?>".</p>
    </div>

    <form method="post" action="" class="produkt-compact-form">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <input type="hidden" name="category_id" value="<?php echo $selected_category; ?>">

        <div class="produkt-form-section">
            <h4>📝 Grunddaten</h4>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="z.B. Neuware, Generalüberholt">
                </div>
                <div class="produkt-form-group">
                    <label>Preisanpassung (%)</label>
                    <input type="number" name="price_modifier" value="0" step="0.01" min="-100" max="100" placeholder="0">
                    <small>Negative Werte für Rabatte, positive für Aufpreise.</small>
                </div>
            </div>
            <div class="produkt-form-row">
                <div class="produkt-form-group full-width">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3" placeholder="Kurze Beschreibung (optional)"></textarea>
                </div>
            </div>
            <div class="produkt-form-row">
                <div class="produkt-form-group">
                    <label>Sortierung</label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
            </div>
        </div>

        <?php if (!empty($variants)): ?>
        <div class="produkt-form-section">
            <h4>🧩 Verfügbarkeit je Ausführung</h4>
            <p class="card-subline">Bestimmen Sie, für welche Varianten der Zustand buchbar ist.</p>
            <div class="variant-availability-grid">
                <?php foreach ($variants as $variant): ?>
                <div class="variant-availability-card">
                    <div class="availability-card-head">
                        <div class="availability-card-title"><?php echo esc_html($variant->name); ?></div>
                        <label class="produkt-toggle-label">
                            <input type="checkbox" name="variant_available[<?php echo $variant->id; ?>]" value="1" checked>
                            <span class="produkt-toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="produkt-form-actions">
            <button type="submit" name="submit" class="button button-primary button-large">✅ Zustand erstellen</button>
            <a href="<?php echo admin_url('admin.php?page=produkt-conditions&category=' . $selected_category . '&tab=list'); ?>" class="button button-large">❌ Abbrechen</a>
        </div>
    </form>
</div>
