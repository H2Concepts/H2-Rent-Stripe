<?php
/* Template Name: Checkout-Seite */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<div class="produkt-container shop-overview-container">
    <h1>Checkout</h1>
    <div id="stripe-container" style="max-width: 700px; margin: 0 auto;">
        <?php echo do_shortcode('[stripe_elements_form]'); ?>
    </div>
</div>
<?php get_footer(); ?>
