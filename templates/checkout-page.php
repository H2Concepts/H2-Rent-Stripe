<?php
/**
 * Template Name: Produkt-Checkout-Seite
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="wrap">
  <h1>Jetzt bezahlen</h1>
  <div id="checkout-wrapper">
    <form id="checkout-form">
      <div id="checkout-element"></div>
      <button type="submit" id="submit-button">Jetzt bezahlen</button>
    </form>
    <div id="checkout-error" style="color:red; display:none;"></div>
  </div>
</div>

<?php get_footer(); ?>
