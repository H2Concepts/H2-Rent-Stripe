<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$table = $wpdb->prefix . 'produkt_shipping_methods';

if (!function_exists('produkt_create_stripe_shipping_item')) {
    function produkt_create_stripe_shipping_item($name, $price) {
        $res = \ProduktVerleih\StripeService::init();
        if (is_wp_error($res)) { return ['product_id' => '', 'price_id' => '']; }
        try {
            $product = \Stripe\Product::create([
                'name' => 'Versand: ' . $name,
                'metadata' => ['type' => 'shipping']
            ]);
            $price_obj = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => intval(round($price * 100)),
                'currency' => 'eur',
            ]);
            return [ 'product_id' => $product->id, 'price_id' => $price_obj->id ];
        } catch (\Exception $e) {
            return ['product_id' => '', 'price_id' => ''];
        }
    }
}

if (isset($_POST['save_shipping'])) {
    \ProduktVerleih\Admin::verify_admin_action();
    $name = sanitize_text_field($_POST['shipping_name']);
    $description = sanitize_textarea_field($_POST['shipping_description']);
    $price = floatval($_POST['shipping_price']);
    $provider = sanitize_text_field($_POST['shipping_provider']);
    $stripe = produkt_create_stripe_shipping_item($name, $price);

    $wpdb->insert($table, [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'service_provider' => $provider,
        'stripe_product_id' => $stripe['product_id'],
        'stripe_price_id' => $stripe['price_id']
    ]);
}

$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$providers = ['dhl' => 'DHL', 'hermes' => 'Hermes', 'ups' => 'UPS', 'dpd' => 'DPD'];
?>
<div class="wrap">
    <h1>Versandarten verwalten</h1>
    <form method="post">
        <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label>Name</label></th>
                <td><input type="text" name="shipping_name" class="regular-text" required></td>
            </tr>
            <tr>
                <th><label>Beschreibung</label></th>
                <td><textarea name="shipping_description" class="large-text"></textarea></td>
            </tr>
            <tr>
                <th><label>Versandkosten (€)</label></th>
                <td><input type="number" name="shipping_price" step="0.01" required class="small-text"></td>
            </tr>
            <tr>
                <th><label>Dienstleister</label></th>
                <td>
                    <?php foreach ($providers as $val => $label): ?>
                        <label style="margin-right:12px;">
                            <input type="radio" name="shipping_provider" value="<?= esc_attr($val) ?>" required>
                            <span class="icon-<?= esc_attr($val) ?>"><?= esc_html($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <p><input type="submit" name="save_shipping" class="button-primary" value="Versandart speichern"></p>
    </form>

    <?php if ($rows): ?>
        <h2>Bestehende Versandarten</h2>
        <table class="widefat striped">
            <thead><tr><th>Name</th><th>Preis</th><th>Dienstleister</th><th>Stripe-ID</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc_html($r->name) ?></td>
                        <td><?= number_format($r->price, 2, ',', '.') ?> €</td>
                        <td><span class="icon-<?= esc_attr($r->service_provider) ?>"><?= esc_html(ucfirst($r->service_provider)) ?></span></td>
                        <td><?= esc_html($r->stripe_price_id) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
