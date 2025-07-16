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
                echo '<div class="notice notice-error"><p><strong>Stripe-Bibliothek nicht gefunden:</strong> <code>includes/stripe-php/</code>-Ordner fehlt. Bitte manuell hochladen oder via Composer installieren.</p></div>';
            });
        }
    }
});
