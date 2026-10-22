<?php
/**
 * EcoShop Demo cart + checkout (no live payments/shipping).
 * All totals are computed server-side from PUBLISHED products.
 * Session token lives in a SameSite/HttpOnly cookie; cart content in WP transient.
 */
defined('ABSPATH') || exit;
const ECO_CART_COOKIE = 'ecoshop_demo_cart';
const ECO_CART_TTL = 604800; // Seven days.
const ECO_CART_MAX_LINE = 20;
const ECO_CART_MAX_PRODUCTS = 15;

add_action('init', function () {
    register_post_type('eco_order', array(
        'labels' => array('name'=>'Đơn hàng demo', 'singular_name'=>'Đơn hàng demo'),
        'public'=>false, 'publicly_queryable'=>false, 'exclude_from_search'=>true,
        'show_ui'=>true, 'show_in_menu'=>true, 'show_in_rest'=>false,
        'supports'=>array('title'),
        'menu_icon'=>'dashicons-clipboard',
        'capability_type'=>'post',
        'map_meta_cap'=>true,
        'capabilities'=>array(
            'edit_posts'=>'manage_options',
            'edit_others_posts'=>'manage_options',
            'publish_posts'=>'manage_options',
            'read_private_posts'=>'manage_options',
            'delete_posts'=>'manage_options',
            'delete_others_posts'=>'manage_options',
            'edit_published_posts'=>'manage_options',
            'delete_published_posts'=>'manage_options',
            'create_posts'=>'do_not_allow'
        )
    ));
}, 11);

function eco_cart_url($which='gio-hang') {
    return eco_demo_navigation($which);
}
function eco_cart_token($create=false) {
    static $token = null;
    if ($token !== null) return $token;
    $raw = isset($_COOKIE[ECO_CART_COOKIE]) ? (string) wp_unslash($_COOKIE[ECO_CART_COOKIE]) : '';
    if (preg_match('/^[a-f0-9]{48}$/D', $raw)) {
        $token=$raw;
        return $token;
    }
    if (!$create) return '';
    $token=bin2hex(random_bytes(24));
    if (!headers_sent()) {
        setcookie(ECO_CART_COOKIE, $token, array(
            'expires'=>time()+ECO_CART_TTL, 'path'=>'/',
            'secure'=>is_ssl(), 'httponly'=>true, 'samesite'=>'Lax'
        ));
    }
    return $token;
}
function eco_cart_key($create=false) {
    $token=eco_cart_token($create);
    return $token ? 'eco_cart_'.hash_hmac('sha256',$token,wp_salt('auth')) : '';
}
function eco_cart_get() {
    $key=eco_cart_key(false);
    if (!$key) return array();
    $data=get_transient($key);
    if (!is_array($data)) return array();
    $cart=array();
    foreach ($data as $id=>$qty) {
        $id=absint($id); $qty=absint($qty);
        if (!$id || !$qty || count($cart)>=ECO_CART_MAX_PRODUCTS) continue;
        $cart[$id]=min(ECO_CART_MAX_LINE, $qty);
    }
    return $cart;
}
function eco_cart_save($cart) {
    $key=eco_cart_key(true);
    if (!$key) return false;
    return set_transient($key, $cart, ECO_CART_TTL);
}
function eco_cart_rows() {
    $cart=eco_cart_get();
    $rows=array();
    $total=0;
    foreach ($cart as $id=>$qty) {
        $post=get_post($id);
        if (!$post || $post->post_type!=='eco_product' || $post->post_status!=='publish') continue;
        $price=(int)get_post_meta($id,'eco_price',true);
        if ($price<=0 || $price>200000000) continue; // No zero/invalid prices.
        $image=get_the_post_thumbnail_url($id,'thumbnail');
        if (!$image) $image=eco_demo_img(get_post_meta($id,'eco_art',true) ?: 'lamp');
        $subtotal=$price*$qty;
        $rows[]=array('id'=>$id,'title'=>get_the_title($id),'url'=>get_permalink($id),
            'image'=>$image,'quantity'=>$qty,'price'=>$price,'subtotal'=>$subtotal);
        $total+=$subtotal;
    }
    return array('items'=>$rows,'total'=>$total,'count'=>array_sum(array_column($rows,'quantity')));
}
function eco_cart_count() {
    $cart=eco_cart_get();
    return array_sum($cart);
}
function eco_cart_redirect($status='updated') {
    wp_safe_redirect(add_query_arg('cart_status',sanitize_key($status),eco_cart_url()));
    exit;
}

function eco_cart_handle_action() {
    check_admin_referer('eco_cart_action','eco_cart_nonce');
    $command=isset($_POST['cart_command']) ? sanitize_key(wp_unslash($_POST['cart_command'])) : '';
    $cart=eco_cart_get();
    if ($command==='add') {
        $id=isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $product=get_post($id);
        $price=$id ? (int)get_post_meta($id,'eco_price',true) : 0;
        if (!$product || $product->post_type!=='eco_product' || $product->post_status!=='publish' || $price<=0) {
            eco_cart_redirect('invalid');
        }
        if (!isset($cart[$id]) && count($cart)>=ECO_CART_MAX_PRODUCTS) eco_cart_redirect('limit');
        $qty=isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $qty=max(1,min(ECO_CART_MAX_LINE,$qty));
        $cart[$id]=min(ECO_CART_MAX_LINE,($cart[$id] ?? 0)+$qty);
        eco_cart_save($cart);
        eco_cart_redirect('added');
    }
    if ($command==='remove') {
        $id=isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        unset($cart[$id]);
        eco_cart_save($cart);
        eco_cart_redirect('removed');
    }
    if ($command==='update') {
        $quantities=isset($_POST['quantities']) && is_array($_POST['quantities'])
          ? wp_unslash($_POST['quantities']) : array();
        foreach ($cart as $id=>$oldqty) {
            if (!isset($quantities[$id])) continue;
            $qty=absint($quantities[$id]);
            if (!$qty) unset($cart[$id]);
            else $cart[$id]=min(ECO_CART_MAX_LINE,$qty);
        }
        eco_cart_save($cart);
        eco_cart_redirect('updated');
    }
    eco_cart_redirect('invalid');
}
add_action('admin_post_eco_cart_action','eco_cart_handle_action');
add_action('admin_post_nopriv_eco_cart_action','eco_cart_handle_action');

function eco_cart_add_button($id,$text='Thêm vào giỏ hàng') {
    if ((int)get_post_meta($id,'eco_price',true)<=0) return;
    ?>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="eco-add-cart-form">
      <input type="hidden" name="action" value="eco_cart_action">
      <input type="hidden" name="cart_command" value="add">
      <input type="hidden" name="product_id" value="<?php echo esc_attr($id); ?>">
      <input type="hidden" name="quantity" value="1">
      <?php wp_nonce_field('eco_cart_action','eco_cart_nonce'); ?>
      <button class="btn btn-primary" type="submit"><?php echo esc_html($text); ?></button>
    </form>
    <?php
}

function eco_cart_checkout_redirect($status='invalid') {
    wp_safe_redirect(add_query_arg('checkout_status',sanitize_key($status),eco_cart_url('thanh-toan')));
    exit;
}
function eco_cart_handle_checkout() {
    check_admin_referer('eco_checkout_demo','eco_checkout_nonce');
    // Honeypot: no database write, do not collect actual private/payment data.
    if (!empty($_POST['website'])) eco_cart_checkout_redirect('invalid');
    $rows=eco_cart_rows();
    if (!$rows['items'] || count($rows['items'])>ECO_CART_MAX_PRODUCTS) eco_cart_checkout_redirect('empty');
    if (!isset($_POST['confirm_demo']) || $_POST['confirm_demo']!=='1') eco_cart_checkout_redirect('confirm');
    $name=sanitize_text_field(wp_unslash(isset($_POST['customer_name']) ? $_POST['customer_name'] : ''));
    $phone=sanitize_text_field(wp_unslash(isset($_POST['customer_phone']) ? $_POST['customer_phone'] : ''));
    $email=sanitize_email(wp_unslash(isset($_POST['customer_email']) ? $_POST['customer_email'] : ''));
    $address=sanitize_textarea_field(wp_unslash(isset($_POST['customer_address']) ? $_POST['customer_address'] : ''));
    $note=sanitize_textarea_field(wp_unslash(isset($_POST['customer_note']) ? $_POST['customer_note'] : ''));
    if (strlen($name)<2 || strlen($name)>120 || !preg_match('/^[0-9+ .()\-]{9,20}$/D',$phone)
        || !is_email($email) || strlen($address)<8 || strlen($address)>400 || strlen($note)>500) {
        eco_cart_checkout_redirect('invalid');
    }
    $token=eco_cart_token();
    $lock='eco_checkout_lock_'.hash_hmac('sha256',$token,wp_salt('auth'));
    if (get_transient($lock)) eco_cart_checkout_redirect('duplicate');
    set_transient($lock,1,45);
    $reference=wp_generate_password(20,false,false);
    $created=wp_insert_post(array(
        'post_type'=>'eco_order', 'post_status'=>'private',
        'post_title'=>'Đơn đặt hàng demo – '.current_time('d/m/Y H:i:s'),
        'post_content'=>''
    ),true);
    if (is_wp_error($created) || !$created) {
        delete_transient($lock);
        eco_cart_checkout_redirect('error');
    }
    $snapshot=array();
    foreach ($rows['items'] as $row) {
        $snapshot[]=array('id'=>$row['id'],'name'=>$row['title'],
            'quantity'=>$row['quantity'],'price'=>$row['price'],'subtotal'=>$row['subtotal']);
    }
    update_post_meta($created,'_eco_order_items',$snapshot);
    update_post_meta($created,'_eco_order_total',$rows['total']);
    update_post_meta($created,'_eco_order_customer',array(
        'name'=>$name,'phone'=>$phone,'email'=>$email,'address'=>$address,'note'=>$note
    ));
    update_post_meta($created,'_eco_order_payment','Thanh toán khi nhận hàng (mô phỏng)');
    update_post_meta($created,'_eco_order_status','Chờ xác nhận (demo)');
    update_post_meta($created,'_eco_order_reference_hash',hash('sha256',$reference));
    $key=eco_cart_key();
    if ($key) delete_transient($key);
    wp_safe_redirect(add_query_arg(array('order'=>(int)$created,'key'=>$reference),
        eco_cart_url('dat-hang-thanh-cong')));
    exit;
}
add_action('admin_post_eco_demo_checkout','eco_cart_handle_checkout');
add_action('admin_post_nopriv_eco_demo_checkout','eco_cart_handle_checkout');

// Admin-only display for stored demo orders. Do not show customer data on public pages.
add_action('add_meta_boxes_eco_order',function () {
    if (!current_user_can('manage_options')) return;
    add_meta_box('eco_order_detail','Chi tiết đơn hàng demo',function ($post) {
        if (!current_user_can('manage_options')) return;
        $customer=get_post_meta($post->ID,'_eco_order_customer',true);
        $items=get_post_meta($post->ID,'_eco_order_items',true);
        $total=(int)get_post_meta($post->ID,'_eco_order_total',true);
        echo '<p><strong>Đây chỉ là đơn mô phỏng, không thu tiền và không giao hàng.</strong></p>';
        if (is_array($customer)) {
            foreach (array('name'=>'Tên','phone'=>'Điện thoại','email'=>'Email','address'=>'Địa chỉ demo','note'=>'Ghi chú') as $key=>$label) {
                echo '<p><strong>'.esc_html($label).':</strong> '.esc_html($customer[$key] ?? '').'</p>';
            }
        }
        if (is_array($items)) {
            echo '<h3>Sản phẩm</h3><ul>';
            foreach ($items as $item) {
                echo '<li>'.esc_html($item['name']).' × '.esc_html($item['quantity'])
                    .' — '.esc_html(eco_demo_money($item['subtotal'])).'</li>';
            }
            echo '</ul>';
        }
        echo '<p><strong>Tổng cộng: '.esc_html(eco_demo_money($total)).'</strong></p>';
        echo '<p>Hình thức: thanh toán khi nhận hàng (chỉ demo).</p>';
    },'eco_order','normal','high');
});

function eco_cart_notice($context='cart') {
    $status=isset($_GET[$context.'_status']) ? sanitize_key(wp_unslash($_GET[$context.'_status'])) : '';
    $messages=array(
        'added'=>'Đã thêm sản phẩm vào giỏ hàng.',
        'removed'=>'Đã bỏ sản phẩm khỏi giỏ hàng.',
        'updated'=>'Đã cập nhật giỏ hàng.',
        'limit'=>'Giỏ hàng chỉ hỗ trợ tối đa 15 sản phẩm khác nhau.',
        'invalid'=>'Thông tin chưa hợp lệ. Vui lòng kiểm tra lại.',
        'empty'=>'Giỏ hàng đang trống.',
        'confirm'=>'Bạn cần xác nhận đây là đơn hàng mô phỏng.',
        'duplicate'=>'Đang xử lý hoặc đã nhận đơn. Vui lòng không gửi nhiều lần.',
        'error'=>'Không thể lưu đơn hàng demo. Vui lòng thử lại.'
    );
    if ($status && isset($messages[$status])) {
        echo '<div class="eco-alert" role="status">'.esc_html($messages[$status]).'</div>';
    }
}
function eco_cart_render_page() {
    $cart=eco_cart_rows();
    ?>
    <section class="page-hero"><div class="container"><div class="breadcrumbs">Trang chủ / Giỏ hàng</div>
      <h1 class="page-title">Giỏ hàng của bạn</h1>
      <p>Chọn số lượng sản phẩm và xem tổng tiền trước khi đặt đơn mô phỏng.</p>
    </div></section>
    <section class="section"><div class="container">
      <?php eco_cart_notice('cart'); ?>
      <?php if (!$cart['items']) : ?>
        <div class="eco-empty"><h2>Giỏ hàng đang trống</h2><p>Bạn hãy chọn một vài sản phẩm để thử chức năng đặt hàng.</p>
          <a class="btn btn-primary" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem sản phẩm</a></div>
      <?php else : ?>
        <div class="eco-cart-layout">
          <div class="eco-cart-panel"><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="eco_cart_action"><input type="hidden" name="cart_command" value="update">
            <?php wp_nonce_field('eco_cart_action','eco_cart_nonce'); ?>
            <?php foreach ($cart['items'] as $row) : ?>
              <div class="eco-cart-row">
                <a href="<?php echo esc_url($row['url']); ?>"><img src="<?php echo esc_url($row['image']); ?>" alt="<?php echo esc_attr($row['title']); ?>"></a>
                <div><h3><a href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html($row['title']); ?></a></h3>
                  <p><?php echo esc_html(eco_demo_money($row['price'])); ?>/sản phẩm</p>
                  <label>Số lượng <input type="number" name="quantities[<?php echo esc_attr($row['id']); ?>]" value="<?php echo esc_attr($row['quantity']); ?>" min="0" max="20" step="1"></label>
                </div>
                <strong><?php echo esc_html(eco_demo_money($row['subtotal'])); ?></strong>
              </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-ghost">Cập nhật số lượng</button>
          </form></div>
          <aside class="eco-cart-panel eco-cart-summary">
            <h2>Tổng đơn hàng</h2><div class="eco-summary-line"><span>Số lượng</span><strong><?php echo esc_html($cart['count']); ?></strong></div>
            <div class="eco-summary-line"><span>Tạm tính</span><strong><?php echo esc_html(eco_demo_money($cart['total'])); ?></strong></div>
            <div class="eco-summary-line eco-total"><span>Tổng cộng</span><strong><?php echo esc_html(eco_demo_money($cart['total'])); ?></strong></div>
            <p class="notice-info">Giá và phí giao hàng chỉ mang tính mô phỏng. Không phát sinh giao dịch thật.</p>
            <a class="btn btn-primary eco-full" href="<?php echo esc_url(eco_cart_url('thanh-toan')); ?>">Tiếp tục thanh toán thử</a>
            <a class="eco-back-link" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">← Tiếp tục xem sản phẩm</a>
          </aside>
        </div>
      <?php endif; ?>
    </div></section>
    <?php
}
function eco_cart_render_checkout() {
    $cart=eco_cart_rows();
    ?>
    <section class="page-hero"><div class="container"><div class="breadcrumbs">Trang chủ / Giỏ hàng / Thanh toán</div>
      <h1 class="page-title">Thanh toán mô phỏng</h1><p>Không thu tiền, không giao hàng; chỉ kiểm thử luồng tạo đơn trong WordPress.</p>
    </div></section>
    <section class="section"><div class="container">
      <?php eco_cart_notice('checkout'); ?>
      <?php if (!$cart['items']) : ?>
        <div class="eco-empty"><h2>Chưa có sản phẩm để thanh toán</h2>
          <a class="btn btn-primary" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Chọn sản phẩm</a></div>
      <?php else : ?>
        <div class="eco-cart-layout">
          <div class="eco-cart-panel">
            <h2>Thông tin nhận hàng thử nghiệm</h2>
            <p class="notice-info">Chỉ nhập thông tin giả lập. Không dùng số điện thoại, email hoặc địa chỉ cá nhân thật.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="eco-checkout-form">
              <input type="hidden" name="action" value="eco_demo_checkout">
              <?php wp_nonce_field('eco_checkout_demo','eco_checkout_nonce'); ?>
              <div class="eco-honeypot" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
              <label>Họ và tên demo *<input type="text" name="customer_name" maxlength="120" required placeholder="Nguyễn Văn A"></label>
              <label>Số điện thoại demo *<input type="tel" name="customer_phone" maxlength="20" required placeholder="0900000000"></label>
              <label>Email demo *<input type="email" name="customer_email" maxlength="120" required placeholder="demo@example.com"></label>
              <label>Địa chỉ nhận hàng demo *<textarea name="customer_address" minlength="8" maxlength="400" rows="3" required placeholder="123 Đường Mẫu, Phường Demo, Việt Nam"></textarea></label>
              <label>Ghi chú (không bắt buộc)<textarea name="customer_note" maxlength="500" rows="2" placeholder="Ghi chú thêm..."></textarea></label>
              <div class="eco-payment-method"><strong>Phương thức thanh toán</strong>
                <p>Thanh toán khi nhận hàng (COD) — chỉ mô phỏng, không thu tiền.</p></div>
              <label class="eco-confirm"><input type="checkbox" name="confirm_demo" value="1" required>
                Tôi hiểu đây là đơn hàng thử nghiệm, không phát sinh thanh toán hoặc giao hàng thật.</label>
              <button type="submit" class="btn btn-primary eco-full">Xác nhận đơn hàng demo</button>
            </form>
          </div>
          <aside class="eco-cart-panel eco-cart-summary"><h2>Đơn hàng của bạn</h2>
            <?php foreach ($cart['items'] as $row) : ?>
              <div class="eco-summary-line"><span><?php echo esc_html($row['title']); ?> × <?php echo esc_html($row['quantity']); ?></span>
                <strong><?php echo esc_html(eco_demo_money($row['subtotal'])); ?></strong></div>
            <?php endforeach; ?>
            <div class="eco-summary-line eco-total"><span>Tổng cộng</span><strong><?php echo esc_html(eco_demo_money($cart['total'])); ?></strong></div>
            <a class="eco-back-link" href="<?php echo esc_url(eco_cart_url()); ?>">← Quay lại giỏ hàng</a>
          </aside>
        </div>
      <?php endif; ?>
    </div></section>
    <?php
}
function eco_cart_render_success() {
    $id=isset($_GET['order']) ? absint($_GET['order']) : 0;
    $key=isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
    $post=$id ? get_post($id) : null;
    $hash=$id ? get_post_meta($id,'_eco_order_reference_hash',true) : '';
    $valid=$post && $post->post_type==='eco_order' && $hash &&
        strlen($key)===20 && hash_equals($hash,hash('sha256',$key));
    ?>
    <section class="page-hero"><div class="container"><h1 class="page-title">Kết quả đặt hàng</h1></div></section>
    <section class="section"><div class="container"><div class="eco-empty">
      <?php if ($valid) : ?>
        <h2>Đã ghi nhận đơn hàng demo!</h2>
        <p>Mã đơn thử nghiệm: <strong>#<?php echo esc_html($id); ?></strong></p>
        <p>Tổng tiền tham khảo: <strong><?php echo esc_html(eco_demo_money((int)get_post_meta($id,'_eco_order_total',true))); ?></strong></p>
        <p>Trạng thái: Chờ xác nhận (demo). Không có thanh toán hoặc giao hàng thật.</p>
      <?php else : ?>
        <h2>Không tìm thấy đơn hàng thử nghiệm</h2>
        <p>Đường dẫn xác nhận không hợp lệ hoặc đã thay đổi.</p>
      <?php endif; ?>
      <a class="btn btn-primary" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Quay lại sản phẩm</a>
    </div></div></section>
    <?php
}
