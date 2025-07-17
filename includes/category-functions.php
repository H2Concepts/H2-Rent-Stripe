<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve categories in a parent/child tree.
 *
 * @return array
 */
function produkt_get_categories_hierarchical() {
    global $wpdb;
    $table = $wpdb->prefix . 'produkt_categories';
    $categories = $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order, name");

    $tree = [];
    foreach ($categories as $cat) {
        if ((int) $cat->parent_id === 0) {
            $tree[$cat->id] = ['category' => $cat, 'children' => []];
        }
    }
    foreach ($categories as $cat) {
        if ((int) $cat->parent_id !== 0 && isset($tree[$cat->parent_id])) {
            $tree[$cat->parent_id]['children'][] = $cat;
        }
    }
    return $tree;
}

/**
 * Render a dropdown for selecting categories with indentation for children.
 *
 * @param array $selected Preselected IDs.
 * @return void
 */
function produkt_render_category_dropdown($selected = []) {
    $tree = produkt_get_categories_hierarchical();
    foreach ($tree as $parent) {
        $id = $parent['category']->id;
        echo '<option value="' . esc_attr($id) . '"' . (in_array($id, $selected) ? ' selected' : '') . '>' . esc_html($parent['category']->name) . '</option>';
        foreach ($parent['children'] as $child) {
            $cid = $child->id;
            echo '<option value="' . esc_attr($cid) . '"' . (in_array($cid, $selected) ? ' selected' : '') . '>-- ' . esc_html($child->name) . '</option>';
        }
    }
}

/**
 * Get all product IDs belonging to a category including its subcategories.
 *
 * @param int $category_id
 * @return array
 */
function produkt_get_product_ids_by_category($category_id) {
    global $wpdb;
    $cat_ids = [(int) $category_id];
    $subs = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}produkt_categories WHERE parent_id = %d",
        $category_id
    ));
    if (!empty($subs)) {
        $cat_ids = array_merge($cat_ids, $subs);
    }
    $placeholders = implode(',', array_fill(0, count($cat_ids), '%d'));
    return $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT produkt_id FROM {$wpdb->prefix}produkt_product_to_category WHERE category_id IN ($placeholders)",
        ...$cat_ids
    ));
}

/**
 * Render a simple hierarchical list of categories for the admin.
 *
 * @return void
 */
function produkt_render_category_admin_list() {
    $tree = produkt_get_categories_hierarchical();
    echo '<ul class="produkt-category-list">';
    foreach ($tree as $parent) {
        echo '<li><strong>' . esc_html($parent['category']->name) . '</strong>';
        if (!empty($parent['children'])) {
            echo '<ul>';
            foreach ($parent['children'] as $child) {
                echo '<li>' . esc_html($child->name) . '</li>';
            }
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
