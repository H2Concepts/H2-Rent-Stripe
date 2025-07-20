<?php if (!is_user_logged_in()) : ?>
<div class="produkt-login-wrapper">
    <div class="login-box">
        <h1>Login</h1>
        <p>Bitte die Email Adresse eingeben die bei Ihrer Bestellung verwendet wurde.</p>
        <?php echo $message; ?>
        <form method="post" class="login-email-form">
            <input type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
            <button type="submit" name="request_login_code">Code zum einloggen anfordern</button>
        </form>
        <?php if ($show_code_form) : ?>
        <form method="post" class="login-code-form">
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
    <?php echo $message; ?>
        <div class="account-layout">
            <aside class="account-sidebar shop-category-list">
                <h2>Hallo <?php echo esc_html($full_name); ?></h2>
                <ul>
                    <li>
                        <a href="#" class="active"><span class="menu-icon">📦</span> Abos</a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">
                            <span class="menu-icon">🚪</span> Logout
                        </a>
                    </li>
                </ul>
            </aside>
            <div>
        <?php if (!empty($subscriptions)) : ?>
            <?php
            $order_map = [];
            foreach ($orders as $o) {
                $order_map[$o->subscription_id] = $o;
            }
            ?>
            <?php foreach ($subscriptions as $sub) : ?>
                <?php
                $order = $order_map[$sub['subscription_id']] ?? null;
                $product_name = $order->produkt_name ?? $sub['subscription_id'];
                $start_ts = strtotime($sub['start_date']);
                $start_formatted = date_i18n('d.m.Y', $start_ts);
                $laufzeit_in_monaten = 3;
                if ($order && !empty($order->duration_id)) {
                    global $wpdb;
                    $laufzeit_in_monaten = (int) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT months_minimum FROM {$wpdb->prefix}produkt_durations WHERE id = %d",
                            $order->duration_id
                        )
                    );
                    if (!$laufzeit_in_monaten) {
                        $laufzeit_in_monaten = 3; // Fallback
                    }
                }
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

                $image_url = '';
                if ($order) {
                    global $wpdb;
                    if (!empty($order->variant_id)) {
                        $image_url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT image_url_1 FROM {$wpdb->prefix}produkt_variants WHERE id = %d",
                                $order->variant_id
                            )
                        );
                    }

                    if (empty($image_url) && !empty($order->category_id)) {
                        $image_url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT default_image FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                                $order->category_id
                            )
                        );
                    }
                }

                $address = esc_html(trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city));
                ?>
                <?php include PRODUKT_PLUGIN_PATH . 'includes/render-subscription.php'; ?>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine aktiven Abos.</p>
        <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
