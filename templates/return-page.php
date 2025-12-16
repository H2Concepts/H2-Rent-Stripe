<?php
/* Template Name: Stripe Rückgabeseite */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<section id="success" class="hidden">
    <p><?php printf(esc_html__('Wir schätzen Ihr Vertrauen! Eine Bestätigung wurde an %s gesendet.', 'h2-rental-pro'), '<span id="customer-email"></span>'); ?></p>
    <p><?php printf(esc_html__('Bei Fragen schreiben Sie an %s.', 'h2-rental-pro'), '<a href="mailto:orders@example.com">orders@example.com</a>'); ?></p>
</section>
<?php get_footer(); ?>

