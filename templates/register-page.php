<?php
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
if (!defined('ABSPATH')) { exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first  = sanitize_text_field($_POST['first_name'] ?? '');
    $last   = sanitize_text_field($_POST['last_name'] ?? '');
    $email  = sanitize_email($_POST['email'] ?? '');
    $phone  = sanitize_text_field($_POST['phone'] ?? '');
    $street = sanitize_text_field($_POST['street'] ?? '');
    $postal = sanitize_text_field($_POST['postal_code'] ?? '');
    $city   = sanitize_text_field($_POST['city'] ?? '');
    $country= sanitize_text_field($_POST['country'] ?? '');

    if ($email) {
        global $wpdb;
        $table = $wpdb->prefix . 'produkt_customers';
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE email = %s", $email));
        $data = [
            'first_name' => $first,
            'last_name'  => $last,
            'phone'      => $phone,
            'street'     => $street,
            'postal_code'=> $postal,
            'city'       => $city,
            'country'    => $country,
        ];
        if ($existing) {
            $wpdb->update($table, $data, ['id' => $existing]);
        } else {
            $data['email'] = $email;
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
        }
        $code = wp_rand(100000, 999999);
        set_transient('produkt_login_code_' . $email, $code, 10 * MINUTE_IN_SECONDS);
        wp_mail($email, 'Ihr Login-Code', "Ihr Einmalcode: $code\n\nGültig für 10 Minuten.");
        wp_redirect(add_query_arg(['sent'=>1,'email'=>$email], home_url('/kundenkonto')));
        exit;
    } else {
        $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    }
}
?>
<div class="produkt-register-wrapper">
    <h1>Registrieren</h1>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo esc_html($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="first_name" placeholder="Vorname" required>
        <input type="text" name="last_name" placeholder="Nachname" required>
        <input type="email" name="email" placeholder="E-Mail" required>
        <input type="text" name="phone" placeholder="Telefon">
        <input type="text" name="street" placeholder="Straße">
        <input type="text" name="postal_code" placeholder="PLZ">
        <input type="text" name="city" placeholder="Stadt">
        <input type="text" name="country" placeholder="Land" value="DE">
        <button type="submit">Jetzt registrieren</button>
    </form>
</div>
