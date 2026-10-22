<?php
defined('ABSPATH') || exit;
get_header();
if (is_page('san-pham')) : ?>
  <section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a> / Sản phẩm</div><span class="eyebrow">Bộ sưu tập EcoShop</span>
    <h1 class="page-title">Chọn điều bạn thích.</h1><p>Khám phá những món đồ hiện đại dành cho làm việc, học tập và cuộc sống hằng ngày.</p>
  </div></section>
  <section class="catalog"><div class="container">
    <div class="filters"><div class="filter-tabs" aria-label="Lọc theo nhóm sản phẩm">
      <?php $options=array('all'=>'Tất cả','Góc làm việc'=>'Góc làm việc','Công nghệ'=>'Công nghệ','Đời sống'=>'Đời sống');
      foreach($options as $value=>$label): ?>
        <button type="button" class="filter-btn <?php echo $value==='all' ? 'is-active' : ''; ?>" data-filter="<?php echo esc_attr($value); ?>" aria-pressed="<?php echo $value==='all'?'true':'false'; ?>"><?php echo esc_html($label); ?></button>
      <?php endforeach; ?></div>
      <label class="search-wrap"><span aria-hidden="true">⌕</span><input type="search" id="eco-product-search" placeholder="Tìm tên sản phẩm..." aria-label="Tìm sản phẩm"></label>
    </div>
    <div class="cards-grid"><?php $products=eco_demo_query(); if($products->have_posts()): while($products->have_posts()): $products->the_post(); eco_demo_render_card(get_the_ID()); endwhile; wp_reset_postdata(); else: ?><p>Chưa có sản phẩm mẫu.</p><?php endif; ?></div>
    <p class="empty-state">Không tìm thấy sản phẩm phù hợp. Hãy thử từ khóa khác nhé!</p>
    <p class="notice-info" style="margin-top:23px">* Sản phẩm, mô tả và mức giá chỉ phục vụ mục đích minh họa cho bài thực hành.</p>
  </div></section>
<?php elseif (is_page('gioi-thieu')) : ?>
  <section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a> / Giới thiệu</div><span class="eyebrow">Câu chuyện EcoShop</span>
    <h1 class="page-title">Nhỏ gọn trong thiết kế.<br>Tử tế trong trải nghiệm.</h1>
    <p>EcoShop là website giới thiệu sản phẩm kết hợp nền tảng triển khai, quản trị và giám sát hệ thống phần mềm.</p>
  </div></section>
  <section class="section"><div class="container copy-grid">
    <div class="copy-card"><span class="big-symbol">✳</span><div><span class="eyebrow">EcoShop Studio</span><h2>Giao diện hiện đại.<br>Hệ thống đáng tin cậy.</h2><p>Website thực hành – Đề 3</p></div></div>
    <div class="article-copy"><span class="eyebrow">Về dự án</span><h2>Trải nghiệm mua sắm bắt đầu bằng sự rõ ràng.</h2>
      <p>EcoShop mô phỏng website quảng bá các sản phẩm công nghệ, đồ dùng bàn làm việc và tiện ích đời sống. Nội dung trực quan giúp khách truy cập tìm sản phẩm nhanh chóng, xem thông tin chi tiết và gửi liên hệ demo.</p>
      <p>Bên dưới giao diện, WordPress kết nối MySQL; Nginx đóng vai trò reverse proxy. Prometheus và Grafana theo dõi hiệu năng, Loki và Promtail tập trung nhật ký truy cập. Mọi dịch vụ được tổ chức qua Docker Compose.</p>
      <div class="stats"><div class="stat"><strong>06</strong><small>Sản phẩm mẫu</small></div><div class="stat"><strong>03</strong><small>Nhóm sản phẩm</small></div><div class="stat"><strong>100%</strong><small>Giao diện co giãn</small></div></div>
      <div class="tech-chips"><span>WordPress</span><span>MySQL 8</span><span>Nginx</span><span>Grafana</span><span>Prometheus</span><span>Loki</span><span>Docker Compose</span></div>
      <a class="btn btn-primary" style="margin-top:18px" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Khám phá sản phẩm ↗</a>
    </div>
  </div></section>
<?php elseif (is_page('lien-he')) : ?>
  <section class="page-hero"><div class="container"><div class="breadcrumbs"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a> / Liên hệ</div><span class="eyebrow">Kết nối với chúng tôi</span>
    <h1 class="page-title">Có câu hỏi? Hãy để lời nhắn.</h1><p>Điền thông tin demo để kiểm tra chức năng lưu liên hệ trong WordPress.</p>
  </div></section>
  <section class="container contact-layout">
    <div class="contact-visual"><div><span class="eyebrow">EcoShop Support</span><h2>Ở đây để giúp bạn<br>khám phá tốt hơn.</h2><p>Bộ biểu mẫu chỉ sử dụng cho mục đích kiểm thử. Vui lòng không nhập dữ liệu cá nhân thật.</p></div>
      <div><div class="contact-bullet">✉&nbsp; Lời nhắn lưu trong WordPress → Liên hệ demo</div><div class="contact-bullet">☘&nbsp; Dự án sinh viên · Đề 3</div><div class="contact-bullet">⌘&nbsp; Docker Compose / MySQL</div></div>
    </div>
    <div class="contact-form"><h2>Gửi liên hệ demo</h2>
      <?php if(isset($_GET['sent'])): $sent=sanitize_key(wp_unslash($_GET['sent'])); ?>
        <div class="notice <?php echo $sent==='1'?'':'error'; ?>" role="status">
          <?php echo $sent==='1'?'Đã lưu lời nhắn demo thành công.':'Không thể gửi. Vui lòng kiểm tra dữ liệu hoặc thử lại sau.'; ?>
        </div>
      <?php endif; ?>
      <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="eco_demo_contact">
        <?php wp_nonce_field('eco_demo_contact','eco_nonce'); ?>
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label for="eco-name">Họ tên demo *</label><input id="eco-name" name="full_name" maxlength="100" required placeholder="Nguyễn Văn A">
        <label for="eco-email">Email demo *</label><input id="eco-email" name="email" type="email" maxlength="120" required placeholder="demo@example.com">
        <label for="eco-message">Nội dung *</label><textarea id="eco-message" name="message" minlength="8" maxlength="2000" required placeholder="Tôi muốn tìm hiểu về sản phẩm..."></textarea>
        <button class="btn btn-primary" type="submit">Gửi lời nhắn <span aria-hidden="true">↗</span></button>
        <p class="notice-info" style="margin-top:15px">Liên hệ được lưu nội bộ để minh họa kết nối website → database, không gửi email ra ngoài.</p>
      </form>
    </div>
  </section>
<?php else : ?>
<section class="page-hero"><div class="container"><h1 class="page-title"><?php the_title(); ?></h1></div></section>
<div class="container article-copy" style="padding:35px 18px 80px"><?php while(have_posts()):the_post();the_content();endwhile; ?></div>
<?php endif;
get_footer();
