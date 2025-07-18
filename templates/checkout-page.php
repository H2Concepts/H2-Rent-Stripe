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

<main class="khv-checkout-container" style="max-width: 600px; margin: 0 auto; padding: 2rem;">
    <h1>Jetzt abschließen</h1>
    <div id="checkout-mount-point" data-session-id=""></div>
</main>

<script src="https://js.stripe.com/embedded/v1/"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mountPoint = document.getElementById("checkout-mount-point");
    const sessionId = mountPoint.dataset.sessionId;

    if (!sessionId) {
        console.error("Keine Session-ID vorhanden.");
        return;
    }

    const stripe = Stripe('<?php echo esc_js(\ProduktVerleih\StripeService::get_publishable_key()); ?>');
    stripe.mountEmbeddedCheckout({
        clientSecret: sessionId,
        element: '#checkout-mount-point'
    });
});
</script>

<?php get_footer(); ?>
