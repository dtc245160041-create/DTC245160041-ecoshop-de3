<?php
defined('ABSPATH') || exit;
get_header(); ?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="kicker"><span class="pulse"></span> Sản phẩm tiện ích cho sinh hoạt và làm việc</span>
      <h1>Đồ dùng tiện ích.<br><em>Dễ chọn, dễ dùng.</em></h1>
      <p class="hero-lead">Khám phá các mẫu đồ dùng cho học tập, làm việc và sinh hoạt hằng ngày. Thông tin ngắn gọn, giá tham khảo rõ ràng và dễ tìm kiếm.</p>
      <div class="hero-buttons">
        <a class="btn btn-primary" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem sản phẩm <span aria-hidden="true">↗</span></a>
        <a class="btn btn-ghost" href="<?php echo esc_url(eco_demo_navigation('gioi-thieu')); ?>">Giới thiệu cửa hàng →</a>
      </div>
      <div class="hero-proof"><span><strong>6</strong> sản phẩm mẫu</span><i aria-hidden="true"></i><span><strong>3</strong> danh mục</span><i aria-hidden="true"></i><span>Thông tin <strong>dễ tra cứu</strong></span></div>
    </div>
    <div class="hero-art" aria-label="Minh họa sản phẩm EcoShop">
      <span class="hero-art-title">Đèn bàn LED Aura</span>
      <div class="hero-orbit" aria-hidden="true"></div>
      <img class="hero-feature" src="<?php echo esc_url(eco_demo_img('lamp')); ?>" alt="Đèn bàn LED Aura">
      <div class="float-pill left">Góc học tập gọn gàng<small>Đèn bàn có 3 chế độ ánh sáng</small></div>
      
    </div>
  </div>
</section>
<section class="trust-row" aria-label="Giá trị EcoShop"><div class="container trust-grid">
  <div class="trust-item"><div class="trust-icon">✦</div><div><strong>Thiết kế hiện đại</strong><small>Hài hòa với không gian của bạn</small></div></div>
  <div class="trust-item"><div class="trust-icon">♧</div><div><strong>Ưu tiên sự tiện ích</strong><small>Gọn gàng, dễ sử dụng mỗi ngày</small></div></div>
  <div class="trust-item"><div class="trust-icon">♡</div><div><strong>Chọn lựa tinh tế</strong><small>Sản phẩm mẫu đa dạng nhu cầu</small></div></div>
</div></section>
<section class="section" id="featured"><div class="container">
  <div class="section-head"><div><span class="eyebrow">Tuyển chọn cho bạn</span><h2>Sản phẩm nổi bật</h2><p class="section-intro">Xem thông tin, giá tham khảo và đặc điểm của các sản phẩm đang giới thiệu.</p></div>
    <a class="text-link" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem tất cả sản phẩm ↗</a>
  </div>
  <div class="cards-grid">
    <?php $products=eco_demo_query(6); if($products->have_posts()): while($products->have_posts()): $products->the_post(); eco_demo_render_card(get_the_ID()); endwhile; wp_reset_postdata(); else: ?>
      <p>Chưa có sản phẩm demo. Chạy script cài giao diện để tự động tạo sản phẩm mẫu.</p>
    <?php endif; ?>
  </div>
</div></section>
<section class="section soft"><div class="container">
  <div class="section-head"><div><span class="eyebrow">Thông tin rõ ràng</span><h2>Mua sắm dễ tìm hiểu hơn.</h2><p class="section-intro">Danh mục được sắp xếp dễ hiểu, giúp bạn xem và so sánh sản phẩm thuận tiện hơn.</p></div></div>
  <div class="features">
    <article class="feature"><div class="feature-icon">01</div><h3>Thông tin đầy đủ</h3><p>Mỗi sản phẩm có hình minh họa, mức giá tham khảo và mô tả những đặc điểm cơ bản.</p></article>
    <article class="feature"><div class="feature-icon">02</div><h3>Danh mục dễ tìm</h3><p>Lọc theo nhóm công nghệ, góc làm việc hoặc đồ dùng đời sống.</p></article>
    <article class="feature"><div class="feature-icon">03</div><h3>Sử dụng thuận tiện</h3><p>Xem trang web bằng điện thoại hoặc máy tính, không cần đăng ký tài khoản.</p></article>
  </div>
</div></section>
<div class="container cta-wrap"><div class="cta"><div><span class="eyebrow" style="color:#a7dcc0">Bộ sưu tập EcoShop</span><h2>Tìm sản phẩm phù hợp với bạn.</h2><p>Xem chi tiết và mức giá tham khảo của toàn bộ sản phẩm mẫu.</p></div>
  <a class="btn" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem sản phẩm <span aria-hidden="true">↗</span></a></div></div>
<?php get_footer(); ?>
