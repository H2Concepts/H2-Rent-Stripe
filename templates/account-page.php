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
    <!-- Hier kommen später die Kundendaten -->
<?php endif; ?>
