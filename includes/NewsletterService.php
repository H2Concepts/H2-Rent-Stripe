<?php
namespace ProduktVerleih;

class NewsletterService {

    public static function create_or_refresh_pending(array $data): void {
        global $wpdb;

        $email = sanitize_email($data['email'] ?? '');
        if (!$email) { return; }

        $token = self::generate_token();
        $hash  = hash('sha256', $token);

        $table = $wpdb->prefix . 'produkt_newsletter_optins';

        $row = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM $table WHERE email = %s", $email));
        $payload = [
            'email'          => $email,
            'first_name'     => sanitize_text_field($data['first_name'] ?? ''),
            'last_name'      => sanitize_text_field($data['last_name'] ?? ''),
            'phone'          => sanitize_text_field($data['phone'] ?? ''),
            'street'         => sanitize_text_field($data['street'] ?? ''),
            'postal_code'    => sanitize_text_field($data['postal_code'] ?? ''),
            'city'           => sanitize_text_field($data['city'] ?? ''),
            'country'        => sanitize_text_field($data['country'] ?? ''),
            'status'         => 0,
            'token_hash'     => $hash,
            'confirmed_at'   => null,
            'stripe_session_id' => sanitize_text_field($data['stripe_session_id'] ?? ''),
            'requested_at'   => current_time('mysql'),
        ];

        if ($row) {
            // Wenn bereits confirmed, NICHT überschreiben (sonst verlierst du den Nachweis).
            if (intval($row->status) === 1) {
                return;
            }
            $wpdb->update($table, $payload, ['id' => intval($row->id)]);
        } else {
            $wpdb->insert($table, $payload);
        }

        self::send_double_optin_mail($email, $token);
    }

    public static function confirm_from_token(string $token): bool {
        global $wpdb;

        $token = trim($token);
        if ($token === '') { return false; }

        $hash  = hash('sha256', $token);
        $table = $wpdb->prefix . 'produkt_newsletter_optins';

        $row = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM $table WHERE token_hash = %s", $hash));
        if (!$row) { return false; }

        if (intval($row->status) === 1) {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $wpdb->update($table, [
            'status'             => 1,
            'confirmed_at'       => current_time('mysql'),
            'confirm_ip'         => sanitize_text_field($ip),
            'confirm_user_agent' => sanitize_textarea_field($ua),
            'token_hash'         => null, // Token nach Nutzung invalidieren
        ], ['id' => intval($row->id)]);

        return true;
    }

    private static function send_double_optin_mail(string $email, string $token): void {
        $confirm_url = add_query_arg([
            'action' => 'produkt_newsletter_confirm',
            'token'  => rawurlencode($token),
        ], admin_url('admin-post.php'));

        $subject = 'Bitte Newsletter-Anmeldung bestätigen';

        $site_title = get_bloginfo('name');
        $logo_url   = get_option('plugin_firma_logo_url', '');
        $divider    = '<div style="height:1px;background:#E6E8ED;margin:20px 0;"></div>';

        $message  = '<html><body style="margin:0;padding:0;background:#F6F7FA;font-family:Arial,sans-serif;color:#000;">';
        $message .= '<div style="max-width:680px;margin:0 auto;padding:24px;">';

        if ($logo_url) {
            $message .= '<div style="text-align:center;margin-bottom:16px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="width:100px;max-width:100%;height:auto;"></div>';
        }

        $message .= '<h1 style="text-align:center;font-size:22px;margin:0 0 40px;">Newsletter-Anmeldung bestätigen</h1>';
        $message .= '<p style="margin:0 0 16px;font-size:14px;line-height:1.6;">Hallo,<br>vielen Dank für Ihre Newsletter-Anmeldung! Bitte bestätigen Sie Ihre Anmeldung, indem Sie auf den folgenden Button klicken:</p>';

        $message .= '<div style="background:#FFFFFF;border-radius:10px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">';
        $message .= '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;">Mit Ihrer Bestätigung erhalten Sie regelmäßig aktuelle Informationen von ' . esc_html($site_title) . ' zu Produkten und exklusiven Angeboten.</p>';

        $message .= '<div style="text-align:center;margin:18px 0 8px;">';
        $message .= '<a href="' . esc_url($confirm_url) . '" style="display:inline-block;padding:14px 36px;background:#000;color:#fff;text-decoration:none;border-radius:999px;font-weight:bold;font-size:15px;">Anmeldung bestätigen</a>';
        $message .= '</div>';
        $message .= '</div>';

        $message .= '<p style="margin:16px 0 8px;font-size:12px;line-height:1.6;color:#666;">Wenn Sie sich nicht angemeldet haben, ignorieren Sie diese E-Mail einfach.</p>';

        if ($logo_url) {
            $message .= '<div style="text-align:center;margin:22px 0 8px;"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_title) . '" style="width:70px;max-width:100%;height:auto;"></div>';
        }

        $message .= $divider;

        $footer_html = function_exists('pv_get_email_footer_html') ? pv_get_email_footer_html() : '';
        if ($footer_html) {
            $message .= $footer_html;
        }

        $message .= '</div>';
        $message .= '</body></html>';

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $from_name  = get_bloginfo('name');
        $from_email = get_option('admin_email');
        $headers[]  = 'From: ' . $from_name . ' <' . $from_email . '>';

        wp_mail($email, $subject, $message, $headers);
    }

    private static function generate_token(): string {
        $bytes = random_bytes(32);
        // URL-safe Base64
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function resend_double_optin(string $email): string {
        global $wpdb;

        $email = sanitize_email($email);
        if (!$email) return 'invalid';

        $table = $wpdb->prefix . 'produkt_newsletter_optins';
        $row = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM $table WHERE email = %s", $email));
        if (!$row) return 'not_found';

        if (intval($row->status) === 1) {
            return 'already_confirmed';
        }

        $token = self::generate_token();
        $hash  = hash('sha256', $token);

        $wpdb->update($table, [
            'token_hash'   => $hash,
            'requested_at' => current_time('mysql'),
        ], ['id' => intval($row->id)]);

        self::send_double_optin_mail($email, $token);

        return 'sent';
    }

    public static function delete_by_email(string $email): void {
        global $wpdb;

        $email = sanitize_email($email);
        if (!$email) return;

        $table = $wpdb->prefix . 'produkt_newsletter_optins';
        $wpdb->delete($table, ['email' => $email]);
    }
}

