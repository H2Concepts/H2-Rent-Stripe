<?php if (!is_user_logged_in()) : ?>
<div class="produkt-login-wrapper">
    <div class="login-box">
        <h1>Login</h1>
        <p>Bitte die Email Adresse eingeben die bei Ihrer Bestellung verwendet wurde.</p>
        <?php if (!empty($message)) { echo $message; } ?>
        <form method="post" class="login-email-form">
            <?php wp_nonce_field('request_login_code_action', 'request_login_code_nonce'); ?>
            <input type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
            <button type="submit" name="request_login_code">Code zum einloggen anfordern</button>
        </form>
        <?php if ($show_code_form) : ?>
        <form method="post" class="login-code-form">
            <?php wp_nonce_field('verify_login_code_action', 'verify_login_code_nonce'); ?>
            <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
            <input type="text" name="code" placeholder="6-stelliger Code" required>
            <button type="submit" name="verify_login_code">Einloggen</button>
        </form>
        <?php endif; ?>
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
                        <a href="#" class="active">Abos</a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">Logout</a>
                    </li>
                </ul>
            </aside>
            <div>
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

        <?php if (!empty($invoices)) : ?>
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

        <?php if (!empty($orders)) : ?>
            <div class="produkt-section">
                <h3>Meine bisherigen Bestellungen</h3>
                <table class="plugin-orders-table">
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Details</th>
                            <th>Zeitraum</th>
                            <th>Preis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o) : ?>
                        <tr>
                            <td><?php echo esc_html($o->produkt_name); ?></td>
                            <td>
                                <?php echo esc_html($o->zustand_text); ?><br>
                                <?php echo esc_html($o->produktfarbe_text); ?> / <?php echo esc_html($o->gestellfarbe_text); ?>
                            </td>
                            <td><?php echo esc_html($o->dauer_text); ?></td>
                            <td><?php echo esc_html(number_format($o->amount_total / 100, 2, ',', '.')); ?> €</td>
                            <td><?php echo esc_html(ucfirst($o->status)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="produkt-section">
                <h3>Meine bisherigen Bestellungen</h3>
                <p>Sie haben bisher keine Bestellungen aufgegeben.</p>
            </div>
        <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
