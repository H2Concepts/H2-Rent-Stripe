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

<?php $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0; ?>
<main class="khv-checkout-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
    <h1>Jetzt abschließen</h1>
    <div id="checkout-mount-point" data-product-id="<?= esc_attr($product_id); ?>"></div>
</main>

<?php get_footer(); ?>
