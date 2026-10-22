<?php
/**
 * EcoShop Studio theme – a self-contained product showcase for DE3.
 * This code runs only in the NEW WordPress Compose lab when activated.
 */
defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ecoshop-studio', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('ecoshop-studio', get_template_directory_uri() . '/assets/app.js', array(), '1.0.0', true);
});

add_action('init', function () {
    register_post_type('eco_product', array(
        'labels' => array('name' => 'Sản phẩm EcoShop', 'singular_name' => 'Sản phẩm', 'add_new_item' => 'Thêm sản phẩm'),
        'public' => true, 'show_in_rest' => true, 'has_archive' => false,
        'rewrite' => array('slug' => 'chi-tiet-san-pham'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
        'menu_icon' => 'dashicons-store'
    ));
    register_post_type('eco_inquiry', array(
        'labels' => array('name' => 'Liên hệ demo', 'singular_name' => 'Liên hệ demo'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false,
        'supports' => array('title', 'editor'), 'menu_icon' => 'dashicons-email-alt'
    ));
});

function eco_demo_seed_products() {
    return array(
        array('slug'=>'den-ban-led-aura','name'=>'Đèn bàn LED Aura','price'=>349000,'old'=>449000,'category'=>'Góc làm việc','art'=>'lamp','tag'=>'Được yêu thích','short'=>'Ánh sáng dịu mắt, ba chế độ tiện lợi cho mỗi ngày.','desc'=>'Thiết kế thanh lịch với ánh sáng tuỳ chỉnh, phù hợp học tập, làm việc và đọc sách.','features'=>array('3 chế độ ánh sáng', 'Thiết kế tiết kiệm diện tích', 'Điều chỉnh góc chiếu linh hoạt')),
        array('slug'=>'binh-giu-nhiet-zen','name'=>'Bình giữ nhiệt Zen','price'=>219000,'old'=>289000,'category'=>'Đời sống','art'=>'bottle','tag'=>'Mới','short'=>'Giữ nhiệt bền bỉ, đồng hành trên mọi hành trình.','desc'=>'Bình giữ nhiệt dáng gọn, màu sắc hiện đại, thích hợp đi học, đi làm và du lịch.','features'=>array('Nắp vặn chắc chắn', 'Kiểu dáng dễ mang theo', 'Chất liệu bền đẹp')),
        array('slug'=>'tai-nghe-airbeat','name'=>'Tai nghe AirBeat','price'=>690000,'old'=>890000,'category'=>'Công nghệ','art'=>'headphones','tag'=>'Nổi bật','short'=>'Âm thanh trong trẻo, thiết kế thoải mái suốt ngày dài.','desc'=>'Tai nghe không dây gọn nhẹ để học online, nghe nhạc và gọi video tiện lợi.','features'=>array('Kết nối không dây', 'Đệm tai mềm mại', 'Dễ dàng mang theo')),
        array('slug'=>'ke-de-ban-nora','name'=>'Kệ để bàn Nora','price'=>459000,'old'=>540000,'category'=>'Góc làm việc','art'=>'shelf','tag'=>'Tiện dụng','short'=>'Sắp xếp góc nhỏ trở nên gọn gàng và truyền cảm hứng.','desc'=>'Kệ lưu trữ tối giản giúp tối ưu không gian bàn làm việc hoặc phòng ngủ.','features'=>array('Nhiều ngăn tiện ích', 'Phong cách tối giản', 'Dễ bố trí trong mọi phòng')),
        array('slug'=>'chuot-khong-day-move','name'=>'Chuột không dây Move','price'=>289000,'old'=>359000,'category'=>'Công nghệ','art'=>'mouse','tag'=>'Giá tốt','short'=>'Cầm vừa tay, di chuyển mượt mà và linh hoạt.','desc'=>'Chuột không dây thiết kế công thái học cơ bản cho học tập và văn phòng.','features'=>array('Kết nối không dây', 'Thiết kế nhỏ gọn', 'Thao tác êm ái')),
        array('slug'=>'ban-phim-mini-keygo','name'=>'Bàn phím Mini KeyGo','price'=>849000,'old'=>990000,'category'=>'Công nghệ','art'=>'keyboard','tag'=>'Bán chạy','short'=>'Tối giản góc máy tính với trải nghiệm gõ thú vị.','desc'=>'Bàn phím mini gọn gàng dành cho làm việc tại nhà hoặc không gian học tập hiện đại.','features'=>array('Bố cục nhỏ gọn', 'Cảm giác gõ dễ chịu', 'Phong cách trẻ trung'))
    );
}
function eco_demo_money($amount) { return number_format((int)$amount, 0, ',', '.') . '₫'; }
function eco_demo_img($art) { return get_template_directory_uri() . '/assets/' . sanitize_file_name($art) . '.svg'; }
function eco_demo_query($limit = -1) {
    return new WP_Query(array('post_type'=>'eco_product','post_status'=>'publish','posts_per_page'=>$limit,'orderby'=>array('menu_order'=>'ASC','date'=>'ASC')));
}
function eco_demo_render_card($id) {
    $category = get_post_meta($id, 'eco_category', true);
    $art = get_post_meta($id, 'eco_art', true);
    $price = (int)get_post_meta($id, 'eco_price', true);
    $old = (int)get_post_meta($id, 'eco_old_price', true);
    $tag = get_post_meta($id, 'eco_tag', true);
    $excerpt = get_the_excerpt($id);
    ?>
    <article class="product-card" data-category="<?php echo esc_attr($category); ?>" data-name="<?php echo esc_attr(mb_strtolower(get_the_title($id))); ?>">
      <a class="product-art art-<?php echo esc_attr($art); ?>" href="<?php echo esc_url(get_permalink($id)); ?>" aria-label="<?php echo esc_attr('Xem ' . get_the_title($id)); ?>">
        <?php if ($tag) : ?><span class="product-tag"><?php echo esc_html($tag); ?></span><?php endif; ?>
        <img loading="lazy" src="<?php echo esc_url(eco_demo_img($art)); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>">
      </a>
      <div class="product-information"><span class="eyebrow category-label"><?php echo esc_html($category); ?></span>
        <h3><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></h3>
        <p><?php echo esc_html($excerpt); ?></p>
        <div class="product-bottom"><div><strong><?php echo esc_html(eco_demo_money($price)); ?></strong><?php if ($old) : ?><del><?php echo esc_html(eco_demo_money($old)); ?></del><?php endif; ?></div>
          <a class="round-link" href="<?php echo esc_url(get_permalink($id)); ?>" aria-label="Chi tiết sản phẩm"><span aria-hidden="true">↗</span></a>
        </div>
      </div>
    </article>
    <?php
}
function eco_demo_navigation($slug) {
    $page = get_page_by_path($slug);
    return $page ? get_permalink($page) : home_url('/' . $slug . '/');
}
function eco_demo_contact_handler() {
    if (!isset($_POST['eco_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_nonce'])), 'eco_demo_contact')) {
        wp_die('Phiên gửi không hợp lệ.', 'Lỗi', array('response'=>403));
    }
    // A honeypot plus a short-lived throttle are sufficient for a coursework demo.
    if (!empty($_POST['website'])) { wp_safe_redirect(add_query_arg('sent','1',eco_demo_navigation('lien-he'))); exit; }
    $name = sanitize_text_field(wp_unslash(isset($_POST['full_name']) ? $_POST['full_name'] : ''));
    $email = sanitize_email(wp_unslash(isset($_POST['email']) ? $_POST['email'] : ''));
    $message = sanitize_textarea_field(wp_unslash(isset($_POST['message']) ? $_POST['message'] : ''));
    if (!$name || !is_email($email) || strlen($message) < 8 || strlen($message) > 2000) {
        wp_safe_redirect(add_query_arg('sent','invalid',eco_demo_navigation('lien-he'))); exit;
    }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'eco_msg_' . md5(wp_salt('auth') . $ip);
    if (get_transient($key)) {
        wp_safe_redirect(add_query_arg('sent','slow',eco_demo_navigation('lien-he'))); exit;
    }
    $id = wp_insert_post(array('post_type'=>'eco_inquiry','post_status'=>'private',
        'post_title'=>'Liên hệ demo – ' . $name,'post_content'=>$message), true);
    if (!is_wp_error($id) && $id) {
        update_post_meta($id, '_eco_reply_email', $email);
        set_transient($key, 1, MINUTE_IN_SECONDS);
        wp_safe_redirect(add_query_arg('sent','1',eco_demo_navigation('lien-he'))); exit;
    }
    wp_safe_redirect(add_query_arg('sent','error',eco_demo_navigation('lien-he'))); exit;
}
add_action('admin_post_nopriv_eco_demo_contact', 'eco_demo_contact_handler');
add_action('admin_post_eco_demo_contact', 'eco_demo_contact_handler');
