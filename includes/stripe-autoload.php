<?php
namespace ProduktVerleih;

if (!defined('ABSPATH')) {
    exit;
}

// Stripe erst laden, wenn alle Plugins geladen sind
add_action('plugins_loaded', function () {
    if (!class_exists('\\Stripe\\Stripe')) {
        $stripe_path = __DIR__ . '/stripe-php/init.php';

        if (file_exists($stripe_path)) {
            require_once $stripe_path;
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>' . esc_html__('Stripe-Bibliothek nicht gefunden:', 'h2-rental-pro') . '</strong> <code>includes/stripe-php/</code>-Ordner fehlt. ' . esc_html__('Bitte manuell hochladen oder via Composer installieren.', 'h2-rental-pro') . '</p></div>';
            });
        }
    }
});
