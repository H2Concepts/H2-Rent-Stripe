<?php
namespace ProduktVerleih;

class Plugin {
    private $db;
    private $ajax;
    private $admin;
    /**
     * Stores an error message when login code verification fails.
     * Displayed on the account page after template_redirect processing.
     * @var string
     */
    private $login_error = '';

    public function __construct() {
        $this->db = new Database();
        $this->ajax = new Ajax();
        $this->admin = new Admin();

        add_action('init', [$this, 'init']);
        // Run database update check as early as possible unless plugin is
        // currently being activated. During activation the tables don't exist
        // yet and are created later in the activation routine, so running the
        // update here would cause errors.
        if (!defined('PRODUKT_PLUGIN_ACTIVATING')) {
            $this->check_for_updates();
        }
    }

    public function init() {
        // Replace deprecated emoji and admin bar functions with enqueue versions.
        $this->replace_deprecated_wp_functions();
        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_shortcode('produkt_product', [$this, 'product_shortcode']);
        add_shortcode('produkt_shop_grid', [$this, 'render_product_grid']);
        add_shortcode('produkt_account', [$this, 'render_customer_account']);
        add_action('init', [$this, 'register_customer_role']);
        add_action('wp_enqueue_scripts', [$this->admin, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);

        add_rewrite_rule('^shop/produkt/([^/]+)/?$', 'index.php?produkt_slug=$matches[1]', 'top');
        add_rewrite_rule('^shop/([^/]+)/?$', 'index.php?produkt_category_slug=$matches[1]', 'top');
        add_filter('query_vars', function ($vars) {
            $vars[] = 'produkt_slug';
            $vars[] = 'produkt_category_slug';
            return $vars;
        });


        add_action('wp_ajax_get_product_price', [$this->ajax, 'ajax_get_product_price']);
        add_action('wp_ajax_nopriv_get_product_price', [$this->ajax, 'ajax_get_product_price']);
        add_action('wp_ajax_get_variant_images', [$this->ajax, 'ajax_get_variant_images']);
        add_action('wp_ajax_nopriv_get_variant_images', [$this->ajax, 'ajax_get_variant_images']);
        add_action('wp_ajax_get_extra_image', [$this->ajax, 'ajax_get_extra_image']);
        add_action('wp_ajax_nopriv_get_extra_image', [$this->ajax, 'ajax_get_extra_image']);
        add_action('wp_ajax_track_interaction', [$this->ajax, 'ajax_track_interaction']);
        add_action('wp_ajax_nopriv_track_interaction', [$this->ajax, 'ajax_track_interaction']);
        add_action('wp_ajax_get_variant_options', [$this->ajax, 'ajax_get_variant_options']);
        add_action('wp_ajax_nopriv_get_variant_options', [$this->ajax, 'ajax_get_variant_options']);
        add_action('wp_ajax_notify_availability', [$this->ajax, 'ajax_notify_availability']);
        add_action('wp_ajax_nopriv_notify_availability', [$this->ajax, 'ajax_notify_availability']);

        add_action('wp_ajax_exit_intent_feedback', [$this->ajax, 'ajax_exit_intent_feedback']);
        add_action('wp_ajax_nopriv_exit_intent_feedback', [$this->ajax, 'ajax_exit_intent_feedback']);

        add_filter('admin_footer_text', [$this->admin, 'custom_admin_footer']);
        add_action('admin_head', [$this->admin, 'custom_admin_styles']);
        add_filter('display_post_states', [$this, 'mark_shop_page'], 10, 2);

        add_filter('show_admin_bar', [$this, 'hide_admin_bar_for_customers']);

        // Handle "Jetzt mieten" form submissions before headers are sent
        add_action('template_redirect', [$this, 'handle_rent_request']);

        // Process login code submissions as early as possible to avoid header issues
        add_action('template_redirect', [$this, 'maybe_handle_login_code'], 1);

        // Ensure Astra page wrappers for plugin templates and login page
        add_filter('body_class', function ($classes) {
            if (get_query_var('produkt_category_slug') || get_query_var('produkt_slug')) {
                $classes[] = 'page';
                $classes[] = 'type-page';
                $classes[] = 'ast-page-builder-template';
                $classes[] = 'ast-no-sidebar';
            }

            $cust_page = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
            if ($cust_page && is_page($cust_page) && !is_user_logged_in()) {
                $classes[] = 'produkt-login-page';
                $classes[] = 'page';
                $classes[] = 'type-page';
                $classes[] = 'ast-page-builder-template';
                $classes[] = 'ast-no-sidebar';
            }

            $checkout_page = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
            if ($checkout_page && is_page($checkout_page)) {
                $classes[] = 'checkout-embedded';
            }

            return $classes;
        });

        add_filter('astra_get_content_layout', function ($layout) {
            if (get_query_var('produkt_category_slug') || get_query_var('produkt_slug')) {
                return 'default';
            }
            return $layout;
        });

    }

    public function check_for_updates() {
        $current_version = get_option('produkt_version', '1.0.0');
        $needs_schema = !$this->db->categories_table_has_parent_column();
        if (version_compare($current_version, PRODUKT_VERSION, '<') || $needs_schema) {
            $this->db->update_database();
            update_option('produkt_version', PRODUKT_VERSION);
        }
    }

    public function activate() {
        $this->db->create_tables();
        // Ensure any new columns are added when activating after an update
        $this->db->update_database();
        $load_sample = defined('PRODUKT_LOAD_DEFAULT_DATA') ? PRODUKT_LOAD_DEFAULT_DATA : false;
        $load_sample = apply_filters('produkt_load_default_data', $load_sample);
        if ($load_sample) {
            $this->db->insert_default_data();
        }
        update_option('produkt_version', PRODUKT_VERSION);
        add_rewrite_rule('^shop/produkt/([^/]+)/?$', 'index.php?produkt_slug=$matches[1]', 'top');
        add_rewrite_rule('^shop/([^/]+)/?$', 'index.php?produkt_category_slug=$matches[1]', 'top');
        $this->create_shop_page();
        $this->create_customer_page();
        $this->create_checkout_page();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public static function activate_plugin() {
        // Indicate that the plugin is currently being activated so the
        // constructor skips the update routine which expects the tables to
        // already exist.
        if (!defined('PRODUKT_PLUGIN_ACTIVATING')) {
            define('PRODUKT_PLUGIN_ACTIVATING', true);
        }
        $plugin = new self();
        $plugin->activate();
    }

    public static function deactivate_plugin() {
        $plugin = new self();
        $plugin->deactivate();
    }

    /**
     * Remove all plugin data and drop tables.
     */
    public function uninstall() {
        $this->db->drop_tables();

        $options = array(
            'produkt_version',
            'produkt_popup_settings',
            'produkt_stripe_publishable_key',
            'produkt_stripe_secret_key',
            'produkt_stripe_pmc_id',
            'produkt_stripe_webhook_secret',
            'produkt_tos_url',
            'produkt_success_url',
            'produkt_cancel_url',
            'produkt_ct_shipping',
            'produkt_ct_submit',
            'produkt_ct_after_submit',
            'produkt_ct_agb',
            'produkt_ui_settings',
            PRODUKT_SHOP_PAGE_OPTION,
            PRODUKT_CUSTOMER_PAGE_OPTION,
            PRODUKT_CHECKOUT_PAGE_OPTION,
        );

        foreach ($options as $opt) {
            delete_option($opt);
        }

        $page_id = get_option(PRODUKT_SHOP_PAGE_OPTION);
        if ($page_id) {
            wp_delete_post($page_id, true);
        }

        $cust_page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        if ($cust_page_id) {
            wp_delete_post($cust_page_id, true);
        }

        $checkout_page_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
        if ($checkout_page_id) {
            wp_delete_post($checkout_page_id, true);
        }
    }

    /**
     * Convenience wrapper for calling uninstall from hooks or actions.
     */
    public static function uninstall_plugin() {
        $plugin = new self();
        $plugin->uninstall();
    }

    public function product_shortcode($atts) {
        global $wpdb;

        $atts = shortcode_atts([
            'category' => '',
            'title' => '',
            'description' => ''
        ], $atts);

        $category = null;
        if (!empty($atts['category'])) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s OR name = %s",
                $atts['category'],
                $atts['category']
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return '<p>Kein aktives Produkt gefunden.</p>';
        }

        $page_title = !empty($atts['title']) ? $atts['title'] : $category->page_title;
        $page_description = !empty($atts['description']) ? $atts['description'] : $category->page_description;

        ob_start();
        include PRODUKT_PLUGIN_PATH . 'templates/product-page.php';
        return ob_get_clean();
    }

    public function render_product_grid() {
        global $wpdb;

        $slug = isset($_GET['kategorie']) ? sanitize_title($_GET['kategorie']) : '';

        $categories = Database::get_all_categories(true);

        if (!empty($slug)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}produkt_product_categories WHERE slug = %s",
                $slug
            ));

            if ($category) {
                $cat_ids = array_merge([$category->id], Database::get_descendant_category_ids($category->id));
                $placeholders = implode(',', array_fill(0, count($cat_ids), '%d'));
                $product_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT produkt_id FROM {$wpdb->prefix}produkt_product_to_category WHERE category_id IN ($placeholders)",
                    $cat_ids
                ));

                $categories = array_filter($categories, function ($prod) use ($product_ids) {
                    return in_array($prod->id, $product_ids);
                });
            } else {
                $categories = []; // ungültiger Slug
            }
        }

        ob_start();
        include PRODUKT_PLUGIN_PATH . 'templates/product-archive.php';
        return ob_get_clean();
    }

    public function render_customer_account() {
        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
        $message        = $this->login_error;
        $show_code_form = isset($_POST['verify_login_code']);
        $email_value    = '';

        if (
            isset($_POST['verify_login_code_nonce'], $_POST['verify_login_code']) &&
            wp_verify_nonce($_POST['verify_login_code_nonce'], 'verify_login_code_action') &&
            !empty($_POST['email']) &&
            !empty($_POST['code'])
        ) {
            $email       = sanitize_email($_POST['email']);
            $input_code  = trim($_POST['code']);
            $email_value = $email;
            $user        = get_user_by('email', $email);

            if ($user) {
                $data = get_user_meta($user->ID, 'produkt_login_code', true);
                if (
                    !(
                        isset($data['code'], $data['expires']) &&
                        $data['code'] == $input_code &&
                        time() <= $data['expires']
                    )
                ) {
                    $message        = '<p style="color:red;">Der Code ist ungültig oder abgelaufen.</p>';
                    $show_code_form = true;
                }
            } else {
                $message        = '<p style="color:red;">Benutzer wurde nicht gefunden.</p>';
                $show_code_form = true;
            }

        } elseif (
            isset($_POST['request_login_code_nonce'], $_POST['request_login_code']) &&
            wp_verify_nonce($_POST['request_login_code_nonce'], 'request_login_code_action') &&
            !empty($_POST['email'])
        ) {
            $email       = sanitize_email($_POST['email']);
            $email_value = $email;
            $user        = get_user_by('email', $email);

            if (!$user) {
                $user_id = wp_create_user($email, wp_generate_password(), $email);
                if (!is_wp_error($user_id)) {
                    wp_update_user(['ID' => $user_id, 'role' => 'kunde']);
                    $user = get_user_by('ID', $user_id);
                }
            }

            if ($user) {
                $code    = random_int(100000, 999999);
                $expires = time() + 15 * MINUTE_IN_SECONDS;
                update_user_meta(
                    $user->ID,
                    'produkt_login_code',
                    ['code' => $code, 'expires' => $expires]
                );

                $headers    = ['Content-Type: text/plain; charset=UTF-8'];
                $from_name  = get_bloginfo('name');
                $from_email = get_option('admin_email');
                $headers[]  = 'From: ' . $from_name . ' <' . $from_email . '>';

                wp_mail(
                    $email,
                    'Ihr Login-Code',
                    "Ihr Login-Code lautet: $code\nGültig für 15 Minuten.",
                    $headers
                );
                $message        = '<p>Login-Code gesendet.</p>';
                $show_code_form = true;
            }

        }

        ob_start();
        $subscriptions  = [];
        $current_user_id = get_current_user_id();
        if ($current_user_id) {
            $customer_id = Database::get_stripe_customer_id_for_user($current_user_id);

            if ($customer_id && isset($_POST['cancel_subscription'])) {
                $sub_id = sanitize_text_field($_POST['cancel_subscription']);
                $subs   = StripeService::get_active_subscriptions_for_customer($customer_id);
                $orders = Database::get_orders_for_user($current_user_id);

                if (!is_wp_error($subs)) {
                    foreach ($subs as $sub) {
                        if ($sub['subscription_id'] === $sub_id) {
                            $matching_order = null;

                            // passende Bestellung zur Subscription suchen
                            foreach ($orders as $order) {
                                if ($order->subscription_id === $sub_id) {
                                    $matching_order = $order;
                                    break;
                                }
                            }

                            $laufzeit_in_monaten = pv_get_minimum_duration_months($matching_order);

                            $start_ts      = strtotime($sub['start_date']);
                            $cancelable_ts = strtotime("+{$laufzeit_in_monaten} months", $start_ts);
                            $cancelable    = time() > $cancelable_ts;
                            $period_end_ts = strtotime($sub['current_period_end']);
                            $period_end_date = date_i18n('d.m.Y', $period_end_ts);

                            if ($cancelable && empty($sub['cancel_at_period_end'])) {
                                $res = StripeService::cancel_subscription_at_period_end($sub_id);
                                if (is_wp_error($res)) {
                                    $message = '<p style="color:red;">' . esc_html($res->get_error_message()) . '</p>';
                                } else {
                                    $message = '<p>' . esc_html__('Kündigung vorgemerkt. Laufzeit endet am ', 'h2-concepts') . esc_html($period_end_date) . '</p>';
                                }
                            } else {
                                $message = '<p style="color:red;">' . esc_html__('Dieses Abo kann noch nicht gekündigt werden.', 'h2-concepts') . '</p>';
                            }

                            break;
                        }
                    }
                }
            }

            if ($customer_id) {
                $subs = StripeService::get_active_subscriptions_for_customer($customer_id);
                if (!is_wp_error($subs)) {
                    $subscriptions = $subs;
                } else {
                    $message = '<p style="color:red;">' . esc_html($subs->get_error_message()) . '</p>';
                }
            }
        }

        include PRODUKT_PLUGIN_PATH . 'templates/account-page.php';
        return ob_get_clean();
    }


    /**
     * Handle the product form submission and redirect to the checkout page
     * before any output is sent to the browser.
     */
    public function handle_rent_request() {
        if (empty($_POST['jetzt_mieten'])) {
            return;
        }

        $price_id = sanitize_text_field($_POST['price_id'] ?? '');
        global $wpdb;
        $shipping_price_id = $wpdb->get_var("SELECT stripe_price_id FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");

        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_die($init->get_error_message());
        }

        try {
            $tos_url = get_option('produkt_tos_url', home_url('/agb'));
            $session_args = [
                'mode' => 'subscription',
                'payment_method_types' => ['card', 'paypal'],
                'allow_promotion_codes' => true,
                'line_items' => [[
                    'price' => $price_id,
                    'quantity' => 1,
                ]],
                'billing_address_collection' => 'required',
                'shipping_address_collection' => ['allowed_countries' => ['DE']],
                'phone_number_collection' => [
                    'enabled' => true,
                ],
                'success_url' => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', get_option('produkt_success_url', home_url('/danke'))),
                'cancel_url'  => get_option('produkt_cancel_url', home_url('/abbrechen')),
                'consent_collection' => [
                    'terms_of_service' => 'required',
                ],
                'custom_text' => [
                    'terms_of_service_acceptance' => [
                        'message' => 'Ich akzeptiere die [Allgemeinen Geschäftsbedingungen (AGB)](' . esc_url($tos_url) . ')',
                    ],
                ],
            ];

            if ($shipping_price_id) {
                $session_args['line_items'][] = [
                    'price' => $shipping_price_id,
                    'quantity' => 1,
                ];
            }

            $session = \Stripe\Checkout\Session::create($session_args);
            // wp_safe_redirect() does not allow external URLs like Stripe's
            // checkout page, so use wp_redirect instead.
            wp_redirect($session->url);
            exit;
        } catch (\Exception $e) {
            wp_die($e->getMessage());
        }
    }

    /**
     * Verify a login code before any output is sent and log the user in.
     * Redirects to the customer account page on success.
     */
    public function maybe_handle_login_code() {
        if (
            !isset($_POST['verify_login_code']) ||
            empty($_POST['email']) ||
            empty($_POST['code'])
        ) {
            return;
        }

        $email      = sanitize_email($_POST['email']);
        $input_code = trim($_POST['code']);
        $user       = get_user_by('email', $email);

        if ($user) {
            $data = get_user_meta($user->ID, 'produkt_login_code', true);
            if (
                isset($data['code'], $data['expires']) &&
                $data['code'] == $input_code &&
                time() <= $data['expires']
            ) {
                delete_user_meta($user->ID, 'produkt_login_code');

                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, true);
                $page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
                wp_safe_redirect(get_permalink($page_id));
                exit;
            }
        }

        // Invalid code – store message for later display
        $this->login_error = '<p style="color:red;">Der Code ist ungültig oder abgelaufen.</p>';
    }


    public function maybe_display_product_page() {
        $slug = sanitize_title(get_query_var('produkt_slug'));
        if (empty($slug)) {
            return;
        }

        global $wpdb;
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
        $category = null;
        foreach ($categories as $cat) {
            if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
                $category = $cat;
                break;
            }
        }

        if (!$category) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        add_filter('pre_get_document_title', function () use ($category) {
            return $category->page_title ?: $category->product_title;
        });

        get_header();
        include PRODUKT_PLUGIN_PATH . 'templates/product-page.php';
        get_footer();
        exit;
    }

    private function create_shop_page() {
        $page = get_page_by_path('shop');
        if (!$page) {
            $page_data = [
                'post_title'   => 'Shop',
                'post_name'    => 'shop',
                'post_content' => '[produkt_shop_grid]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ];
            $page_id = wp_insert_post($page_data);
        } else {
            $page_id = $page->ID;
        }

        update_option(PRODUKT_SHOP_PAGE_OPTION, $page_id);
    }

    private function create_customer_page() {
        $page = get_page_by_path('kundenkonto');
        if (!$page) {
            $page_data = [
                'post_title'   => 'Kundenkonto',
                'post_name'    => 'kundenkonto',
                'post_content' => '[produkt_account]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ];
            $page_id = wp_insert_post($page_data);
        } else {
            $page_id = $page->ID;
        }

        update_option(PRODUKT_CUSTOMER_PAGE_OPTION, $page_id);
    }

    private function create_checkout_page() {
        $page = get_page_by_path('checkout');
        if (!$page) {
            $page_data = [
                'post_title'   => 'Checkout',
                'post_name'    => 'checkout',
                'post_content' => '[stripe_elements_form]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ];
            $page_id = wp_insert_post($page_data);
        } else {
            $page_id = $page->ID;
        }

        update_option(PRODUKT_CHECKOUT_PAGE_OPTION, $page_id);
    }

    public function mark_shop_page($states, $post) {
        $shop_page_id = get_option(PRODUKT_SHOP_PAGE_OPTION);
        if ($post->ID == $shop_page_id) {
            $states[] = __('Shop-Seite', 'h2-concepts');
        }
        $customer_page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        if ($post->ID == $customer_page_id) {
            $states[] = __('Kundenkonto-Seite', 'h2-concepts');
        }
        $checkout_page_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
        if ($post->ID == $checkout_page_id) {
            $states[] = __('Checkout-Seite', 'h2-concepts');
        }
        return $states;
    }

    /**
     * Find the page containing the checkout shortcode and return its URL.
     * Returns null if no such page is found.
     */
    public static function get_checkout_page_url() {
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ]);

        foreach ($pages as $page) {
            if (has_shortcode($page->post_content, 'stripe_elements_form')) {
                return get_permalink($page->ID);
            }
        }

        return null;
    }

    public function register_customer_role() {
        add_role('kunde', 'Kunde', [
            'read' => true,
        ]);
    }

    public function hide_admin_bar_for_customers($show) {
        if (current_user_can('kunde')) {
            return false;
        }
        return $show;
    }

    /**
     * Replace deprecated WordPress functions with modern equivalents.
     * Removes the old actions that trigger deprecation warnings.
     */
    private function replace_deprecated_wp_functions() {
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'wp_admin_bar_header');

        add_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
        add_action('admin_enqueue_scripts', 'wp_enqueue_admin_bar_header_styles');
    }
}

add_filter('template_include', function ($template) {
    if (get_query_var('produkt_slug')) {
        return PRODUKT_PLUGIN_PATH . 'templates/product-page.php';
    }

    if (get_query_var('produkt_category_slug')) {
        return PRODUKT_PLUGIN_PATH . 'templates/product-archive.php';
    }

    $checkout_page_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
    if ($checkout_page_id && is_page($checkout_page_id)) {
        return PRODUKT_PLUGIN_PATH . 'templates/checkout-page.php';
    }

    return $template;
});

add_action('admin_init', function () {
    if (current_user_can('kunde') && !wp_doing_ajax()) {
        wp_redirect(home_url('/kundenkonto'));
        exit;
    }
});
