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

    if ($id > 0) {
        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        if ($current) {
            $wpdb->update($table, [
                'name'            => $name,
                'description'     => $description,
                'price'           => $price,
                'service_provider'=> $provider,
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
<div id="shipping-modal" class="modal-overlay" data-open="<?php echo $edit_shipping ? '1' : '0'; ?>">
    <div class="modal-content">
        <button type="button" class="modal-close">&times;</button>
        <h2><?php echo $edit_shipping ? 'Versandart bearbeiten' : 'Neue Versandart hinzufügen'; ?></h2>
        <form method="post" class="produkt-compact-form">
            <?php wp_nonce_field('save_shipping_action', 'save_shipping_nonce'); ?>
            <input type="hidden" name="shipping_id" value="<?php echo esc_attr($edit_shipping->id ?? ''); ?>">
            <div class="form-grid">
                <div class="form-field">
                    <label for="shipping_name">Name</label>
                    <input type="text" id="shipping_name" name="shipping_name" required value="<?php echo esc_attr($edit_shipping->name ?? ''); ?>">
                </div>
                <div class="form-field">
                    <label for="shipping_price">Versandkosten (€)</label>
                    <input type="number" id="shipping_price" name="shipping_price" step="0.01" required value="<?php echo esc_attr($edit_shipping->price ?? ''); ?>">
                </div>
                <div class="form-field">
                    <label for="shipping_provider">Dienstleister</label>
                    <select id="shipping_provider" name="shipping_provider" required>
                        <?php foreach ($providers as $val => $label): ?>
                            <option value="<?php echo esc_attr($val); ?>" <?php selected($edit_shipping && $edit_shipping->service_provider === $val); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field full">
                    <label for="shipping_description">Beschreibung</label>
                    <textarea id="shipping_description" name="shipping_description"><?php echo esc_textarea($edit_shipping->description ?? ''); ?></textarea>
                </div>
            </div>
            <p>
                <button type="submit" name="save_shipping" class="icon-btn" aria-label="Speichern">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.3 80.3">
                        <path d="M32,53.4c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l20.8-20.8c1.7-1.7,1.7-4.2,0-5.8-1.7-1.7-4.2-1.7-5.8,0l-17.9,17.9-7.7-7.7c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l10.6,10.6Z"/>
                        <path d="M40.2,79.6c21.9,0,39.6-17.7,39.6-39.6S62,.5,40.2.5.6,18.2.6,40.1s17.7,39.6,39.6,39.6ZM40.2,8.8c17.1,0,31.2,14,31.2,31.2s-14,31.2-31.2,31.2-31.2-14.2-31.2-31.2,14.2-31.2,31.2-31.2Z"/>
                    </svg>
                </button>
            </p>
        </form>
    </div>
</div>

<div class="produkt-admin dashboard-wrapper">
    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋</h1>
    <p class="dashboard-subline">Versandarten verwalten</p>

    <div class="h2-rental-card card-shipping-list">
        <div class="card-header-flex">
            <div>
                <h2>Versandarten</h2>
                <p class="card-subline">Lieferoptionen für Bestellungen</p>
            </div>
            <button id="add-shipping-btn" type="button" class="icon-btn add-category-btn" aria-label="Hinzufügen">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80.3">
                    <path d="M12.1,12c-15.4,15.4-15.4,40.4,0,55.8,7.7,7.7,17.7,11.7,27.9,11.7s20.2-3.8,27.9-11.5c15.4-15.4,15.4-40.4,0-55.8-15.4-15.6-40.4-15.6-55.8-.2h0ZM62.1,62c-12.1,12.1-31.9,12.1-44.2,0-12.1-12.1-12.1-31.9,0-44.2,12.1-12.1,31.9-12.1,44.2,0,12.1,12.3,12.1,31.9,0,44.2Z"/>
                    <path d="M54.6,35.7h-10.4v-10.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v10.4h-10.4c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2h10.4v10.4c0,2.3,1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2v-10.4h10.4c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2Z"/>
                </svg>
            </button>
        </div>
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Preis</th>
                    <th>Dienstleister</th>
                    <th>Standard</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo esc_html($r->name); ?></td>
                        <td><?php echo number_format($r->price, 2, ',', '.'); ?> €</td>
                        <td><?php echo esc_html($providers[$r->service_provider] ?? ucfirst($r->service_provider)); ?></td>
                        <td>
                            <input type="checkbox" class="default-shipping-checkbox" data-id="<?php echo $r->id; ?>" <?php checked($r->is_default); ?>>
                        </td>
                        <td>
                            <button type="button" class="icon-btn" aria-label="Bearbeiten" onclick="window.location.href='?page=produkt-shipping&edit=<?php echo $r->id; ?>'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80.8 80.1">
                                    <path d="M54.7,4.8l-31.5,31.7c-.6.6-1,1.5-1.2,2.3l-3.3,18.3c-.2,1.2.2,2.7,1.2,3.8.8.8,1.9,1.2,2.9,1.2h.8l18.3-3.3c.8-.2,1.7-.6,2.3-1.2l31.7-31.7c5.8-5.8,5.8-15.2,0-21-6-5.8-15.4-5.8-21.2,0h0ZM69.9,19.8l-30.8,30.8-11,1.9,2.1-11.2,30.6-30.6c2.5-2.5,6.7-2.5,9.2,0,2.5,2.7,2.5,6.7,0,9.2Z"/>
                                    <path d="M5.1,79.6h70.8c2.3,0,4.2-1.9,4.2-4.2v-35.4c0-2.3-1.9-4.2-4.2-4.2s-4.2,1.9-4.2,4.2v31.2H9.2V8.8h31.2c2.3,0,4.2-1.9,4.2-4.2s-1.9-4.2-4.2-4.2H5.1c-2.3,0-4.2,1.9-4.2,4.2v70.8c0,2.3,1.9,4.2,4.2,4.2h0Z"/>
                                </svg>
                            </button>
                            <button type="button" class="icon-btn" aria-label="Löschen" onclick="if(confirm('Wirklich löschen?')){window.location.href='?page=produkt-shipping&delete=<?php echo $r->id; ?>&fw_nonce=<?php echo wp_create_nonce('produkt_admin_action'); ?>';}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                    <path d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z"/>
                                    <path d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
