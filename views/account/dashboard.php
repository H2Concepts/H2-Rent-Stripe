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
            $subscription_lookup  = [];
            foreach ($active_subscriptions as $sub_meta) {
                if (!empty($sub_meta['subscription_key'])) {
                    $subscription_lookup[$sub_meta['subscription_key']] = $sub_meta;
                }
            }

            $view               = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'overview';
            $selected_sub_id    = isset($_GET['subscription']) ? sanitize_text_field($_GET['subscription']) : '';
            $selected_order_id  = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : '';
            $overview_url       = remove_query_arg(['view', 'subscription', 'order']);
            $subscriptions_url  = add_query_arg('view', 'abos', $overview_url);

            $monthly_total = 0.0;

            if (!empty($active_subscriptions)) {
                foreach ($active_subscriptions as $sub) {
                    $sub_id         = trim((string) ($sub['subscription_key'] ?? ''));
                    $orders_for_sub = $order_map[$sub_id] ?? [];
                    if (!is_array($orders_for_sub)) {
                        $orders_for_sub = [$orders_for_sub];
                    }

                    foreach ($orders_for_sub as $order) {
                        if ($order && isset($order->final_price)) {
                            $monthly_total += max(0.0, (float) $order->final_price);
                        }
                    }
                }
            }

            $monthly_total_formatted = number_format($monthly_total, 2, ',', '.');
            $user_id                 = get_current_user_id();
            $ref_seed                = $user_id ? $user_id . wp_get_current_user()->user_email : 'customer';
            $invite_code             = 'FREUND-' . strtoupper(substr(md5($ref_seed), 0, 6));

            $selected_order = null;
            $selected_sub_id = trim((string) $selected_sub_id);

            if ($selected_sub_id) {
                $orders_for_selected = $order_map[$selected_sub_id] ?? [];
                if (!is_array($orders_for_selected)) {
                    $orders_for_selected = [$orders_for_selected];
                }

                if ($selected_order_id) {
                    foreach ($orders_for_selected as $order_item) {
                        if ((string) ($order_item->id ?? '') === (string) $selected_order_id) {
                            $selected_order = $order_item;
                            break;
                        }
                    }
                }

                if (!$selected_order && !empty($orders_for_selected)) {
                    $selected_order = $orders_for_selected[0];
                }
            }
        ?>
        <?php if ($view === 'abos' && !$selected_sub_id) : ?>
            <div class="account-section-header">
                <a class="account-back-link" href="<?php echo esc_url($overview_url); ?>">&larr; Zurück</a>
                <h4>Meine Abos</h4>
            </div>
            <div class="subscription-grid">
                <?php if (!empty($active_subscriptions)) : ?>
                    <?php foreach ($active_subscriptions as $sub) : ?>
                        <?php
                            $sub_id         = trim((string) ($sub['subscription_key'] ?? ''));
                            $orders_for_sub = $order_map[$sub_id] ?? [];
                            if (!is_array($orders_for_sub)) {
                                $orders_for_sub = [$orders_for_sub];
                            }
                        ?>
                        <?php foreach ($orders_for_sub as $order) : ?>
                            <?php
                                $variant_id  = $order ? ($order->variant_id ?? 0) : 0;
                                $category_id = $order ? ($order->category_id ?? 0) : 0;
                                $image_url   = pv_get_image_url_by_variant_or_category($variant_id, $category_id);
                                $product     = $order ? ($order->category_name ?? $order->product_title ?? $order->produkt_name ?? ($order->variant_name ?? 'Produkt')) : 'Produkt';
                                $order_date  = (!empty($order) && !empty($order->created_at)) ? date_i18n('d.m.Y', strtotime($order->created_at)) : '–';
                                $duration    = (!empty($order) && !empty($order->duration_name)) ? $order->duration_name : ((!empty($order) && !empty($order->dauer_text)) ? $order->dauer_text : 'Mindestlaufzeit');
                                $payments    = $order ? pv_calculate_rental_payments($order) : ['monthly_amount' => 0];
                                $monthly     = number_format($payments['monthly_amount'] ?? 0, 2, ',', '.') . ' €';
                                $min_months  = $order ? pv_get_minimum_duration_months($order) : 0;
                                $min_label   = $min_months ? $min_months . ' Monate' : '';
                                $detail_url  = add_query_arg(
                                    [
                                        'view'         => 'abo-detail',
                                        'subscription' => $sub_id,
                                        'order'        => $order->id ?? '',
                                    ],
                                    $overview_url
                                );
                            ?>
                            <a class="subscription-card" href="<?php echo esc_url($detail_url); ?>">
                                <img class="subscription-thumb" src="<?php echo esc_url($image_url ?: ''); ?>" alt="">
                                <div class="subscription-card-body">
                                    <div class="subscription-card-title"><?php echo esc_html($product); ?></div>
                                    <div class="subscription-meta">
                                        <span class="pill-badge success">ABO AKTIV</span>
                                        <div><strong>Bestelldatum:</strong> <?php echo esc_html($order_date); ?></div>
                                        <div><strong>Mindestlaufzeit:</strong> <?php echo esc_html($min_label ?: $duration); ?></div>
                                        <div><strong>Monatliche Kosten:</strong> <?php echo esc_html($monthly); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>Keine aktiven Abos vorhanden.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($view === 'abo-detail' && $selected_sub_id && $selected_order) : ?>
            <?php
                $product      = $selected_order->category_name ?? $selected_order->product_title ?? $selected_order->produkt_name ?? ($selected_order->variant_name ?? 'Produkt');
                $order_date   = !empty($selected_order->created_at) ? date_i18n('d.m.Y', strtotime($selected_order->created_at)) : '–';
                $order_number = $selected_order->order_number ?? $selected_order->id ?? '–';
                $duration     = !empty($selected_order->duration_name) ? $selected_order->duration_name : (!empty($selected_order->dauer_text) ? $selected_order->dauer_text : 'Mindestlaufzeit');
                $condition    = $selected_order->condition_name ?? $selected_order->zustand_text ?? '';
                $color        = $selected_order->product_color_name ?? $selected_order->produktfarbe_text ?? '';
                $variant      = $selected_order->variant_name ?? '';
                $payments     = pv_calculate_rental_payments($selected_order);
                $monthly      = number_format($payments['monthly_amount'], 2, ',', '.') . ' €';

                $start_date_raw = pv_get_order_start_date($selected_order);
                $start_date_raw = $start_date_raw ?: (!empty($selected_order->created_at) ? date('Y-m-d', strtotime($selected_order->created_at)) : '');
                $start_ts       = $start_date_raw ? strtotime($start_date_raw) : current_time('timestamp');
                $min_months     = pv_get_minimum_duration_months($selected_order);
                $min_end_ts     = strtotime('+' . $min_months . ' months', $start_ts);
                $min_end_date   = date_i18n('d.m.Y', $min_end_ts);
                $cancel_ready_ts = strtotime('-14 days', $min_end_ts);
                $cancel_ready    = current_time('timestamp') >= $cancel_ready_ts;
                $cancel_open_date = date_i18n('d.m.Y', $cancel_ready_ts);
                $selected_meta   = $subscription_lookup[$selected_sub_id] ?? [];
                $cancel_sub_id   = $selected_meta['subscription_id'] ?? $selected_sub_id;
            ?>
            <div class="account-section-header">
                <a class="account-back-link" href="<?php echo esc_url($subscriptions_url); ?>">&larr; Zurück</a>
                <h4><?php echo esc_html($product); ?></h4>
            </div>
            <div class="subscription-detail-grid">
                <div class="subscription-detail-card">
                    <h3>Bestellt</h3>
                    <p><strong>Bestelldatum:</strong><br><?php echo esc_html($order_date); ?></p>
                    <p><strong>Bestellnummer:</strong><br><?php echo esc_html($order_number); ?></p>
                </div>
                <div class="subscription-detail-card">
                    <h3>Produkt</h3>
                    <p><strong>Produkt:</strong><br><?php echo esc_html($product); ?></p>
                    <?php if (!empty($variant)) : ?><p><strong>Ausführung:</strong><br><?php echo esc_html($variant); ?></p><?php endif; ?>
                    <?php if (!empty($condition)) : ?><p><strong>Zustand:</strong><br><?php echo esc_html($condition); ?></p><?php endif; ?>
                    <?php if (!empty($color)) : ?><p><strong>Farbe:</strong><br><?php echo esc_html($color); ?></p><?php endif; ?>
                </div>
                <div class="subscription-detail-card">
                    <h3>Ende der Mindestlaufzeit</h3>
                    <p><strong>Datum:</strong><br><?php echo esc_html($min_end_date); ?></p>
                </div>
                <div class="subscription-detail-card">
                    <h3>Kündigung</h3>
                    <form method="post">
                        <?php wp_nonce_field('cancel_subscription_action', 'cancel_subscription_nonce'); ?>
                        <input type="hidden" name="subscription_id" value="<?php echo esc_attr($cancel_sub_id); ?>">
                        <button type="submit" name="cancel_subscription" class="card-button cancel-button<?php echo $cancel_ready ? ' is-active' : ''; ?>" <?php echo ($cancel_ready && $cancel_sub_id) ? '' : 'disabled'; ?>>Jetzt kündigen</button>
                    </form>
                    <p class="card-helper">Kündigung möglich ab dem <?php echo esc_html($cancel_open_date); ?>.</p>
                </div>
                <div class="subscription-detail-card subscription-wide-card">
                    <h3>Abodetails</h3>
                    <p><strong>Mindestlaufzeit:</strong><br><?php echo esc_html($min_months . ' Monate'); ?></p>
                    <p><strong>Monatlicher Mietpreis:</strong><br><?php echo esc_html($monthly); ?></p>
                    <p><strong>Ende der Mindestlaufzeit:</strong><br><?php echo esc_html($min_end_date); ?></p>
                    <p><strong>Datum für Kündigung:</strong><br><?php echo esc_html($cancel_open_date); ?></p>
                </div>
            </div>
        <?php else : ?>
            <div class="account-dashboard-grid">
                <div class="account-dashboard-card">
                    <div class="card-title">Aktive Abos</div>
                    <div class="card-metric"><?php echo esc_html($active_count); ?></div>
                    <a class="card-button card-button-link" href="<?php echo esc_url($subscriptions_url); ?>">Abos ansehen</a>
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
    <?php endif; ?>
</div>
<?php endif; ?>
