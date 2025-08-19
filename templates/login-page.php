<?php
if (!defined('ABSPATH')) { exit; }

$message        = $message ?? '';
$show_code_form = isset($show_code_form) ? $show_code_form : false;
$email_value    = $email_value ?? '';
$redirect_to    = $redirect_to ?? '';
?>
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
