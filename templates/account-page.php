<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!is_user_logged_in()) : ?>
    <h2>Login zum Kundenbereich</h2>
    <form method="post">
        <label for="produkt_email">Ihre E-Mail-Adresse:</label>
        <input type="email" name="produkt_email" id="produkt_email" required>
        <button type="submit" name="produkt_login_request">Login-Link anfordern</button>
    </form>
<?php else: ?>
    <p>Willkommen zurück, <?php echo wp_get_current_user()->display_name; ?>!</p>
    <!-- Hier kommen später die Kundendaten -->
<?php endif; ?>
