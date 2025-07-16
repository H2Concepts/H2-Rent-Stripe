<?php
namespace ProduktVerleih;

if (!defined('ABSPATH')) {
    exit;
}

function load_stripe_sdk() {
    if (!class_exists('\\Stripe\\Stripe')) {
        $vendor_path = __DIR__ . '/../vendor/autoload.php';

        if (file_exists($vendor_path)) {
            require_once $vendor_path;
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p><strong>Stripe-Bibliothek nicht gefunden.</strong> Bitte führen Sie <code>composer install</code> aus oder laden Sie den <code>/vendor</code>-Ordner hoch.</p></div>';
            });
        }
    }
}

load_stripe_sdk();
