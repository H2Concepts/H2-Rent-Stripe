<?php
if (!defined('ABSPATH')) {
    exit;
}

use ProduktVerleih\Admin;
use ProduktVerleih\Database;

global $wpdb;

$table_reviews = $wpdb->prefix . 'produkt_reviews';
$categories = Database::get_product_categories_tree();
array_unshift($categories, (object) ['id' => 0, 'name' => __('Alle Kategorien', 'h2-rental-pro')]);

$selected_category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

if (isset($_GET['delete_review'])) {
    Admin::verify_admin_action('fw_nonce');
    $wpdb->delete($table_reviews, ['id' => intval($_GET['delete_review'])]);
}

$metrics = Database::get_review_admin_metrics();
$reviews = Database::get_reviews_with_meta($selected_category, $search_term, 300);

if (!function_exists('pv_extract_review_title_admin')) {
    function pv_extract_review_title_admin($text)
    {
        $title = '';
        if (preg_match('/^Titel:\s*(.+)$/mi', (string) $text, $m)) {
            $title = trim($m[1]);
        }
        if ($title === '' && preg_match('/^#\s*(.+)$/m', (string) $text, $m)) {
            $title = trim($m[1]);
        }
        return $title;
    }
}
?>

<div class="produkt-admin dashboard-wrapper">
    <div id="review-modal" class="modal-overlay" data-open="0">
        <div class="modal-content review-modal-content">
            <button type="button" class="modal-close">&times;</button>
            <h2 class="modal-title"><?php echo esc_html__('Bewertung ansehen', 'h2-rental-pro'); ?></h2>
            <div class="modal-body" id="review-modal-body">
                <div class="review-modal-header">
                    <div class="review-modal-product-info">
                        <div class="review-modal-product"></div>
                        <div class="review-modal-meta">
                            <div class="review-modal-name"></div>
                            <div class="review-modal-date"></div>
                        </div>
                    </div>
                    <div class="review-modal-rating"></div>
                </div>
                <div class="review-modal-content-section">
                    <h3 class="review-modal-title"></h3>
                    <div class="review-modal-text"></div>
                </div>
            </div>
        </div>
    </div>

    <h1 class="dashboard-greeting"><?php echo pv_get_time_greeting(); ?>,
        <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋
    </h1>
    <p class="dashboard-subline"><?php echo esc_html__('Kundenbewertungen verwalten', 'h2-rental-pro'); ?></p>

    <div class="product-info-grid cols-4">
        <div class="product-info-box bg-pastell-gelb">
            <span class="label"><?php echo esc_html__('Anzahl Bewertungen', 'h2-rental-pro'); ?></span>
            <strong class="value"><?php echo intval($metrics['total'] ?? 0); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-orange">
            <span class="label"><?php echo esc_html__('Anzahl 1 Stern', 'h2-rental-pro'); ?></span>
            <strong class="value"><?php echo intval($metrics['one_star'] ?? 0); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-gruen">
            <span class="label"><?php echo esc_html__('Anzahl 5 Sterne', 'h2-rental-pro'); ?></span>
            <strong class="value"><?php echo intval($metrics['five_star'] ?? 0); ?></strong>
        </div>
        <div class="product-info-box bg-pastell-mint">
            <span class="label"><?php echo esc_html__('Abos nicht bewertet', 'h2-rental-pro'); ?></span>
            <strong class="value"><?php echo intval($metrics['unreviewed'] ?? 0); ?></strong>
        </div>
    </div>

    <div class="h2-rental-card">
        <div class="card-header-flex">
            <div>
                <h2><?php echo esc_html__('Kundenbewertungen', 'h2-rental-pro'); ?></h2>
                <p class="card-subline">
                    <?php echo esc_html__('Alle abgegebenen Bewertungen im Überblick', 'h2-rental-pro'); ?>
                </p>
            </div>
            <div class="card-header-actions">
                <form method="get" class="produkt-filter-form product-search-bar">
                    <input type="hidden" name="page" value="produkt-reviews">
                    <div class="search-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="search-icon">
                            <path
                                d="M10 2a8 8 0 105.3 14.1l4.3 4.3a1 1 0 101.4-1.4l-4.3-4.3A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z" />
                        </svg>
                        <input type="text" name="s"
                            placeholder="<?php echo esc_attr__('Nach Produkt oder Kunde suchen', 'h2-rental-pro'); ?>"
                            value="<?php echo esc_attr($search_term); ?>">
                    </div>
                    <select name="category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat->id); ?>" <?php selected($selected_category, $cat->id); ?>><?php echo str_repeat('--', $cat->depth ?? 0) . ' ' . esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <form method="post" class="produkt-compact-form">
            <?php wp_nonce_field('produkt_admin_action', 'produkt_admin_nonce'); ?>
            <table class="activity-table">
                <thead>

                    <tr>
                        <th><?php echo esc_html__('Produkt', 'h2-rental-pro'); ?></th>
                        <th><?php echo esc_html__('Name', 'h2-rental-pro'); ?></th>
                        <th><?php echo esc_html__('Datum', 'h2-rental-pro'); ?></th>
                        <th><?php echo esc_html__('Titel', 'h2-rental-pro'); ?></th>
                        <th><?php echo esc_html__('Sterne', 'h2-rental-pro'); ?></th>
                        <th><?php echo esc_html__('Aktionen', 'h2-rental-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $title = pv_extract_review_title_admin($review->review_text ?? '') ?: '–';
                            $product_name = $review->product_name ?: (sprintf(__('Produkt #%s', 'h2-rental-pro'), ($review->product_id ?? '-')));
                            $full_name = trim(($review->first_name ?? '') . ' ' . ($review->last_name ?? ''));
                            $rating = (float) ($review->rating ?? 0);
                            $date_str = $review->created_at ? date_i18n('d.m.Y', strtotime($review->created_at)) : '–';
                            $view_text = trim((string) ($review->review_text ?? ''));
                            $modal_title = $title !== '–' ? $title : __('Ohne Titel', 'h2-rental-pro');
                            $modal_name = $full_name !== '' ? $full_name : __('Unbekannter Kunde', 'h2-rental-pro');
                            ?>
                            <tr>
                                <td><?php echo esc_html($product_name); ?></td>
                                <td><?php echo esc_html($full_name !== '' ? $full_name : '–'); ?></td>
                                <td><?php echo esc_html($date_str); ?></td>
                                <td><?php echo esc_html($title); ?></td>
                                <td>
                                    <div class="produkt-star-rating" style="--rating: <?php echo esc_attr($rating); ?>;"></div>
                                    <span class="rating-number"><?php echo number_format($rating, 1, ',', ''); ?></span>
                                </td>
                                <td>
                                    <button type="button" class="icon-btn icon-btn-no-stroke review-view-btn"
                                        aria-label="<?php echo esc_attr__('Ansehen', 'h2-rental-pro'); ?>"
                                        data-title="<?php echo esc_attr($modal_title); ?>"
                                        data-text="<?php echo esc_attr($view_text); ?>"
                                        data-product="<?php echo esc_attr($product_name); ?>"
                                        data-name="<?php echo esc_attr($modal_name); ?>"
                                        data-date="<?php echo esc_attr($date_str); ?>"
                                        data-rating="<?php echo esc_attr($rating); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 22.1">
                                            <path
                                                d="M16,0C7.2,0,0,4.9,0,11s7.2,11,16,11,16-4.9,16-11S24.8,0,16,0ZM16,20c-7.7,0-14-4-14-9S8.3,2,16,2s14,4,14,9-6.3,9-14,9ZM16,5c-3.3,0-6,2.7-6,6s2.7,6,6,6,6-2.7,6-6-2.7-6-6-6ZM16,15c-2.2,0-4-1.8-4-4s1.8-4,4-4,4,1.8,4,4-1.8,4-4,4Z" />
                                        </svg>
                                    </button>
                                    <?php $delete_url = wp_nonce_url(add_query_arg([
                                        'page' => 'produkt-reviews',
                                        'category' => $selected_category,
                                        's' => $search_term,
                                        'delete_review' => $review->id,
                                    ]), 'produkt_admin_action', 'fw_nonce'); ?>
                                    <a class="icon-btn" href="<?php echo esc_url($delete_url); ?>"
                                        onclick="return confirm('<?php echo esc_js(__('Bist du sicher das du Löschen möchtest?', 'h2-rental-pro')); ?>');"
                                        aria-label="<?php echo esc_attr__('Löschen', 'h2-rental-pro'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 79.9 80.1">
                                            <path
                                                d="M39.8.4C18,.4.3,18.1.3,40s17.7,39.6,39.6,39.6,39.6-17.7,39.6-39.6S61.7.4,39.8.4ZM39.8,71.3c-17.1,0-31.2-14-31.2-31.2s14.2-31.2,31.2-31.2,31.2,14,31.2,31.2-14.2,31.2-31.2,31.2Z" />
                                            <path
                                                d="M53,26.9c-1.7-1.7-4.2-1.7-5.8,0l-7.3,7.3-7.3-7.3c-1.7-1.7-4.2-1.7-5.8,0-1.7,1.7-1.7,4.2,0,5.8l7.3,7.3-7.3,7.3c-1.7,1.7-1.7,4.2,0,5.8.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2l7.3-7.3,7.3,7.3c.8.8,1.9,1.2,2.9,1.2s2.1-.4,2.9-1.2c1.7-1.7,1.7-4.2,0-5.8l-7.3-7.3,7.3-7.3c1.7-1.7,1.7-4.4,0-5.8h0Z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6"><?php echo esc_html__('Keine Bewertungen gefunden.', 'h2-rental-pro'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>