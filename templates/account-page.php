<?php
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';

if (!defined('ABSPATH')) {
    exit;
}

if (isset($_GET['logout'])) {
    produkt_customer_logout();
    wp_redirect(home_url('/kundenkonto'));
    exit;
}

if (produkt_is_customer_logged_in()) {
    include plugin_dir_path(__FILE__) . '../views/account/dashboard.php';
    return;
}

$sent  = false;
$error = '';
$email = '';
if (isset($_GET['sent'], $_GET['email']) && $_GET['sent'] == 1) {
    $sent  = true;
    $email = sanitize_email($_GET['email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_email'])) {
    $email = sanitize_email($_POST['customer_email']);
    global $wpdb;
    $table = $wpdb->prefix . 'produkt_customers';
    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email = %s", $email));
    if ($customer) {
        $code = wp_rand(100000, 999999);
        set_transient('produkt_login_code_' . $email, $code, 10 * MINUTE_IN_SECONDS);
        wp_mail($email, 'Ihr Login-Code', "Ihr Einmalcode: $code\n\nGültig für 10 Minuten.");
        $sent = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_email'], $_POST['login_code'])) {
    $email  = sanitize_email($_POST['login_email']);
    $code   = sanitize_text_field($_POST['login_code']);
    $stored = get_transient('produkt_login_code_' . $email);

    if ($stored && $stored === $code) {
        produkt_customer_login($email);
        delete_transient('produkt_login_code_' . $email);
        wp_redirect(home_url('/kundenkonto'));
        exit;
    } else {
        $error = 'Ungültiger oder abgelaufener Code.';
    }
}
?>
<div class="kundenkonto-wrap">
    <h1>Kundenkonto</h1>

    <?php if ($sent): ?>
        <p>Ein Code wurde an Ihre E-Mail-Adresse gesendet. Bitte prüfen Sie Ihr Postfach.</p>
        <form method="post">
            <input type="hidden" name="login_email" value="<?php echo esc_attr($email); ?>">
            <input type="text" name="login_code" placeholder="Einmalcode" required>
            <button type="submit">Anmelden</button>
        </form>
    <?php else: ?>
        <form method="post">
            <input type="email" name="customer_email" placeholder="E-Mail-Adresse" required>
            <button type="submit">Login-Code senden</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo esc_html($error); ?></p>
    <?php endif; ?>
</div>
