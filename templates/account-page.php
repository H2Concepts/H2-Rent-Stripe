<?php
use ProduktVerleih\Database;

require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

if (!defined('ABSPATH')) {
    exit;
}

$message = '';
$db = new Database();

if (isset($_POST['cancel_subscription'], $_POST['cancel_subscription_nonce'])) {
    if (wp_verify_nonce($_POST['cancel_subscription_nonce'], 'cancel_subscription_action')) {
        $sub_id = sanitize_text_field($_POST['subscription_id']);
        $res    = \ProduktVerleih\StripeService::cancel_subscription_at_period_end($sub_id);
        if (is_wp_error($res)) {
            $redirect = add_query_arg('cancel_msg', rawurlencode($res->get_error_message()), get_permalink());
        } else {
            $redirect = add_query_arg('cancel_msg', 'success', get_permalink());
        }
        wp_safe_redirect($redirect);
        exit;
    }
}

if (isset($_GET['cancel_msg'])) {
    if ($_GET['cancel_msg'] === 'success') {
        $message = '<p>Kündigung vorgemerkt.</p>';
    } else {
        $message = '<p style="color:red;">' . esc_html($_GET['cancel_msg']) . '</p>';
    }
}

?>
<?php if (!is_user_logged_in()) : ?>
<div class="produkt-login-wrapper">
    <div class="login-box">
        <h1>Login</h1>
        <p>Bitte die Email Adresse eingeben die bei Ihrer Bestellung verwendet wurde.</p>
        <?php if ($message) { echo $message; } ?>
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
    <?php if ($message) { echo $message; } ?>
        <?php
        $orders = Database::get_orders_for_user(get_current_user_id());

        // Preload months_minimum for all durations referenced by orders
        $duration_map = [];
        $duration_ids = array();
        foreach ($orders as $tmp_o) {
            if (!empty($tmp_o->duration_id)) {
                $duration_ids[] = (int) $tmp_o->duration_id;
            }
        }
        $duration_ids = array_unique($duration_ids);
        if ($duration_ids) {
            global $wpdb;
            $placeholders = implode(',', array_fill(0, count($duration_ids), '%d'));
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, months_minimum FROM {$wpdb->prefix}produkt_durations WHERE id IN ($placeholders)",
                $duration_ids
            ));
            foreach ($rows as $row) {
                $duration_map[$row->id] = (int) $row->months_minimum;
            }
        }

        $full_name = '';
        foreach ($orders as $o) {
            if (!empty($o->customer_name)) {
                $full_name = $o->customer_name;
                break;
            }
        }
        if (!$full_name) {
            $full_name = wp_get_current_user()->display_name;
        }
        ?>
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
                if ($order && !empty($order->duration_id) && isset($duration_map[$order->duration_id])) {
                    $laufzeit_in_monaten = $duration_map[$order->duration_id];
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

                $variant_id = $order->variant_id ?? 0;
                $image_url = '';
                if ($order) {
                    $image_url = pv_get_variant_image_url($variant_id);
                    if (empty($image_url) && !empty($order->category_id)) {
                        global $wpdb;
                        $image_url = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT default_image FROM {$wpdb->prefix}produkt_categories WHERE id = %d",
                                $order->category_id
                            )
                        );
                    }
                }

                $address = trim($order->customer_street . ', ' . $order->customer_postal . ' ' . $order->customer_city);
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
