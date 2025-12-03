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
        add_filter('rank_math/frontend/breadcrumb/items', [self::class, 'filter_breadcrumb_items']);
        // add sitemap reference to robots.txt
        add_filter('robots_txt', [self::class, 'add_sitemap_to_robots'], 10, 2);
    }

    public static function add_meta_tags() {
        global $wpdb;
        $slug          = sanitize_title(get_query_var('produkt_slug'));
        $category_slug = sanitize_title(get_query_var('produkt_category_slug'));

        if ($category_slug) {
            $cat = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}produkt_product_categories WHERE slug = %s",
                $category_slug
            ));
            if ($cat) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}produkt_product_to_category WHERE category_id = %d",
                    $cat->id
                ));
                if (intval($count) === 0) {
                    echo '<meta name="robots" content="noindex,follow">' . "\n";
                }
            } else {
                echo '<meta name="robots" content="noindex,follow">' . "\n";
            }
        }

        if (empty($slug)) {
            return;
        }

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE REPLACE(LOWER(product_title),' ', '-') = %s",
            $slug
        ));
        if (!$category) {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
            foreach ($categories as $cat) {
                if (sanitize_title($cat->product_title) === $slug) {
                    $category = $cat;
                    break;
                }
            }
        }
        if (!$category) return;

        $title = !empty($category->meta_title) ? $category->meta_title : $category->product_title . ' | ' . get_bloginfo('name');
        $desc  = !empty($category->meta_description) ? $category->meta_description : $category->product_description;

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
        echo '<meta name="robots" content="index,follow">' . "\n";
        $canonical = home_url('/shop/produkt/' . sanitize_title($slug));
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    public static function add_open_graph_tags() {
        global $post, $wpdb;

        $slug = sanitize_title(get_query_var('produkt_slug'));

        if (!is_singular() && empty($slug)) {
            return;
        }

        $category = null;
        if ($slug) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE REPLACE(LOWER(product_title),' ', '-') = %s",
                sanitize_title($slug)
            ));
            if (!$category) {
                $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
                foreach ($categories as $cat) {
                    if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
                        $category = $cat;
                        break;
                    }
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
        echo '<link rel="canonical" href="' . esc_url($og_url) . '">' . "\n";
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

    public static function filter_breadcrumb_items($crumbs) {
        $slug          = sanitize_title(get_query_var('produkt_slug'));
        $category_slug = sanitize_title(get_query_var('produkt_category_slug'));

        if (empty($slug) && empty($category_slug)) {
            return $crumbs;
        }

        // Normalize incoming crumbs and keep the home label if present
        $normalized_crumbs = array_values(array_filter($crumbs, function ($crumb) {
            return trim(self::get_crumb_label($crumb)) !== '';
        }));

        $home_label = !empty($normalized_crumbs) ? self::get_crumb_label($normalized_crumbs[0]) : __('Home', 'h2-concepts');
        $home_url   = home_url('/');

        if (!empty($normalized_crumbs)) {
            $first = $normalized_crumbs[0];
            if (is_array($first)) {
                if (!empty($first['url'])) {
                    $home_url = $first['url'];
                } elseif (!empty($first[1])) {
                    $home_url = $first[1];
                }
            } elseif (is_object($first) && !empty($first->url)) {
                $home_url = $first->url;
            }
        }

        $shop_page_id = get_option(\PRODUKT_SHOP_PAGE_OPTION);
        $shop_label   = __('Shop', 'h2-concepts');
        $shop_url     = '';

        if ($shop_page_id) {
            $shop_label = get_the_title($shop_page_id) ?: $shop_label;
            $shop_url   = get_permalink($shop_page_id);
        }

        if (empty($shop_url)) {
            $shop_url = home_url('/shop/');
        }

        $final_crumbs = [
            [
                'label' => $home_label,
                'url'   => $home_url,
            ],
            [
                'label' => $shop_label,
                'url'   => $shop_url,
            ],
        ];

        if (!empty($category_slug)) {
            $category = self::get_product_by_slug($category_slug);
            if ($category) {
                $final_crumbs[] = [
                    'label' => $category->product_title,
                    'url'   => '',
                ];
            }
        }

        if (!empty($slug)) {
            $product = self::get_product_by_slug($slug);
            if ($product) {
                $final_crumbs[] = [
                    'label' => $product->product_title,
                    'url'   => '',
                ];
            }
        }

        return $final_crumbs;
    }

    private static function crumb_exists($crumbs, $label) {
        foreach ($crumbs as $crumb) {
            $existing_label = self::get_crumb_label($crumb);

            if (!empty($existing_label) && strtolower($existing_label) === strtolower($label)) {
                return true;
            }
        }

        return false;
    }

    private static function get_crumb_label($crumb) {
        if (is_array($crumb)) {
            return $crumb['label'] ?? ($crumb[0] ?? '');
        }

        if (is_object($crumb) && isset($crumb->label)) {
            return $crumb->label;
        }

        return '';
    }

    private static function get_product_by_slug($slug) {
        global $wpdb;

        if (empty($slug)) {
            return null;
        }

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE REPLACE(LOWER(product_title),' ', '-') = %s",
            $slug
        ));

        if (!$category) {
            $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
            foreach ($categories as $cat) {
                if (sanitize_title($cat->product_title) === $slug) {
                    $category = $cat;
                    break;
                }
            }
        }

        return $category;
    }

    public static function add_schema_markup() {
        global $post, $wpdb;

        $slug = sanitize_title(get_query_var('produkt_slug'));

        if (!is_singular() && empty($slug)) {
            return;
        }

        $category = null;
        if ($slug) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}produkt_categories WHERE REPLACE(LOWER(product_title),' ', '-') = %s",
                sanitize_title($slug)
            ));
            if (!$category) {
                $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}produkt_categories");
                foreach ($categories as $cat) {
                    if (sanitize_title($cat->product_title) === sanitize_title($slug)) {
                        $category = $cat;
                        break;
                    }
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
        add_rewrite_rule('^shop-sitemap\.xml/?$', 'index.php?shop_sitemap=1', 'top');
        add_rewrite_tag('%shop_sitemap%', '1');
    }

    public static function add_sitemap_to_robots($output, $public) {
        $sitemap_url = home_url('/shop-sitemap.xml/');
        $output .= "\nSitemap: $sitemap_url";
        return $output;
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

        $categories = $wpdb->get_results(
            "SELECT product_title FROM {$wpdb->prefix}produkt_categories WHERE active = 1 ORDER BY sort_order"
        );
        foreach ($categories as $cat) {
            $cat_slug = sanitize_title($cat->product_title);
            echo '<url><loc>' . esc_url($base . '/shop/kategorie/' . $cat_slug) . '</loc></url>' . "\n";
        }

        echo '</urlset>'; exit;
    }
}
