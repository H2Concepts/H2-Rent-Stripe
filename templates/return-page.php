<?php
/* Template Name: Stripe Rückgabeseite */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<section id="success" class="hidden">
    <p>Wir schätzen Ihr Vertrauen! Eine Bestätigung wurde an <span id="customer-email"></span> gesendet.</p>
    <p>Bei Fragen schreiben Sie an <a href="mailto:orders@example.com">orders@example.com</a>.</p>
</section>
<?php get_footer(); ?>

