<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!is_user_logged_in()) : ?>
    <h2>Login zum Kundenbereich</h2>
    <?php if (!empty($show_code_form)) : ?>
        <form method="post">
            <input type="hidden" name="email" value="<?php echo esc_attr($email_value); ?>">
            <input type="text" name="code" placeholder="6-stelliger Code" required>
            <input type="submit" name="verify_login_code" value="Jetzt einloggen">
        </form>
    <?php else: ?>
        <form method="post">
            <input type="email" name="email" placeholder="Ihre E-Mail-Adresse" required>
            <input type="submit" name="request_login_code" value="Login-Code anfordern">
        </form>
    <?php endif; ?>
<?php else: ?>
    <p>Willkommen zurück, <?php echo esc_html(wp_get_current_user()->display_name); ?>!</p>
    <?php foreach ($subscriptions as $sub) :
        $start_ts = (int) $sub['start_date'];
        $start_formatted = date_i18n(get_option('date_format'), $start_ts);
        $cancelable_ts   = strtotime('+3 months', $start_ts);
        $cancelable_date = date_i18n(get_option('date_format'), $cancelable_ts);
        $cancelable      = time() > $cancelable_ts;
    ?>
        <div class="abo-box">
            <h3><?php echo esc_html($sub['product_name']); ?></h3>
            <p>Gemietet seit: <?php echo esc_html($start_formatted); ?></p>
            <p>Kündbar ab: <?php echo esc_html($cancelable_date); ?></p>

            <?php if ($sub['cancel_at_period_end']) : ?>
                <p style="color:orange;">Kündigung vorgemerkt.</p>
            <?php elseif ($cancelable) : ?>
                <form method="post">
                    <input type="hidden" name="cancel_subscription" value="<?php echo esc_attr($sub['subscription_id']); ?>">
                    <button type="submit">Jetzt kündigen</button>
                </form>
            <?php else : ?>
                <p>Mindestlaufzeit noch aktiv.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($user_orders)) : ?>
        <h2>Ihre Mietprodukte</h2>
        <?php foreach ($user_orders as $order): ?>
            <div class="mietprodukt-box">
                <h3><?php echo esc_html($order->produkt_name); ?></h3>
                <?php if (!empty($order->produkt_image)) : ?>
                    <img src="<?php echo esc_url($order->produkt_image); ?>" style="max-width: 150px;">
                <?php endif; ?>
                <ul>
                    <li><strong>Gemietet am:</strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($order->created_at))); ?></li>
                    <li><strong>Dauer:</strong> <?php echo esc_html($order->dauer_text); ?></li>
                    <li><strong>Extras:</strong> <?php echo esc_html($order->extra_text); ?></li>
                    <li><strong>Farbe:</strong> <?php echo esc_html($order->produktfarbe_text); ?></li>
                    <li><strong>Zustand:</strong> <?php echo esc_html($order->zustand_text); ?></li>
                    <li><strong>Status:</strong> <?php echo esc_html($order->status); ?></li>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>Aktuell liegen keine Mietprodukte vor.</p>
    <?php endif; ?>
    <style>
    .mietprodukt-box {
        border: 1px solid #ccc;
        padding: 16px;
        margin-bottom: 24px;
        border-radius: 8px;
        background: #f9f9f9;
    }
    .mietprodukt-box img {
        float: right;
        margin-left: 16px;
    }
    </style>
<?php endif; ?>
