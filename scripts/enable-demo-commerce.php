<?php
/**
 * Creates ONLY checkout demo pages in the already installed NEW EcoShop lab.
 * Usage: sudo docker compose exec -T wordpress php < scripts/enable-demo-commerce.php
 * Idempotent; does not touch existing product content, customers or source DB.
 */
if (PHP_SAPI !== 'cli') exit(1);
define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';

if (!is_blog_installed()) {
    fwrite(STDERR, "WORDPRESS_DATABASE_NOT_READY\n");
    exit(1);
}
if (get_option('stylesheet') !== 'ecoshop-studio') {
    fwrite(STDERR, "WRONG_ACTIVE_THEME: first install EcoShop Studio.\n");
    exit(1);
}
if (!function_exists('eco_cart_render_page') || !post_type_exists('eco_order')) {
    fwrite(STDERR, "ECOSHOP_COMMERCE_FILES_NOT_LOADED\n");
    exit(1);
}
$pages = array(
    'gio-hang' => 'Giỏ hàng',
    'thanh-toan' => 'Thanh toán demo',
    'dat-hang-thanh-cong' => 'Đặt hàng thành công'
);
$created=0;
foreach ($pages as $slug=>$title) {
    $existing=get_page_by_path($slug,OBJECT,'page');
    if ($existing) {
        if ($existing->post_status!=='publish') {
            fwrite(STDERR, "CHECK_EXISTING_PAGE_STATUS: ".$slug." (not published)\n");
            exit(1);
        }
        echo "PAGE_REUSED: ".$slug." #".$existing->ID."\n";
        continue;
    }
    $id=wp_insert_post(array(
        'post_type'=>'page',
        'post_status'=>'publish',
        'post_name'=>$slug,
        'post_title'=>$title,
        'post_content'=>'Giao diện được cung cấp bởi theme EcoShop Studio.'
    ),true);
    if (is_wp_error($id) || !$id) {
        fwrite(STDERR, "PAGE_CREATE_FAILED: ".$slug."\n");
        exit(1);
    }
    echo "PAGE_CREATED: ".$slug." #".$id."\n";
    $created++;
}
flush_rewrite_rules(false);
echo "PAGES_CREATED: ".$created."\n";
echo "CHECKOUT_DEMO_ENABLED\n";
