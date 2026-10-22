<?php
defined('ABSPATH') || exit;
get_header(); ?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="kicker"><span class="pulse"></span> Đồ dùng tinh tế · Cuộc sống xanh</span>
      <h1>Những điều nhỏ,<br><em>trải nghiệm lớn.</em></h1>
      <p class="hero-lead">EcoShop chọn lọc các sản phẩm hiện đại, tiện ích và đẹp mắt để biến những khoảnh khắc thường ngày trở nên dễ chịu hơn.</p>
      <div class="hero-buttons">
        <a class="btn btn-primary" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Khám phá bộ sưu tập <span aria-hidden="true">↗</span></a>
        <a class="btn btn-ghost" href="<?php echo esc_url(eco_demo_navigation('gioi-thieu')); ?>">Về EcoShop →</a>
      </div>
      <div class="hero-proof"><span><strong>06</strong> sản phẩm demo</span><i aria-hidden="true"></i><span><strong>03</strong> nhóm sản phẩm</span><i aria-hidden="true"></i><span>Giao diện <strong>responsive</strong></span></div>
    </div>
    <div class="hero-art" aria-label="Minh họa sản phẩm EcoShop">
      <span class="hero-art-title">A space to feel good.</span>
      <div class="hero-orbit" aria-hidden="true"></div>
      <img class="hero-feature" src="<?php echo esc_url(eco_demo_img('lamp')); ?>" alt="Đèn bàn LED Aura">
      <div class="float-pill left"><span class="stars" aria-hidden="true">★★★★★</span><br>Sự tiện lợi trong từng chi tiết<small>Thiết kế cho cuộc sống hằng ngày</small></div>
      <div class="float-pill right">🌿&nbsp; Eco picks<small>Gợi ý cho góc làm việc</small></div>
    </div>
  </div>
</section>
<section class="trust-row" aria-label="Giá trị EcoShop"><div class="container trust-grid">
  <div class="trust-item"><div class="trust-icon">✦</div><div><strong>Thiết kế hiện đại</strong><small>Hài hòa với không gian của bạn</small></div></div>
  <div class="trust-item"><div class="trust-icon">♧</div><div><strong>Ưu tiên sự tiện ích</strong><small>Gọn gàng, dễ sử dụng mỗi ngày</small></div></div>
  <div class="trust-item"><div class="trust-icon">♡</div><div><strong>Chọn lựa tinh tế</strong><small>Sản phẩm mẫu đa dạng nhu cầu</small></div></div>
</div></section>
<section class="section" id="featured"><div class="container">
  <div class="section-head"><div><span class="eyebrow">Tuyển chọn cho bạn</span><h2>Sản phẩm nổi bật</h2><p class="section-intro">Từ góc làm việc đến những đồ dùng cá nhân, khám phá các sản phẩm mẫu của EcoShop.</p></div>
    <a class="text-link" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem tất cả sản phẩm ↗</a>
  </div>
  <div class="cards-grid">
    <?php $products=eco_demo_query(6); if($products->have_posts()): while($products->have_posts()): $products->the_post(); eco_demo_render_card(get_the_ID()); endwhile; wp_reset_postdata(); else: ?>
      <p>Chưa có sản phẩm demo. Chạy script cài giao diện để tự động tạo sản phẩm mẫu.</p>
    <?php endif; ?>
  </div>
</div></section>
<section class="section soft"><div class="container">
  <div class="section-head"><div><span class="eyebrow">Tại sao là EcoShop?</span><h2>Ít phức tạp.<br>Nhiều cảm hứng.</h2><p class="section-intro">Một nơi để tìm kiếm các gợi ý đơn giản nhưng tạo nên khác biệt trong đời sống hằng ngày.</p></div></div>
  <div class="features">
    <article class="feature"><div class="feature-icon">✨</div><h3>Thẩm mỹ tối giản</h3><p>Giao diện và sản phẩm hướng đến vẻ đẹp tinh tế, dễ phối hợp với nhiều phong cách.</p></article>
    <article class="feature"><div class="feature-icon">☘️</div><h3>Hữu ích mỗi ngày</h3><p>Những món đồ gần gũi, phù hợp không gian học tập, làm việc và sinh hoạt.</p></article>
    <article class="feature"><div class="feature-icon">💡</div><h3>Khám phá dễ dàng</h3><p>Danh mục rõ ràng, chi tiết trực quan và bộ lọc tìm kiếm nhanh trên mọi thiết bị.</p></article>
  </div>
</div></section>
<div class="container cta-wrap"><div class="cta"><div><span class="eyebrow" style="color:#a7dcc0">Đến lúc làm mới góc của bạn</span><h2>Bắt đầu từ một lựa chọn nhỏ.</h2><p>Khám phá trọn bộ sản phẩm demo EcoShop ngay hôm nay.</p></div>
  <a class="btn" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Xem sản phẩm <span aria-hidden="true">↗</span></a></div></div>
<?php get_footer(); ?>
