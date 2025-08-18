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
        $this->ensure_required_pages();

        // Replace deprecated emoji and admin bar functions with enqueue versions.
        $this->replace_deprecated_wp_functions();

        // Ensure webhook route is registered
        require_once PRODUKT_PLUGIN_PATH . 'includes/Webhook.php';
        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_shortcode('produkt_product', [$this, 'product_shortcode']);
        add_shortcode('produkt_shop_grid', [$this, 'render_product_grid']);
        add_shortcode('produkt_account', [$this, 'render_customer_account']);
        add_shortcode('produkt_confirmation', [$this, 'render_order_confirmation']);
        add_shortcode('produkt_category_layout', [$this, 'render_category_layout']);
        add_action('init', [$this, 'register_customer_role']);
        add_action('wp_enqueue_scripts', [$this->admin, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);

        add_rewrite_rule('^shop/produkt/([^/]+)/?$', 'index.php?produkt_slug=$matches[1]', 'top');
        add_rewrite_rule('^shop/([^/]+)/?$', 'index.php?pagename=shop&produkt_category_slug=$matches[1]', 'top');
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
        add_action('wp_ajax_get_variant_booked_days', [$this->ajax, 'ajax_get_variant_booked_days']);
        add_action('wp_ajax_nopriv_get_variant_booked_days', [$this->ajax, 'ajax_get_variant_booked_days']);
        add_action('wp_ajax_check_variant_availability', [$this->ajax, 'ajax_check_variant_availability']);
        add_action('wp_ajax_nopriv_check_variant_availability', [$this->ajax, 'ajax_check_variant_availability']);
        add_action('wp_ajax_get_extra_booked_days', [$this->ajax, 'ajax_get_extra_booked_days']);
        add_action('wp_ajax_nopriv_get_extra_booked_days', [$this->ajax, 'ajax_get_extra_booked_days']);
        add_action('wp_ajax_check_extra_availability', [$this->ajax, 'ajax_check_extra_availability']);
        add_action('wp_ajax_nopriv_check_extra_availability', [$this->ajax, 'ajax_check_extra_availability']);
        add_action('wp_ajax_notify_availability', [$this->ajax, 'ajax_notify_availability']);
        add_action('wp_ajax_nopriv_notify_availability', [$this->ajax, 'ajax_notify_availability']);

        add_action('wp_ajax_exit_intent_feedback', [$this->ajax, 'ajax_exit_intent_feedback']);
        add_action('wp_ajax_nopriv_exit_intent_feedback', [$this->ajax, 'ajax_exit_intent_feedback']);

        add_filter('admin_footer_text', [$this->admin, 'custom_admin_footer']);
        add_action('admin_head', [$this->admin, 'custom_admin_styles']);
        add_filter('display_post_states', [$this, 'mark_shop_page'], 10, 2);

        add_filter('show_admin_bar', [$this, 'hide_admin_bar_for_customers']);
        add_filter('wp_nav_menu_items', [$this, 'add_cart_icon_to_menu'], 10, 2);
        add_filter('render_block', [$this, 'maybe_inject_cart_icon_block'], 10, 2);
        add_action('wp_footer', [$this, 'render_cart_sidebar']);

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
        $needs_schema =
            !$this->db->categories_table_has_parent_column() ||
            !$this->db->customer_notes_table_exists() ||
            !$this->db->category_layouts_table_exists();
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
        add_rewrite_rule('^shop/([^/]+)/?$', 'index.php?pagename=shop&produkt_category_slug=$matches[1]', 'top');
        $this->create_shop_page();
        $this->create_customer_page();
        $this->create_checkout_page();
        $this->create_confirmation_page();
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
            PRODUKT_CONFIRM_PAGE_OPTION,
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

        $confirm_page_id = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
        if ($confirm_page_id) {
            wp_delete_post($confirm_page_id, true);
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
        if (get_query_var('produkt_category_slug')) {
            return '';
        }

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

    public function render_category_layout($atts) {
        global $wpdb;

        $atts = shortcode_atts([
            'id' => '',
        ], $atts);

        if (empty($atts['id'])) {
            return '';
        }

        $table = $wpdb->prefix . 'produkt_category_layouts';
        $layout = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shortcode = %s", $atts['id']));
        if (!$layout) {
            return '';
        }

        $items = json_decode($layout->categories, true);
        if (empty($items)) {
            return '';
        }

        $border_style = $layout->border_radius ? 'border-radius:20px;' : '';

        ob_start();
        ?>
        <div class="produkt-category-layout layout-<?php echo intval($layout->layout_type); ?>">
            <?php
            $count = 0;
            foreach ($items as $item) {
                $cat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}produkt_product_categories WHERE id = %d", intval($item['id'])));
                if (!$cat) {
                    continue;
                }
                $url = esc_url(site_url('/shop/' . $cat->slug));
                $style = 'background-color:' . esc_attr($item['color'] ?? '#fff') . ';' . $border_style;
                $img_class = 'layout-cat-img';
                if (intval($layout->layout_type) === 1 && $count === 4) {
                    $img_class .= ' layout-cat-img-last';
                }
                $img = !empty($item['image']) ? '<img src="' . esc_url($item['image']) . '" class="' . $img_class . '" alt="" />' : '';
                $classes = 'layout-card';
                $tag = in_array($layout->heading_tag ?? '', ['h1','h2','h3','h4','h5','h6'], true) ? $layout->heading_tag : 'h3';
                if (intval($layout->layout_type) === 1 && $count === 4) {
                    $classes .= ' span-2';
                }
                echo '<a href="' . $url . '" class="' . $classes . '" style="' . $style . '"><' . $tag . ' class="layout-cat-name">' . esc_html($cat->name) . '</' . $tag . '>' . $img . '</a>';
                $count++;
                if (intval($layout->layout_type) === 1 && $count >= 5) {
                    break;
                }
                if (intval($layout->layout_type) === 2 && $count >= 6) {
                    break;
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_customer_account() {
        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
        $message        = $this->login_error;
        $show_code_form = isset($_POST['verify_login_code']);
        $email_value    = '';
        $redirect_to    = isset($_REQUEST['redirect_to']) ? esc_url_raw($_REQUEST['redirect_to']) : '';

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
            } else {
                $message        = '<p style="color:red;">Email nicht gefunden.</p>';
                $show_code_form = false;
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
        if (!empty($_POST['shipping_price_id'])) {
            $shipping_price_id = sanitize_text_field($_POST['shipping_price_id']);
        } else {
            $shipping_price_id = $wpdb->get_var("SELECT stripe_price_id FROM {$wpdb->prefix}produkt_shipping_methods WHERE is_default = 1 LIMIT 1");
        }

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
                $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';
                if (empty($redirect)) {
                    $page_id  = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
                    $redirect = get_permalink($page_id);
                }
                wp_safe_redirect($redirect);
                exit;
            }
        }

        // Invalid code – store message for later display
        $this->login_error = '<p style="color:red;">Der Code ist ungültig oder abgelaufen.</p>';
    }


    public function render_order_confirmation() {
        require_once PRODUKT_PLUGIN_PATH . 'includes/account-helpers.php';
        $session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';
        if (!$session_id) {
            return '<p>Keine Bestellung gefunden.</p>';
        }

        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, c.name AS category_name,
                    COALESCE(v.name, o.produkt_name) AS variant_name,
                    COALESCE(NULLIF(GROUP_CONCAT(e.name SEPARATOR ', '), ''), o.extra_text) AS extra_names,
                    sm.name AS shipping_name
             FROM {$wpdb->prefix}produkt_orders o
             LEFT JOIN {$wpdb->prefix}produkt_categories c ON o.category_id = c.id
             LEFT JOIN {$wpdb->prefix}produkt_variants v ON o.variant_id = v.id
             LEFT JOIN {$wpdb->prefix}produkt_extras e ON FIND_IN_SET(e.id, o.extra_ids)
             LEFT JOIN {$wpdb->prefix}produkt_shipping_methods sm
                ON sm.stripe_price_id = COALESCE(o.shipping_price_id, c.shipping_price_id)
             WHERE o.stripe_session_id = %s
             GROUP BY o.id
             ORDER BY o.id DESC LIMIT 1",
            $session_id
        ));

        if (!$order) {
            return '<p>Bestellung nicht gefunden.</p>';
        }

        $variant_id = $order->variant_id ?? 0;
        $image_url  = pv_get_image_url_by_variant_or_category($variant_id, $order->category_id ?? 0);

        ob_start();
        ?>
        <h1>Bestellübersicht</h1>
        <p>Hallo <?php echo esc_html($order->customer_name); ?>, vielen Dank für deine Bestellung. Du erhältst von uns in Kürze eine Email mit allen Informationen zu deiner Bestellung.</p>
        <?php include PRODUKT_PLUGIN_PATH . 'includes/render-order.php'; ?>
        <p>Wir bedanken uns für Ihr Vertrauen. Bei Fragen rund um unseren Service oder Produkte, stehen wir dir gerne zur Verfügung. <a href="<?php echo esc_url(home_url('/')); ?>" style="text-decoration: underline;">Zurück zur Startseite</a></p>
        <?php
        return ob_get_clean();
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

    private function create_confirmation_page() {
        $page = get_page_by_path('bestellbestaetigung');
        if (!$page) {
            $page_data = [
                'post_title'   => 'Bestellbestätigung',
                'post_name'    => 'bestellbestaetigung',
                'post_content' => '[produkt_confirmation]',
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ];
            $page_id = wp_insert_post($page_data);
        } else {
            $page_id = $page->ID;
        }

        update_option(PRODUKT_CONFIRM_PAGE_OPTION, $page_id);
    }

    private function ensure_required_pages() {
        $shop_id = get_option(PRODUKT_SHOP_PAGE_OPTION);
        if (!$shop_id || get_post_status($shop_id) === false) {
            $this->create_shop_page();
        }

        $cust_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        if (!$cust_id || get_post_status($cust_id) === false) {
            $this->create_customer_page();
        }

        $checkout_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
        if (!$checkout_id || get_post_status($checkout_id) === false) {
            $this->create_checkout_page();
        }

        $confirm_id = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
        if (!$confirm_id || get_post_status($confirm_id) === false) {
            $this->create_confirmation_page();
        }
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
        $confirm_page_id = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
        if ($post->ID == $confirm_page_id) {
            $states[] = __('Bestellbestätigung', 'h2-concepts');
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

    /**
     * Return the URL of the customer account page.
     */
    public static function get_customer_page_url() {
        $page_id = get_option(PRODUKT_CUSTOMER_PAGE_OPTION);
        if ($page_id) {
            return get_permalink($page_id);
        }
        return home_url('/kundenkonto');
    }

    /**
     * Return the URL of the order confirmation page.
     */
    public static function get_confirmation_page_url() {
        $page_id = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
        if ($page_id) {
            return get_permalink($page_id);
        }
        return home_url('/bestellbestaetigung');
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
     * Append a cart icon to the main navigation menu.
     */
    public function add_cart_icon_to_menu($items, $args) {
        $inject_menus = (array) get_option('produkt_menu_locations', []);

        $current_menu_id = 0;
        if (!empty($args->menu)) {
            if (is_object($args->menu)) {
                $current_menu_id = (int) $args->menu->term_id;
            } elseif (is_numeric($args->menu)) {
                $current_menu_id = (int) $args->menu;
            } else {
                $menu_obj = wp_get_nav_menu_object($args->menu);
                if ($menu_obj) {
                    $current_menu_id = (int) $menu_obj->term_id;
                }
            }
        } elseif (!empty($args->theme_location)) {
            $locations = get_nav_menu_locations();
            if (isset($locations[$args->theme_location])) {
                $current_menu_id = (int) $locations[$args->theme_location];
            }
        }

        if ($current_menu_id && in_array($current_menu_id, $inject_menus, true)) {
            $items .= '<li class="menu-item plugin-cart-icon">'
                . '<a href="#" class="h2-cart-link" onclick="openCartSidebar(); return false;" aria-label="' . esc_attr__('Warenkorb', 'produkt') . '">'
                . '<span class="cart-icon"><svg viewBox="0 0 61 46.8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2.2.2c-1.1,0-2,.9-2,2s.2,1,.6,1.4.9.6,1.4.6h3.9c2.1,0,4,1.4,4.7,3.4l2.2,6.7h0c0,0,5.4,16.8,5.4,16.8,1.1,3.4,4.2,5.7,7.8,5.7h23.5c3.6,0,6.6-2.5,7.4-6l3.6-16.5c.7-3.5-2-6.8-5.5-6.8H18c-1,0-2,.3-2.8.8l-.6-1.9C13.4,2.7,9.9.2,6.1.2h-3.9ZM18,11.5h37.1c1.1,0,1.8.9,1.6,2l-3.5,16.5c-.4,1.7-1.8,2.8-3.5,2.8h-23.5c-1.8,0-3.4-1.2-4-2.9l-5.4-16.7c-.3-.9.3-1.7,1.2-1.7h0ZM27,39.3c-1.9,0-3.6,1.6-3.6,3.6s1.6,3.6,3.6,3.6,3.6-1.6,3.6-3.6-1.6-3.6-3.6-3.6ZM46.4,39.3c-1.9,0-3.6,1.6-3.6,3.6s1.6,3.6,3.6,3.6,3.6-1.6,3.6-3.6-1.6-3.6-3.6-3.6Z"/></svg><span class="h2-cart-badge" data-h2-cart-count="0">0</span></span>'
                . '</a></li>';
        }
        return $items;
    }

    /**
     * Inject cart icon into block-based navigation menus.
     */
    public function maybe_inject_cart_icon_block($content, $block) {
        if (($block['blockName'] ?? '') !== 'core/navigation') {
            return $content;
        }
        $inject_menus = (array) get_option('produkt_menu_locations', []);
        $menu_id      = isset($block['attrs']['ref']) ? (int) $block['attrs']['ref'] : 0;
        $inject_all   = (bool) get_option('produkt_inject_block_nav_all', false);

        if ((!$menu_id && !$inject_all) || strpos($content, 'plugin-cart-icon') !== false) {
            return $content;
        }

        if ($menu_id && !in_array($menu_id, $inject_menus, true)) {
            return $content;
        }

        $icon = '<li class="wp-block-navigation-item plugin-cart-icon">'
            . '<a class="wp-block-navigation-item__content h2-cart-link" href="#" onclick="openCartSidebar();return false;" aria-label="' . esc_attr__('Warenkorb', 'produkt') . '">'
            . '<span class="cart-icon"><svg viewBox="0 0 61 46.8" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M2.2.2c-1.1,0-2,.9-2,2s.2,1,.6,1.4.9.6,1.4.6h3.9c2.1,0,4,1.4,4.7,3.4l2.2,6.7h0c0,0,5.4,16.8,5.4,16.8,1.1,3.4,4.2,5.7,7.8,5.7h23.5c3.6,0,6.6-2.5,7.4-6l3.6-16.5c.7-3.5-2-6.8-5.5-6.8H18c-1,0-2,.3-2.8.8l-.6-1.9C13.4,2.7,9.9.2,6.1.2h-3.9ZM18,11.5h37.1c1.1,0,1.8.9,1.6,2l-3.5,16.5c-.4,1.7-1.8,2.8-3.5,2.8h-23.5c-1.8,0-3.4-1.2-4-2.9l-5.4-16.7c-.3-.9.3-1.7,1.2-1.7h0ZM27,39.3c-1.9,0-3.6,1.6-3.6,3.6s1.6,3.6,3.6,3.6,3.6-1.6,3.6-3.6-1.6-3.6-3.6-3.6ZM46.4,39.3c-1.9,0-3.6,1.6-3.6,3.6s1.6,3.6,3.6,3.6,3.6-1.6,3.6-3.6-1.6-3.6-3.6-3.6Z"/></svg><span class="h2-cart-badge" data-h2-cart-count="0">0</span></span>'
            . '</a></li>';

        return preg_replace('#</ul>\s*</nav>#', $icon . '</ul></nav>', $content, 1) ?: $content;
    }

    /**
     * Output the sliding cart sidebar markup in the footer so it is available on all pages.
     */
    public function render_cart_sidebar() {
        include PRODUKT_PLUGIN_PATH . 'templates/cart-sidebar.php';
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

    $checkout_page_id = get_option(PRODUKT_CHECKOUT_PAGE_OPTION);
    if ($checkout_page_id && is_page($checkout_page_id)) {
        return PRODUKT_PLUGIN_PATH . 'templates/checkout-page.php';
    }

    $confirm_page_id = get_option(PRODUKT_CONFIRM_PAGE_OPTION);
    if ($confirm_page_id && is_page($confirm_page_id)) {
        return PRODUKT_PLUGIN_PATH . 'templates/confirmation-page.php';
    }

    return $template;
});

add_filter('the_content', function ($content) {
    if (get_query_var('produkt_category_slug') && is_main_query() && in_the_loop()) {
        ob_start();
        include PRODUKT_PLUGIN_PATH . 'templates/product-archive.php';
        return ob_get_clean();
    }
    return $content;
});

add_action('produkt_async_handle_checkout_completed', function ($json) {
    $session = json_decode($json);
    if (!$session || !isset($session->customer)) {
        return;
    }
    \ProduktVerleih\StripeService::process_checkout_session($session);
});

add_action('admin_init', function () {
    if (current_user_can('kunde') && !wp_doing_ajax()) {
        wp_redirect(home_url('/kundenkonto'));
        exit;
    }
});
