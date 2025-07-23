<?php
require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
if (!defined('ABSPATH')) { exit; }

get_header();

if (!produkt_is_customer_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

echo '<div class="produkt-account-wrapper produkt-container shop-overview-container">';
include plugin_dir_path(__FILE__) . '../views/account/dashboard.php';
echo '</div>';

get_footer();
