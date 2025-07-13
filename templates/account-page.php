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
        <p>Hier entsteht Ihr persönlicher Kundenbereich.</p>
    <?php endif; ?>
</div>

