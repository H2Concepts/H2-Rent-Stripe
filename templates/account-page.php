<?php
use ProduktVerleih\Database;

if (!defined('ABSPATH')) {
    exit;
}

$db = new Database();

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
                $cancelable_ts       = strtotime("+{$laufzeit_in_monaten} months", $start_ts);
                $cancelable_date       = date_i18n('d.m.Y', $cancelable_ts);
                $kuendigungsfenster_ts = strtotime('-14 days', $cancelable_ts);
                $cancelable            = time() >= $kuendigungsfenster_ts;
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
                            <p style="margin-bottom:8px;"> Sie können jetzt kündigen – die Kündigung wird zum Ende der Mindestlaufzeit wirksam (<?php echo esc_html($cancelable_date); ?>).</p>
                            <button type="submit" style="background:#dc3545;color:white;border:none;padding:10px 20px;border-radius:5px;">
                                Zum Laufzeitende kündigen
                            </button>
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
