<?php
// Read-only CLI verification of WordPress cart and checkout registration.
if (PHP_SAPI !== 'cli') exit(1);
define('WP_USE_THEMES', false);
require '/var/www/html/wp-load.php';
if (get_option('stylesheet') !== 'ecoshop-studio') {
    fwrite(STDERR,"CHECKOUT_THEME_NOT_ACTIVE\n");
    exit(1);
}
foreach (array('eco_cart_get','eco_cart_save','eco_cart_rows','eco_cart_render_page','eco_cart_render_checkout','eco_cart_render_success') as $func) {
    if (!function_exists($func)) {
        fwrite(STDERR,"CHECKOUT_FUNCTION_MISSING: ".$func."\n");
        exit(1);
    }
}
if (!post_type_exists('eco_order')) {
    fwrite(STDERR,"ECO_ORDER_NOT_REGISTERED\n");
    exit(1);
}
foreach (array('gio-hang','thanh-toan','dat-hang-thanh-cong') as $slug) {
    $page=get_page_by_path($slug,OBJECT,'page');
    if (!$page || $page->post_status !== 'publish') {
        fwrite(STDERR,"SHOP_PAGE_MISSING: ".$slug."\n");
        exit(1);
    }
    echo "SHOP_PAGE_OK: ".$slug."\n";
}
$counts=wp_count_posts('eco_product');
$published=isset($counts->publish) ? (int)$counts->publish : 0;
if ($published<1) {
    fwrite(STDERR,"NO_PUBLISHED_PRODUCTS\n");
    exit(1);
}
echo "PUBLISHED_PRODUCTS: ".$published."\n";
echo "CHECKOUT_BACKEND_OK\n";
