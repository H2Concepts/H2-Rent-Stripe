<?php
$customer = produkt_get_current_customer_data();
if (!$customer) {
    echo '<p>Kunde nicht gefunden.</p>';
    return;
}

$modus    = get_option('produkt_betriebsmodus', 'miete');
$is_sale  = ($modus === 'kauf');

global $wpdb;
$orders = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT *, stripe_subscription_id AS subscription_id FROM {$wpdb->prefix}produkt_orders WHERE customer_email = %s ORDER BY created_at",
        $customer->email
    )
);

$sale_orders = [];
$order_map   = [];
foreach ($orders as $o) {
    if (!empty($o->subscription_id)) {
        $order_map[$o->subscription_id] = $o;
    }
    if ($o->mode === 'kauf') {
        $sale_orders[] = $o;
    }
}

$subscriptions = [];
$invoices      = [];
if ($customer->stripe_customer_id) {
    $subs = \ProduktVerleih\StripeService::get_active_subscriptions_for_customer($customer->stripe_customer_id);
    if (!is_wp_error($subs)) {
        $subscriptions = $subs;
    }
    $invoices = \ProduktVerleih\StripeService::get_customer_invoices($customer->stripe_customer_id);
}

$full_name = trim($customer->first_name . ' ' . $customer->last_name);
?>
<div class="produkt-account-wrapper produkt-container shop-overview-container">
    <h1>Kundenkonto</h1>
    <?php if (!empty($message)) { echo $message; } ?>
        <div class="account-layout">
            <aside class="account-sidebar shop-category-list">
                <h2>Hallo <?php echo esc_html($full_name); ?></h2>
                <ul>
                    <li>
                        <a href="#" class="active"><?php echo $is_sale ? 'Bestellungen' : 'Abos'; ?></a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(home_url('/kundenkonto?logout=1')); ?>">Logout</a>
                    </li>
                </ul>
            </aside>
            <div>
        <?php if ($is_sale) : ?>
            <?php if (!empty($sale_orders)) : ?>
                <?php foreach ($sale_orders as $order) : ?>
                    <?php
                        $variant_id = $order->variant_id ?? 0;
                        $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);
                    ?>
                    <?php include PRODUKT_PLUGIN_PATH . 'includes/render-order.php'; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Keine Bestellungen.</p>
            <?php endif; ?>
        <?php else : ?>
            <?php if (!empty($subscriptions)) : ?>
                <?php foreach ($subscriptions as $sub) : ?>
                    <?php
                    $order = $order_map[$sub['subscription_id']] ?? null;
                    if (!$order) {
                        continue;
                    }
                    $product_name = $order->produkt_name ?? $sub['subscription_id'];
                    $start_ts = strtotime($sub['start_date']);
                    $start_formatted = date_i18n('d.m.Y', $start_ts);
                    $laufzeit_in_monaten = pv_get_minimum_duration_months($order);
                    $cancelable_ts            = strtotime("+{$laufzeit_in_monaten} months", $start_ts);
                    $kuendigungsfenster_ts    = strtotime('-14 days', $cancelable_ts);
                    $kuendigbar_ab_date       = date_i18n('d.m.Y', $kuendigungsfenster_ts);
                    $cancelable               = time() >= $kuendigungsfenster_ts;
                    $is_extended              = time() > $cancelable_ts;

                    $period_end_ts   = null;
                    $period_end_date = '';
                    if (!empty($sub['current_period_end'])) {
                        $period_end_ts   = strtotime($sub['current_period_end']);
                        $period_end_date = date_i18n('d.m.Y', $period_end_ts);
                    }

                    $variant_id = $order->variant_id ?? 0;
                    $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);

                    $address = '';
                    if (!empty($order->customer_street) && !empty($order->customer_postal) && !empty($order->customer_city)) {
                        $address = esc_html(trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city));
                    }
                    ?>
                    <?php include PRODUKT_PLUGIN_PATH . 'includes/render-subscription.php'; ?>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Keine aktiven Abos.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$is_sale && !empty($invoices)) : ?>
            <div class="produkt-section">
                <h3>Rechnungen</h3>
                <table class="stripe-invoice-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Betrag</th>
                            <th>Status</th>
                            <th>PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($invoices as $invoice) : ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('d.m.Y', $invoice->created)); ?></td>
                            <td><?php echo esc_html(number_format($invoice->amount_due / 100, 2, ',', '.')); ?> €</td>
                            <td><?php echo esc_html(ucfirst($invoice->status)); ?></td>
                            <td><a href="<?php echo esc_url($invoice->invoice_pdf); ?>" target="_blank">Download</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
            </div>
        </div>
</div>
