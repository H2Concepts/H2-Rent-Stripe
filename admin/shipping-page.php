<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$table = $wpdb->prefix . 'produkt_shipping_methods';

require_once plugin_dir_path(__FILE__) . '/../includes/stripe-sync.php';

// Get current record if editing
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit_shipping = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id)) : null;

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

if (!function_exists('produkt_create_stripe_price_only')) {
    function produkt_create_stripe_price_only($product_id, $price) {
        $res = \ProduktVerleih\StripeService::init();
        if (is_wp_error($res)) { return ''; }
        try {
            $price_obj = \Stripe\Price::create([
                'product'     => $product_id,
                'unit_amount' => intval(round($price * 100)),
                'currency'    => 'eur',
            ]);
            return $price_obj->id;
        } catch (\Exception $e) {
            return '';
        }
    }
}

if (isset($_POST['save_shipping'])) {
    if (
        !isset($_POST['save_shipping_nonce']) ||
        !wp_verify_nonce($_POST['save_shipping_nonce'], 'save_shipping_action')
    ) {
        wp_die('Ungültige Anfrage – bitte Seite neu laden und erneut versuchen.');
    }
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'h2-concepts'));
    }

    $name        = sanitize_text_field($_POST['shipping_name']);
    $description = sanitize_textarea_field($_POST['shipping_description']);
    $price       = floatval($_POST['shipping_price']);
    $provider    = sanitize_text_field($_POST['shipping_provider']);
    $id          = intval($_POST['shipping_id'] ?? 0);
    $is_default  = isset($_POST['is_default']) ? 1 : 0;

    if ($is_default) {
        $wpdb->query("UPDATE $table SET is_default = 0");
    }

    if ($id > 0) {
        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($current) {
            $wpdb->update($table, [
                'name'            => $name,
                'description'     => $description,
                'price'           => $price,
                'service_provider'=> $provider,
                'is_default'      => $is_default,
            ], ['id' => $id]);

            $price_id = produkt_create_stripe_price_only($current->stripe_product_id, $price);
            if ($price_id) {
                $wpdb->update($table, ['stripe_price_id' => $price_id], ['id' => $id]);
            }
        }
    } else {
        $stripe = produkt_create_stripe_shipping_item($name, $price);
        $wpdb->insert($table, [
            'name'             => $name,
            'description'      => $description,
            'price'            => $price,
            'service_provider' => $provider,
            'stripe_product_id'=> $stripe['product_id'],
            'stripe_price_id'  => $stripe['price_id'],
            'is_default'       => $is_default,
        ]);
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['fw_nonce']) &&
    wp_verify_nonce($_GET['fw_nonce'], 'produkt_admin_action')) {
    if (current_user_can('manage_options')) {
        $del_id = intval($_GET['delete']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT stripe_product_id FROM $table WHERE id = %d", $del_id));
        if ($row && $row->stripe_product_id) {
            produkt_delete_or_archive_stripe_product($row->stripe_product_id);
        }
        $result = $wpdb->delete($table, ['id' => $del_id], ['%d']);
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>✅ Versandart gelöscht!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Fehler beim Löschen: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }
}

$rows      = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$providers = [
    'none'   => 'Ohne',
    'pickup' => 'Abholung',
    'dhl'    => 'DHL',
    'hermes' => 'Hermes',
    'ups'    => 'UPS',
    'dpd'    => 'DPD'
];
?>
<div class="wrap" id="produkt-admin-shipping">
    <div class="produkt-admin-card">
        <div class="produkt-admin-header-compact">
            <div class="produkt-admin-logo-compact">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="produkt-admin-title-compact">
                <h1>Versandarten verwalten</h1>
                <p>Lieferoptionen für Bestellungen</p>
            </div>
        </div>

        <form method="post" class="produkt-compact-form">
            <?php wp_nonce_field('save_shipping_action', 'save_shipping_nonce'); ?>
            <table class="form-table">
            <tr>
                <th><label>Name</label></th>
                <td><input type="text" name="shipping_name" class="regular-text" value="<?= esc_attr($edit_shipping->name ?? '') ?>" required></td>
            </tr>
            <tr>
                <th><label>Beschreibung</label></th>
                <td><textarea name="shipping_description" class="large-text"><?= esc_textarea($edit_shipping->description ?? '') ?></textarea></td>
            </tr>
            <tr>
                <th><label>Versandkosten (€)</label></th>
                <td><input type="number" name="shipping_price" step="0.01" value="<?= esc_attr($edit_shipping->price ?? '') ?>" required class="small-text"></td>
            </tr>
            <tr>
                <th><label>Dienstleister</label></th>
                <td>
                    <?php foreach ($providers as $val => $label): ?>
                        <label style="margin-right:12px;">
                            <input type="radio" name="shipping_provider" value="<?= esc_attr($val) ?>" <?php checked($edit_shipping && $edit_shipping->service_provider === $val); ?> required>
                            <span class="icon-<?= esc_attr($val) ?>"><?= esc_html($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th><label>Standardversand?</label></th>
                <td><input type="checkbox" name="is_default" value="1" <?= ($edit_shipping && $edit_shipping->is_default) ? 'checked' : '' ?>></td>
            </tr>
        </table>
        <input type="hidden" name="shipping_id" value="<?= esc_attr($edit_shipping->id ?? 0) ?>">
        <p><input type="submit" name="save_shipping" class="button button-primary" value="Versandart speichern"></p>
        </form>

        <?php if ($rows): ?>
        <h2>Bestehende Versandarten</h2>
        <table class="widefat striped">
            <thead>
                <tr><th>Name</th><th>Preis</th><th>Dienstleister</th><th>Stripe-ID</th><th>Aktion</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= esc_html($r->name) ?><?= $r->is_default ? ' (Standard)' : '' ?></td>
                        <td><?= number_format($r->price, 2, ',', '.') ?> €</td>
                        <td><span class="icon-<?= esc_attr($r->service_provider) ?>"><?= esc_html($providers[$r->service_provider] ?? ucfirst($r->service_provider)) ?></span></td>
                        <td><?= esc_html($r->stripe_price_id) ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=produkt-shipping&edit=' . $r->id); ?>">Bearbeiten</a>
                            |
                            <a href="<?php echo admin_url('admin.php?page=produkt-shipping&delete=' . $r->id . '&fw_nonce=' . wp_create_nonce('produkt_admin_action')); ?>" onclick="return confirm('Sind Sie sicher?');">Löschen</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
