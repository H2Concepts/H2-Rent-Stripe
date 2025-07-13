<?php
if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['verify_login_code'])) {
    $email      = sanitize_email($_POST['email'] ?? '');
    $input_code = sanitize_text_field($_POST['code'] ?? '');
    $user       = get_user_by('email', $email);

    if ($user) {
        $data = get_user_meta($user->ID, 'produkt_login_code', true);
        if ($data && $data['code'] == $input_code && time() <= $data['expires']) {
            delete_user_meta($user->ID, 'produkt_login_code');
            delete_user_meta($user->ID, 'login_code');
            delete_user_meta($user->ID, 'login_code_time');
            wp_set_auth_cookie($user->ID, true);
            wp_safe_redirect(get_permalink());
            exit;
        }
    }
}
?>
<div class="produkt-account-wrapper">
    <?php if (!is_user_logged_in()) : ?>
        <form method="post" class="produkt-account-email-form">
            <input type="email" name="email" placeholder="Ihre E-Mail" value="<?php echo esc_attr($email_value); ?>" required>
            <button type="submit" name="request_login_code">Login-Code anfordern</button>
        </form>
        <?php if ($show_code_form) : ?>
        <form method="post" class="produkt-account-code-form">
            <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
            <input type="text" name="code" placeholder="6-stelliger Code" required>
            <button type="submit" name="verify_login_code">Einloggen</button>
        </form>
        <?php endif; ?>
    <?php else : ?>
        <?php if (!empty($subscriptions)) : ?>
            <h2>Ihre Abos</h2>
            <?php
            $orders = Database::get_orders_for_user(get_current_user_id());
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
                if ($order && !empty($order->dauer_text) && preg_match('/(\d+)\+/', $order->dauer_text, $m)) {
                    $laufzeit_in_monaten = (int) $m[1];
                }
                $cancelable_ts = strtotime("+{$laufzeit_in_monaten} months", $start_ts);
                $cancelable_date = date_i18n('d.m.Y', $cancelable_ts);
                $cancelable = time() > $cancelable_ts;
                ?>
                <div class="abo-box">
                    <h3><?php echo esc_html($product_name); ?></h3>
                    <p><strong>Gemietet seit:</strong> <?php echo esc_html($start_formatted); ?></p>
                    <p><strong>Kündbar ab:</strong> <?php echo esc_html($cancelable_date); ?></p>

                    <?php if ($sub['cancel_at_period_end']) : ?>
                        <p style="color:orange;"><strong>✅ Kündigung vorgemerkt.</strong></p>
                    <?php elseif ($cancelable) : ?>
                        <form method="post">
                            <input type="hidden" name="cancel_subscription" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                            <button type="submit" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">Jetzt kündigen</button>
                        </form>
                    <?php else : ?>
                        <p style="color:#888;"><strong>⏳ Mindestlaufzeit noch aktiv.</strong></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p>Keine aktiven Abos.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<style>
.abo-box {
    border: 1px solid #ddd;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    background: #fff;
}
.abo-box h3 {
    margin-top: 0;
}
</style>
