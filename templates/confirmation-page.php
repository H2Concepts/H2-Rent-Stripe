<?php
/* Template Name: Bestellbestätigung */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<div class="produkt-container shop-overview-container">
    <?php echo do_shortcode('[produkt_confirmation]'); ?>
</div>
<?php get_footer(); ?>
