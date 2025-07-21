<?php
/* Template Name: Checkout-Seite */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<div class="produkt-container shop-overview-container">
    <h1>Checkout</h1>
    <div id="stripe-container" style="min-width: 100%; margin: 0 auto;">
        <?php echo do_shortcode('[stripe_elements_form]'); ?>
    </div>
</div>

<div id="produkt-exit-popup" class="produkt-exit-popup" style="display:none;">
    <div class="produkt-exit-popup-content">
        <button type="button" class="produkt-exit-popup-close">&times;</button>
        <h3 id="produkt-exit-title"></h3>
        <div id="produkt-exit-message"></div>
        <div id="produkt-exit-email-wrapper" style="display:none;">
            <input type="email" id="produkt-exit-email" placeholder="E-Mail-Adresse">
        </div>
        <div id="produkt-exit-select-wrapper" style="display:none;">
            <select id="produkt-exit-select"></select>
        </div>
        <button id="produkt-exit-send" style="display:none;">Senden</button>
    </div>
</div>
<?php get_footer(); ?>
