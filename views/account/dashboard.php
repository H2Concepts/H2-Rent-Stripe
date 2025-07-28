<?php if (!is_user_logged_in()) : ?>
<div class="produkt-login-wrapper">
    <div class="login-box">
        <h1>Login</h1>
        <p>Bitte die Email Adresse eingeben die bei Ihrer Bestellung verwendet wurde.</p>
        <?php if (!empty($message)) { echo $message; } ?>
        <form method="post" class="login-email-form">
            <?php wp_nonce_field('request_login_code_action', 'request_login_code_nonce'); ?>
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
            <input type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
            <button type="submit" name="request_login_code">Code zum einloggen anfordern</button>
        </form>
        <?php if ($show_code_form) : ?>
        <form method="post" class="login-code-form">
            <?php wp_nonce_field('verify_login_code_action', 'verify_login_code_nonce'); ?>
            <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
            <input type="text" name="code" placeholder="6-stelliger Code" required>
            <button type="submit" name="verify_login_code">Einloggen</button>
        </form>
        <?php endif; ?>
        <a class="back-to-shop" href="<?php echo esc_url(home_url('/shop')); ?>">Zurück zum Shop</a>
    </div>
</div>
<?php else : ?>
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
                        <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">Logout</a>
                    </li>
                </ul>
            </aside>
            <div>
        <?php if ($is_sale) : ?>
            <?php if (!empty($sale_orders)) : ?>
                <?php $first = $sale_orders[0]; ?>
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
                        <?php foreach ($sale_orders as $idx => $order) : ?>
                            <?php
                                $variant_id = $order->variant_id ?? 0;
                                $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);
                                $active     = $idx === 0 ? ' active' : '';
                            ?>
                            <div class="produkt-accordion-item<?php echo $active; ?>">
                                <button type="button" class="produkt-accordion-header">
                                    Bestellung #<?php echo esc_html($order->id); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?>
                                </button>
                                <div class="produkt-accordion-content">
                                    <?php include PRODUKT_PLUGIN_PATH . 'includes/render-order-details.php'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

            </div>
        </div>
    <?php endif; ?>
</div>
