<?php
/* Template Name: Checkout-Seite */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<div class="produkt-container shop-overview-container">
    <h1>Checkout</h1>
    <?php echo do_shortcode('[stripe_elements_form]'); ?>
</div>
<?php get_footer(); ?>
