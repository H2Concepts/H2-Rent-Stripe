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
<?php endif; ?>
