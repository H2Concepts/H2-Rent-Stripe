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
                        <?php
                            $initials = '';
                            if (!empty($first->customer_name)) {
                                $parts = preg_split('/\s+/', trim($first->customer_name));
                                $initials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
                            }
                            $shipping_addr = $customer_addr;
                            if (!$shipping_addr) {
                                $shipping_addr = trim($first->customer_street . ', ' . $first->customer_postal . ' ' . $first->customer_city);
                                if ($first->customer_country) {
                                    $shipping_addr .= ', ' . $first->customer_country;
                                }
                            }
                            $billing_addr = $shipping_addr;
                        ?>
                        <div class="order-box customer-box">
                            <h3 class="kundendaten-heading">Kundendaten</h3>
                            <div class="customer-header">
                                <div class="customer-avatar"><?php echo esc_html($initials); ?></div>
                                <div class="customer-ident">
                                    <?php if (!empty($first->customer_name)) : ?>
                                        <h3 class="customer-name"><?php echo esc_html($first->customer_name); ?></h3>
                                    <?php endif; ?>
                                    <?php if (!empty($first->customer_email)) : ?>
                                        <p class="customer-email"><?php echo esc_html($first->customer_email); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($first->customer_phone)) : ?>
                                <p class="customer-phone">Telefon: <?php echo esc_html($first->customer_phone); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="address-row">
                            <div class="order-box">
                                <h3 class="address-heading">Versandadresse</h3>
                                <p><?php echo esc_html($shipping_addr ?: 'Nicht angegeben'); ?></p>
                            </div>
                            <div class="order-box">
                                <h3 class="address-heading">Rechnungsadresse</h3>
                                <p><?php echo esc_html($billing_addr ?: 'Nicht angegeben'); ?></p>
                            </div>
                        </div>
                        <div class="orders-column produkt-accordions orders-accordion">
                            <?php foreach ($rental_orders as $idx => $order) : ?>
                                <?php
                                    $variant_id = $order->variant_id ?? 0;
                                    $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);
                                    $active     = $idx === 0 ? ' active' : '';
                                ?>
                                <div class="produkt-accordion-item<?php echo $active; ?>">
                                    <button type="button" class="produkt-accordion-header">
                                        Bestellung vom <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?>
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

