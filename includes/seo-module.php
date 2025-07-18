<?php
/**
 * Datei: seo-module.php
 * Zweck: Dynamische SEO-Ausgabe (Meta-Tags, Open Graph, Schema) + Sitemap + Indexierungssteuerung
 */

namespace ProduktVerleih;

class SeoModule {
    public static function init() {
        add_action('wp_head', [self::class, 'add_meta_tags']);
        add_action('wp_head', [self::class, 'add_open_graph_tags']);
        add_action('wp_head', [self::class, 'add_schema_markup']);
        add_action('init', [self::class, 'add_sitemap_rewrite']);
        add_action('template_redirect', [self::class, 'render_sitemap']);
    }

    public static function add_meta_tags() {
        global $wpdb;
        $slug = sanitize_title(get_query_var('produkt_slug'));
        if (empty($slug)) return;

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
        $category   = null;
        foreach ($categories as $cat) {
            if (sanitize_title($cat->product_title) === $slug) {
                $category = $cat;
                break;
            }
        }
        if (!$category) return;

        $title = !empty($category->meta_title) ? $category->meta_title : $category->product_title . ' | ' . get_bloginfo('name');
        $desc  = !empty($category->meta_description) ? $category->meta_description : $category->product_description;

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    }

    public static function add_open_graph_tags() {
        global $post, $wpdb;

        $slug = sanitize_title(get_query_var('produkt_slug'));

        if (!is_singular() && empty($slug)) {
            return;
        }

        $category = null;
        if ($slug) {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
            foreach ($categories as $cat) {
                if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
                    $category = $cat;
                    break;
                }
            }
        } else {
            $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
            preg_match($pattern, $post->post_content, $matches);
            $category_shortcode = isset($matches[1]) ? $matches[1] : '';

            if (!empty($category_shortcode)) {
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                    $category_shortcode
                ));
            }
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
        $og_url = $slug ? home_url('/shop/produkt/' . sanitize_title($slug)) : get_permalink($post->ID);

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

    public static function add_schema_markup() {
        global $post, $wpdb;

        $slug = sanitize_title(get_query_var('produkt_slug'));

        if (!is_singular() && empty($slug)) {
            return;
        }

        $category = null;
        if ($slug) {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
            foreach ($categories as $cat) {
                if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
                    $category = $cat;
                    break;
                }
            }
        } else {
            $pattern = '/\[produkt_product[^\]]*category=["\']([^"\']*)["\'][^\]]*\]/';
            preg_match($pattern, $post->post_content, $matches);
            $category_shortcode = isset($matches[1]) ? $matches[1] : '';

            if (!empty($category_shortcode)) {
                $category = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE shortcode = %s",
                    $category_shortcode
                ));
            }
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
                'url' => $slug ? home_url('/shop/produkt/' . sanitize_title($slug)) : get_permalink($post->ID),
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

    public static function add_sitemap_rewrite() {
        add_rewrite_rule('^shop-sitemap\.xml$', 'index.php?shop_sitemap=1', 'top');
        add_rewrite_tag('%shop_sitemap%', '1');
    }

    public static function render_sitemap() {
        if (get_query_var('shop_sitemap') != 1) return;

        global $wpdb;
        header('Content-Type: application/xml; charset=utf-8');

        $base = home_url();
        $products = $wpdb->get_results(
            "SELECT product_title FROM {$wpdb->prefix}produkt_categories WHERE active = 1 ORDER BY sort_order"
        );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($products as $p) {
            $slug = sanitize_title($p->product_title);
            echo '<url><loc>' . esc_url($base . '/shop/produkt/' . $slug) . '</loc></url>' . "\n";
        }

        echo '</urlset>'; exit;
    }
}
