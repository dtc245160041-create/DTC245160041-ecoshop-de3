<?php
/**
 * Idempotent CLI installer for EcoShop Studio.
 * Intended ONLY for the NEW Docker Compose WordPress service and lab database.
 * Invoked with: sudo docker compose exec -T wordpress php < scripts/apply-demo-site.php
 */
if (PHP_SAPI !== 'cli') { exit(1); }
define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

function eco_demo_cli_fail($message) {
    fwrite(STDERR, 'ERROR: ' . $message . PHP_EOL);
    exit(1);
}
if (!function_exists('wp_insert_post') || !is_blog_installed()) {
    eco_demo_cli_fail('WordPress is not installed or its database is unavailable.');
}
$theme = wp_get_theme('ecoshop-studio');
if (!$theme->exists()) { eco_demo_cli_fail('EcoShop Studio theme files are missing.'); }
if (!function_exists('eco_demo_seed_products')) {
    // The products function is defined in our theme. Load it before switching.
    require_once get_theme_root() . '/ecoshop-studio/functions.php';
}
// WordPress has already executed init while bootstrapping its OLD active theme.
// Register our CPT explicitly for THIS CLI process before creating demo products.
if (!post_type_exists('eco_product')) {
    register_post_type('eco_product', array(
        'label'=>'Sản phẩm EcoShop',
        'public'=>true, 'show_in_rest'=>true,
        'rewrite'=>array('slug'=>'chi-tiet-san-pham'),
        'supports'=>array('title','editor','excerpt','thumbnail')
    ));
}
$existing_theme = get_option('stylesheet');
echo 'PREVIOUS_THEME: ' . $existing_theme . PHP_EOL;

function eco_demo_find_or_add_page($slug, $title) {
    $page = get_page_by_path($slug, OBJECT, 'page');
    if ($page) {
        echo 'PAGE_REUSED: ' . $slug . ' #' . $page->ID . PHP_EOL;
        return (int) $page->ID;
    }
    $result = wp_insert_post(array(
        'post_type'=>'page', 'post_status'=>'publish',
        'post_name'=>$slug, 'post_title'=>$title,
        'post_content'=>'Nội dung hiển thị bằng giao diện EcoShop Studio.'
    ), true);
    if (is_wp_error($result) || !$result) {
        eco_demo_cli_fail('Cannot create page: ' . $slug);
    }
    echo 'PAGE_CREATED: ' . $slug . ' #' . $result . PHP_EOL;
    return (int) $result;
}

$home = eco_demo_find_or_add_page('ecoshop-demo-trang-chu', 'Trang chủ EcoShop');
eco_demo_find_or_add_page('san-pham', 'Sản phẩm');
eco_demo_find_or_add_page('gioi-thieu', 'Giới thiệu');
eco_demo_find_or_add_page('lien-he', 'Liên hệ');

$seed = eco_demo_seed_products();
$created = 0; $reused = 0;
foreach ($seed as $idx => $product) {
    $existing = get_page_by_path($product['slug'], OBJECT, 'eco_product');
    if ($existing) {
        $id = (int) $existing->ID;
        $reused++;
    } else {
        $id = wp_insert_post(array(
            'post_type'=>'eco_product',
            'post_status'=>'publish',
            'post_title'=>$product['name'],
            'post_name'=>$product['slug'],
            'post_content'=>$product['desc'],
            'post_excerpt'=>$product['short'],
            'menu_order'=>$idx
        ), true);
        if (is_wp_error($id) || !$id) eco_demo_cli_fail('Cannot create product: ' . $product['slug']);
        $created++;
    }
    // Only initialize absent fields for reruns; do not overwrite user edits.
    $fields = array(
        'eco_price'=>$product['price'],
        'eco_old_price'=>$product['old'],
        'eco_category'=>$product['category'],
        'eco_art'=>$product['art'],
        'eco_tag'=>$product['tag'],
        'eco_features'=>$product['features']
    );
    foreach ($fields as $key => $value) {
        if (!metadata_exists('post', $id, $key)) update_post_meta($id, $key, $value);
    }
}

update_option('blogname', 'EcoShop – Quảng bá sản phẩm');
update_option('blogdescription', 'Sản phẩm tiện ích cho cuộc sống hiện đại');
update_option('show_on_front', 'page');
update_option('page_on_front', $home);
if ($existing_theme !== 'ecoshop-studio') switch_theme('ecoshop-studio');
flush_rewrite_rules(false);

$actual = wp_get_theme()->get_stylesheet();
if ($actual !== 'ecoshop-studio') eco_demo_cli_fail('Theme activation did not complete.');
echo 'PRODUCTS_CREATED: ' . $created . PHP_EOL;
echo 'PRODUCTS_REUSED: ' . $reused . PHP_EOL;
echo 'THEME_ACTIVE: ' . $actual . PHP_EOL;
echo 'HOME_PAGE_ID: ' . $home . PHP_EOL;
echo 'FRONT_URL: ' . home_url('/') . PHP_EOL;
echo 'ECOSHOP_DEMO_INSTALL_OK' . PHP_EOL;
