<?php
/**
 * Simple product-management fields for EcoShop.
 * Admin only. WordPress handles creating/editing/trashing products and uploading images.
 */
defined('ABSPATH') || exit;

/* The classic editor is simpler for the coursework's product management demo. */
add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
    return $post_type === 'eco_product' ? false : $use_block_editor;
}, 10, 2);

add_action('add_meta_boxes_eco_product', function () {
    add_meta_box(
        'eco_product_details',
        'Thông tin sản phẩm',
        'eco_product_details_metabox',
        'eco_product',
        'normal',
        'high'
    );
});

function eco_product_details_metabox($post) {
    wp_nonce_field('eco_save_product_' . $post->ID, 'eco_product_nonce');
    $price = get_post_meta($post->ID, 'eco_price', true);
    $old_price = get_post_meta($post->ID, 'eco_old_price', true);
    $category = get_post_meta($post->ID, 'eco_category', true);
    $tag = get_post_meta($post->ID, 'eco_tag', true);
    $art = get_post_meta($post->ID, 'eco_art', true);
    $features = get_post_meta($post->ID, 'eco_features', true);
    $features_text = is_array($features) ? implode("\n", $features) : '';
    $categories = array('Góc làm việc', 'Công nghệ', 'Đời sống');
    $arts = array(
        'lamp'=>'Đèn bàn', 'bottle'=>'Bình nước', 'headphones'=>'Tai nghe',
        'shelf'=>'Kệ để bàn', 'mouse'=>'Chuột', 'keyboard'=>'Bàn phím'
    );
    ?>
    <style>
      .eco-admin-details{max-width:840px}
      .eco-admin-details .eco-field{margin:0 0 18px}
      .eco-admin-details label{display:block;font-size:14px;font-weight:600;margin:0 0 6px}
      .eco-admin-details input[type=text],.eco-admin-details select,.eco-admin-details textarea{width:100%;max-width:630px}
      .eco-admin-details p.description{font-size:12px;margin:5px 0 0}
      .eco-admin-details .eco-two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
      @media(max-width:782px){.eco-admin-details .eco-two-col{display:block}}
    </style>
    <div class="eco-admin-details">
      <p>Nhập <strong>tên sản phẩm</strong> ở trên, <strong>mô tả ngắn</strong> ở ô Tóm tắt và <strong>mô tả chi tiết</strong> trong khung soạn thảo. Đặt <strong>Ảnh đại diện</strong> ở cột bên phải để dùng hình thật thay cho hình minh họa.</p>
      <div class="eco-two-col">
        <div class="eco-field">
          <label for="eco_price">Giá bán tham khảo (VNĐ) *</label>
          <input id="eco_price" name="eco_price" type="text" inputmode="numeric" placeholder="349000" value="<?php echo esc_attr($price); ?>">
          <p class="description">Nhập số nguyên, ví dụ 349000.</p>
        </div>
        <div class="eco-field">
          <label for="eco_old_price">Giá gốc (VNĐ) – tùy chọn</label>
          <input id="eco_old_price" name="eco_old_price" type="text" inputmode="numeric" placeholder="449000" value="<?php echo esc_attr($old_price); ?>">
        </div>
      </div>
      <div class="eco-two-col">
        <div class="eco-field">
          <label for="eco_category">Danh mục *</label>
          <select id="eco_category" name="eco_category">
            <?php foreach ($categories as $option) : ?>
              <option value="<?php echo esc_attr($option); ?>" <?php selected($category, $option); ?>><?php echo esc_html($option); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="eco-field">
          <label for="eco_tag">Nhãn hiển thị (tùy chọn)</label>
          <input id="eco_tag" name="eco_tag" type="text" maxlength="35" placeholder="Sản phẩm mới" value="<?php echo esc_attr($tag); ?>">
        </div>
      </div>
      <div class="eco-field">
        <label for="eco_features">Điểm nổi bật</label>
        <textarea id="eco_features" name="eco_features" rows="5" placeholder="Thiết kế nhỏ gọn&#10;Dễ sử dụng&#10;Tiện mang theo"><?php echo esc_textarea($features_text); ?></textarea>
        <p class="description">Mỗi dòng là một đặc điểm. Tối đa 6 dòng.</p>
      </div>
      <div class="eco-field">
        <label for="eco_art">Ảnh minh họa mặc định</label>
        <select id="eco_art" name="eco_art">
          <?php foreach ($arts as $slug => $label) : ?>
            <option value="<?php echo esc_attr($slug); ?>" <?php selected($art ?: 'lamp', $slug); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="description">Chỉ dùng nếu bạn chưa tải Ảnh đại diện của sản phẩm lên.</p>
      </div>
      <p><strong>Cách xuất bản:</strong> Nhấn <em>Đăng</em> để sản phẩm xuất hiện trên EcoShop. Nhấn <em>Lưu bản nháp</em> nếu chưa muốn công khai. Muốn xóa, vào danh sách sản phẩm và chọn <em>Thùng rác</em>.</p>
    </div>
    <?php
}

/* Do not bypass WordPress autosave, nonce or role-based authorization checks. */
add_action('save_post_eco_product', function ($post_id, $post, $update) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!isset($_POST['eco_product_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eco_product_nonce'])), 'eco_save_product_' . $post_id)
        || !current_user_can('edit_post', $post_id)) {
        return;
    }
    $categories = array('Góc làm việc', 'Công nghệ', 'Đời sống');
    $arts = array('lamp','bottle','headphones','shelf','mouse','keyboard');
    foreach (array('eco_price','eco_old_price') as $key) {
        if (!isset($_POST[$key]) || !is_scalar($_POST[$key])) continue;
        $raw = trim(sanitize_text_field(wp_unslash($_POST[$key])));
        $digits = preg_replace('/[^0-9]/', '', $raw);
        $value = $digits === '' ? 0 : (int)substr($digits, 0, 12);
        update_post_meta($post_id, $key, max(0, $value));
    }
    if (isset($_POST['eco_category']) && is_scalar($_POST['eco_category'])) {
        $value = sanitize_text_field(wp_unslash($_POST['eco_category']));
        if (in_array($value, $categories, true)) update_post_meta($post_id, 'eco_category', $value);
    }
    if (isset($_POST['eco_art']) && is_scalar($_POST['eco_art'])) {
        $value = sanitize_key(wp_unslash($_POST['eco_art']));
        if (in_array($value, $arts, true)) update_post_meta($post_id, 'eco_art', $value);
    }
    if (isset($_POST['eco_tag']) && is_scalar($_POST['eco_tag'])) {
        update_post_meta($post_id, 'eco_tag', mb_substr(sanitize_text_field(wp_unslash($_POST['eco_tag'])), 0, 35));
    }
    if (isset($_POST['eco_features']) && is_scalar($_POST['eco_features'])) {
        $lines = preg_split('/\r\n|\r|\n/', sanitize_textarea_field(wp_unslash($_POST['eco_features'])));
        $features = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') $features[] = substr($line, 0, 150);
            if (count($features) >= 6) break;
        }
        update_post_meta($post_id, 'eco_features', $features);
    }
}, 10, 3);

/* Helpful columns in WP Admin > Sản phẩm EcoShop. */
add_filter('manage_eco_product_posts_columns', function ($columns) {
    return array(
        'cb'=>$columns['cb'],
        'title'=>'Tên sản phẩm',
        'eco_thumbnail'=>'Ảnh',
        'eco_category'=>'Danh mục',
        'eco_price'=>'Giá bán',
        'date'=>$columns['date']
    );
});
add_action('manage_eco_product_posts_custom_column', function ($column, $post_id) {
    switch ($column) {
        case 'eco_thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(55,55));
            } else {
                echo '<img src="' . esc_url(eco_demo_img(get_post_meta($post_id, 'eco_art', true))) . '" alt="" width="55" height="55" style="object-fit:contain">';
            }
            break;
        case 'eco_category':
            echo esc_html(get_post_meta($post_id, 'eco_category', true));
            break;
        case 'eco_price':
            echo esc_html(eco_demo_money(get_post_meta($post_id, 'eco_price', true)));
            break;
    }
}, 10, 2);

add_filter('enter_title_here', function ($placeholder, $post) {
    return $post && $post->post_type === 'eco_product' ? 'Nhập tên sản phẩm...' : $placeholder;
}, 10, 2);
