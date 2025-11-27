<?php if (!is_user_logged_in()) : ?>
<?php
    $login_layout    = $branding['login_layout'] ?? 'classic';
    $login_logo      = $branding['login_logo'] ?? '';
    $login_bg_image  = $branding['login_bg_image'] ?? '';
    $site_name       = get_bloginfo('name');
?>

<?php if ($login_layout === 'split') : ?>
<div class="produkt-login-wrapper login-layout-split">
    <div class="produkt-login-panel">
        <div class="login-panel-header">
            <?php if ($login_logo) : ?>
                <div class="login-brand-logo">
                    <img src="<?php echo esc_url($login_logo); ?>" alt="<?php echo esc_attr($site_name); ?> Logo">
                </div>
            <?php endif; ?>
        </div>
        <div class="login-panel-main">
            <h1>Schön, dass du da bist!</h1>
            <p class="login-lead">Melde dich mit der E-Mail an, die du bei deiner Bestellung angegeben hast. Wir senden dir einen Login-Code.</p>
            <?php if (!empty($message)) { echo $message; } ?>
            <form method="post" class="login-email-form">
                <?php wp_nonce_field('request_login_code_action', 'request_login_code_nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
                <label for="login-email" class="screen-reader-text">E-Mail-Adresse</label>
                <input id="login-email" type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
                <button type="submit" name="request_login_code">Code zum Einloggen anfordern</button>
            </form>
            <?php if ($show_code_form) : ?>
                <form method="post" class="login-code-form">
                    <?php wp_nonce_field('verify_login_code_action', 'verify_login_code_nonce'); ?>
                    <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_to); ?>">
                    <label for="login-code" class="screen-reader-text">6-stelliger Code</label>
                    <input id="login-code" type="text" name="code" placeholder="6-stelliger Code" required>
                    <button type="submit" name="verify_login_code">Einloggen</button>
                </form>
            <?php endif; ?>
        </div>
        <p class="login-signup-hint">Du hast noch kein <?php echo esc_html($site_name); ?>-Account?<br>
            <a href="<?php echo esc_url(home_url('/shop')); ?>">Jetzt <?php echo esc_html($site_name); ?>-Kunde werden.</a>
        </p>
    </div>
    <div class="produkt-login-visual"<?php echo $login_bg_image ? ' style="background-image:url(' . esc_url($login_bg_image) . ');"' : ''; ?> aria-hidden="true"></div>
</div>
<?php else : ?>
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
<?php endif; ?>
<?php else : ?>
<div class="produkt-account-wrapper produkt-container shop-overview-container">
    <h1>Kundenkonto</h1>
    <?php if (!empty($message)) { echo $message; } ?>
    <?php if ($is_sale) : ?>
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
                                <?php $num = !empty($order->order_number) ? $order->order_number : $order->id; ?>
                                <button type="button" class="produkt-accordion-header">
                                    Bestellung #<?php echo esc_html($num); ?> – <?php echo esc_html(date_i18n('d.m.Y', strtotime($order->created_at))); ?>
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
            </div>
        </div>
    <?php else : ?>
        <?php
            $active_subscriptions = !empty($subscriptions) ? array_filter($subscriptions) : [];
            $active_count         = is_array($active_subscriptions) ? count($active_subscriptions) : 0;

            $monthly_total = 0.0;
            if (!empty($active_subscriptions)) {
                foreach ($active_subscriptions as $sub) {
                    $order = $order_map[$sub['subscription_id']] ?? null;
                    if ($order && isset($order->final_price)) {
                        $monthly_total += max(0.0, (float) $order->final_price);
                    }
                }
            }

            $monthly_total_formatted = number_format($monthly_total, 2, ',', '.');
            $user_id                 = get_current_user_id();
            $ref_seed                = $user_id ? $user_id . wp_get_current_user()->user_email : 'customer';
            $invite_code             = 'FREUND-' . strtoupper(substr(md5($ref_seed), 0, 6));
        ?>
        <div class="account-dashboard-grid">
            <div class="account-dashboard-card">
                <div class="card-title">Aktive Abos</div>
                <div class="card-metric"><?php echo esc_html($active_count); ?></div>
                <button type="button" class="card-button" aria-disabled="true">Abos ansehen</button>
            </div>
            <div class="account-dashboard-card">
                <div class="card-title">Summe pro Monat</div>
                <div class="card-metric"><?php echo esc_html($monthly_total_formatted); ?>€</div>
                <button type="button" class="card-button" aria-disabled="true">Alle Rechnungen</button>
            </div>
            <div class="account-dashboard-card">
                <div class="card-title">Freunde einladen</div>
                <div class="card-code"><?php echo esc_html($invite_code); ?></div>
                <button type="button" class="card-button" aria-disabled="true">Code kopieren</button>
            </div>
            <div class="account-dashboard-card">
                <div class="card-title">Logout</div>
                <div class="card-helper">Beende deine Sitzung sicher.</div>
                <a class="card-button card-button-link" href="<?php echo esc_url(wp_logout_url(get_permalink())); ?>">Jetzt ausloggen</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
