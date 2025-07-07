<?php
namespace ProduktVerleih;

class Plugin {
    private $db;
    private $ajax;
    private $admin;

    public function __construct() {
        $this->db = new Database();
        $this->ajax = new Ajax();
        $this->admin = new Admin();

        add_action('init', [$this, 'init']);
        // Run database update check as early as possible
        $this->check_for_updates();
        add_action('wp_head', [$this, 'add_meta_tags']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        add_action('wp_head', [$this, 'add_open_graph_tags']);
    }

    public function init() {
        $this->register_product_post_type();
        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_shortcode('produkt_product', [$this, 'product_shortcode']);
        add_action('wp_enqueue_scripts', [$this->admin, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_assets']);

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

        // Handle "Jetzt mieten" form submissions before headers are sent
        add_action('template_redirect', [$this, 'handle_rent_request']);
    }

    public function check_for_updates() {
        $current_version = get_option('produkt_version', '1.0.0');
        if (version_compare($current_version, PRODUKT_VERSION, '<')) {
            $this->db->update_database();
            update_option('produkt_version', PRODUKT_VERSION);
        }
    }

    public function register_product_post_type() {
        $labels = [
            'name' => 'produkte',
            'singular_name' => 'produkt',
        ];
        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'shop'],
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
        register_post_type('produkt', $args);
    }

    public static function create_sample_product() {
        if (!post_type_exists('produkt')) {
            return;
        }

        $existing = get_posts([
            'post_type' => 'produkt',
            'posts_per_page' => 1,
            'post_status' => 'publish',
        ]);

        if (empty($existing)) {
            $post_id = wp_insert_post([
                'post_type' => 'produkt',
                'post_title' => 'Beispielprodukt',
                'post_status' => 'publish',
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, 'is_plugin_starter', true);
            }
        }
    }

    public function activate() {
        $this->db->create_tables();
        $load_sample = defined('PRODUKT_LOAD_DEFAULT_DATA') ? PRODUKT_LOAD_DEFAULT_DATA : false;
        $load_sample = apply_filters('produkt_load_default_data', $load_sample);
        if ($load_sample) {
            $this->db->insert_default_data();
        }
        update_option('produkt_version', PRODUKT_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public static function activate_plugin() {
        $plugin = new self();
        $plugin->register_product_post_type();
        $plugin->activate();
        self::create_sample_product();
        flush_rewrite_rules();
    }

    public static function deactivate_plugin() {
        $plugin = new self();
        $plugin->deactivate();
        flush_rewrite_rules();
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
            return '<p>Keine aktive Produktkategorie gefunden.</p>';
        }

        $page_title = !empty($atts['title']) ? $atts['title'] : $category->page_title;
        $page_description = !empty($atts['description']) ? $atts['description'] : $category->page_description;

        ob_start();
        include PRODUKT_PLUGIN_PATH . 'templates/product-page.php';
        return ob_get_clean();
    }

    public function add_meta_tags() {
        global $post, $wpdb;

        if (!is_singular() || (!has_shortcode($post->post_content, 'produkt_product') && !is_singular('produkt'))) {
            return;
        }

        $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return;
        }

        $meta_title = !empty($category->meta_title) ? $category->meta_title : $category->page_title;
        if (!empty($meta_title)) {
            echo '<meta name="title" content="' . esc_attr($meta_title) . '">' . "\n";
        }

        $meta_description = !empty($category->meta_description) ? $category->meta_description : $category->page_description;
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }

        echo '<meta name="keywords" content="Produkt mieten, Babywiege, Produkt Verleih, Baby Schlaf, Produkt günstig">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
    }

    public function add_open_graph_tags() {
        global $post, $wpdb;

        if (!is_singular() || (!has_shortcode($post->post_content, 'produkt_product') && !is_singular('produkt'))) {
            return;
        }

        $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return;
        }

        $og_title = !empty($category->meta_title) ? $category->meta_title : $category->page_title;
        $og_description = !empty($category->meta_description) ? $category->meta_description : $category->page_description;
        $og_image = !empty($category->default_image) ? $category->default_image : '';
        $og_url = get_permalink($post->ID);

        echo '<!-- Open Graph Tags -->' . "\n";
        echo '<meta property="og:type" content="product">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        if (!empty($og_image)) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '">' . "\n";
        if (!empty($og_image)) {
            echo '<meta name="twitter:image" content="' . esc_url($og_image) . '">' . "\n";
        }
    }

    public function add_schema_markup() {
        global $post, $wpdb;

        if (!is_singular() || (!has_shortcode($post->post_content, 'produkt_product') && !is_singular('produkt'))) {
            return;
        }

        $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
        preg_match($pattern, $post->post_content, $matches);
        $category_shortcode = isset($matches[1]) ? $matches[1] : '';

        $category = null;
        if (!empty($category_shortcode)) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                $category_shortcode
            ));
        }

        if (!$category) {
            $category = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order LIMIT 1");
        }

        if (!$category) {
            return;
        }

        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_variants WHERE category_id = %d ORDER BY sort_order",
            $category->id
        ));

        if (empty($variants)) {
            return;
        }

        $prices = array();
        foreach ($variants as $v) {
            if (!empty($v->stripe_price_id)) {
                $p = StripeService::get_price_amount($v->stripe_price_id);
                if (!is_wp_error($p)) {
                    $prices[] = $p;
                }
            }
        }
        if (empty($prices)) {
            return;
        }
        $min_price = min($prices);
        $max_price = max($prices);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $category->product_title,
            'description' => $category->product_description,
            'category' => 'Baby & Toddler > Baby Transport > Baby Swings',
            'brand' => [
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'EUR',
                'lowPrice' => $min_price,
                'highPrice' => $max_price,
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => $min_price,
                    'priceCurrency' => 'EUR',
                    'unitCode' => 'MON',
                    'unitText' => 'pro Monat'
                ],
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID),
                'seller' => [
                    '@type' => 'Organization',
                    'name' => get_bloginfo('name')
                ]
            ]
        ];

        if (!empty($category->default_image)) {
            $schema['image'] = $category->default_image;
        }

        $total_interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_analytics WHERE category_id = %d AND event_type = 'rent_button_click'",
            $category->id
        ));

        if ($total_interactions > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => max(1, floor($total_interactions / 10)),
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }

        echo '<script type="application/ld+json">' . "\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo "\n" . '</script>' . "\n";
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
        $shipping_price_id = sanitize_text_field($_POST['shipping_price_id'] ?? '');

        $init = StripeService::init();
        if (is_wp_error($init)) {
            wp_die($init->get_error_message());
        }

        try {
            $tos_url = get_option('produkt_tos_url', home_url('/agb'));
            $session_args = [
                'mode' => 'subscription',
                'payment_method_types' => ['card', 'paypal'],
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
}
