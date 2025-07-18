<?php
/**
 * Template: Checkout Page
 * Zeigt das Stripe Embedded Checkout Formular
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<?php $variant_id = isset($_GET['variant_id']) ? intval($_GET['variant_id']) : 0; ?>
<main class="khv-checkout-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
    <h1>Jetzt abschließen</h1>
    <div id="checkout-mount-point" data-variant-id="<?= esc_attr($variant_id); ?>"></div>
</main>

<?php get_footer(); ?>
