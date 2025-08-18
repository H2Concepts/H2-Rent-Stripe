<?php
if (!defined('ABSPATH')) {
    exit;
}
// The account page uses its own order-detail partial for customer-facing views.
$modus = get_option('produkt_betriebsmodus', 'miete');
?>
<div class="produkt-account-wrapper produkt-container shop-overview-container">
    <h1>Kundenkonto</h1>
    <?php if (!empty($message)) { echo $message; } ?>
        <div class="account-layout">
            <aside class="account-sidebar shop-category-list">
                <h2>Hallo <?php echo esc_html($full_name); ?></h2>
                <ul>
                    <?php if ($modus === 'miete') : ?>
                        <li><a href="#" class="active" data-section="subscriptions">Abos</a></li>
                    <?php else : ?>
                        <li><a href="#" class="active" data-section="rentals">Miete</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">Logout</a></li>
                </ul>
            </aside>
            <div class="account-content">
                <?php if ($modus === 'miete') : ?>
                    <div id="dashboard-subscriptions" class="dashboard-section active">
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
                    </div>
                <?php else : ?>
                    <div id="dashboard-rentals" class="dashboard-section active">
                <?php if (!empty($rental_orders)) : ?>
                    <?php $first = $rental_orders[0]; ?>
                    <div class="abo-row">
                        <div class="order-box">
                            <h3>Kundendaten</h3>
                            <?php if (!empty($first->customer_name)) : ?>
                                <p><strong>Name:</strong> <?php echo esc_html($first->customer_name); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($first->customer_email)) : ?>
                                <p><strong>E-Mail:</strong> <?php echo esc_html($first->customer_email); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($first->customer_phone)) : ?>
                                <p><strong>Telefon:</strong> <?php echo esc_html($first->customer_phone); ?></p>
                            <?php endif; ?>
                            <?php
                                $addr = $customer_addr;
                                if (!$addr) {
                                    $addr = trim($first->customer_street . ', ' . $first->customer_postal . ' ' . $first->customer_city);
                                    if ($first->customer_country) {
                                        $addr .= ', ' . $first->customer_country;
                                    }
                                }
                            ?>
                            <h4>Versandadresse</h4>
                            <p><?php echo esc_html($addr ?: 'Nicht angegeben'); ?></p>
                            <h4>Rechnungsadresse</h4>
                            <p><?php echo esc_html($addr ?: 'Nicht angegeben'); ?></p>
                        </div>
                        <div class="orders-column produkt-accordions orders-accordion">
                            <?php foreach ($rental_orders as $idx => $order) : ?>
                                <?php
                                    $variant_id = $order->variant_id ?? 0;
                                    $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);
                                    $active     = $idx === 0 ? ' active' : '';
                                ?>
                                <div class="produkt-accordion-item<?php echo $active; ?>">
                                    <?php $num = !empty($order->order_number) ? $order->order_number : $order->id; ?>
                                    <button type="button" class="produkt-accordion-header">
                                        Bestellung #<?php echo esc_html($num); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?>
                                    </button>
                                    <div class="produkt-accordion-content">
                                        <?php include PRODUKT_PLUGIN_PATH . 'views/account/order-details.php'; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else : ?>
                    <p>Keine Bestellungen.</p>
                <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
</div>

