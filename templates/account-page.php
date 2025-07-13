<?php
if (!defined('ABSPATH')) {
    exit;
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
            <ul class="produkt-subscriptions">
                <?php foreach ($subscriptions as $s) : ?>
                    <li>
                        <?php echo esc_html($s['subscription_id']); ?>
                        <?php if (empty($s['cancel_at_period_end'])) : ?>
                            <form method="post" style="display:inline;margin-left:10px;">
                                <input type="hidden" name="cancel_subscription" value="<?php echo esc_attr($s['subscription_id']); ?>">
                                <button type="submit">Jetzt kündigen</button>
                            </form>
                        <?php else : ?>
                            <span style="margin-left:10px;">Kündigung vorgemerkt</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>Keine aktiven Abos.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

