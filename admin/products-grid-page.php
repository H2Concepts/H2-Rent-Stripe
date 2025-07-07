<?php
if (!defined('ABSPATH')) { exit; }

global $wpdb;
$categories = $wpdb->get_results("SELECT id,name FROM {$wpdb->prefix}produkt_categories ORDER BY sort_order, name");
$selected = isset($_GET['category']) ? intval($_GET['category']) : 0;
$args = [ 'post_type' => 'produkt', 'posts_per_page' => -1 ];
if ($selected) {
    $args['meta_query'] = [ [ 'key' => 'produkt_category_id', 'value' => $selected ] ];
}
$posts = get_posts($args);
?>
<div class="wrap">
    <h1>Produkte</h1>
    <form method="get" action="" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="produkt-products">
        <label>Kategorie:</label>
        <select name="category" onchange="this.form.submit()">
            <option value="0">Alle</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?php echo $c->id; ?>" <?php selected($selected,$c->id); ?>><?php echo esc_html($c->name); ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><input type="submit" class="button" value="Filtern"></noscript>
    </form>
    <div class="produkt-categories-grid">
        <?php foreach ($posts as $p): ?>
        <div class="produkt-category-card">
            <div class="produkt-category-image">
                <?php if (has_post_thumbnail($p->ID)) { echo get_the_post_thumbnail($p->ID,'medium'); } else { ?>
                <div class="produkt-category-placeholder"><span>📦</span><small>Kein Bild</small></div>
                <?php } ?>
            </div>
            <div class="produkt-category-content">
                <h4><?php echo esc_html($p->post_title); ?></h4>
                <div class="produkt-category-actions">
                    <a href="<?php echo get_edit_post_link($p->ID); ?>" class="button button-small">Bearbeiten</a>
                    <a href="<?php echo get_delete_post_link($p->ID); ?>" class="button button-small" onclick="return confirm('Löschen?');">Löschen</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
