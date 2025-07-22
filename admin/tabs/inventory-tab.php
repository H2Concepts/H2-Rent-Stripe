<?php
// Inventory management Tab
$table_name = $wpdb->prefix . 'produkt_variants';
$sku_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'sku'");
if (empty($sku_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN sku VARCHAR(255) DEFAULT '' AFTER delivery_time");
}
$avail_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stock_available'");
if (empty($avail_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stock_available INT DEFAULT 0 AFTER sku");
}
$rented_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'stock_rented'");
if (empty($rented_exists)) {
    $wpdb->query("ALTER TABLE $table_name ADD COLUMN stock_rented INT DEFAULT 0 AFTER stock_available");
}

if (isset($_POST['update_inventory'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    foreach ((array)($_POST['variants'] ?? []) as $id => $data) {
        $sku = sanitize_text_field($data['sku'] ?? '');
        $available = intval($data['stock_available'] ?? 0);
        $rented = intval($data['stock_rented'] ?? 0);
        $wpdb->update(
            $table_name,
            ['sku' => $sku, 'stock_available' => $available, 'stock_rented' => $rented],
            ['id' => intval($id)],
            ['%s','%d','%d'],
            ['%d']
        );
    }
    echo '<div class="notice notice-success"><p>✅ Lager aktualisiert</p></div>';
}

$variants = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE category_id = %d ORDER BY sort_order, name",
    $selected_category
));
$modus = get_option('produkt_betriebsmodus', 'miete');
?>
<div class="produkt-tab-section">
    <h3>🏪 Lagerverwaltung</h3>
    <?php if (empty($variants)) : ?>
        <p><strong>❗Bitte erstellen Sie zuerst mindestens eine Ausführung, um Lagerbestände zu verwalten.</strong></p>
    <?php else: ?>
    <form method="post">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Ausführung</th>
                    <th>Preis</th>
                    <th>Menge</th>
                    <th>SKU</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($variants as $v): ?>
                <?php $price = ($modus === 'kauf') ? $v->verkaufspreis_einmalig : $v->mietpreis_monatlich; ?>
                <tr>
                    <td><?php echo esc_html($v->name); ?></td>
                    <td><?php echo number_format((float)$price, 2, ',', '.'); ?> €</td>
                    <td>
                        <button type="button" class="button edit-qty">Bearbeiten</button>
                        <div class="qty-popup" style="display:none;margin-top:4px;">
                            <div class="spinner-group">
                                <label>Verfügbar</label>
                                <div class="spinner-field">
                                    <button type="button" class="button minus">-</button>
                                    <input type="number" name="variants[<?php echo $v->id; ?>][stock_available]" value="<?php echo esc_attr($v->stock_available); ?>" class="small-text">
                                    <button type="button" class="button plus">+</button>
                                </div>
                            </div>
                            <div class="spinner-group">
                                <label>In Vermietung</label>
                                <div class="spinner-field">
                                    <button type="button" class="button minus">-</button>
                                    <input type="number" name="variants[<?php echo $v->id; ?>][stock_rented]" value="<?php echo esc_attr($v->stock_rented); ?>" class="small-text">
                                    <button type="button" class="button plus">+</button>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><input type="text" name="variants[<?php echo $v->id; ?>][sku]" value="<?php echo esc_attr($v->sku); ?>"></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><button type="submit" name="update_inventory" class="button button-primary">Lager speichern</button></p>
    </form>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.edit-qty').forEach(function(btn){
    btn.addEventListener('click',function(e){e.preventDefault();const p=this.nextElementSibling;p.style.display=p.style.display==='block'?'none':'block';});
  });
  document.querySelectorAll('.spinner-field').forEach(function(f){
    const input=f.querySelector('input');
    f.querySelector('.minus').addEventListener('click',function(e){e.preventDefault();input.stepDown();});
    f.querySelector('.plus').addEventListener('click',function(e){e.preventDefault();input.stepUp();});
  });
});
</script>
