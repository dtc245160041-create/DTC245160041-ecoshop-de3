</main>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a class="logo" href="<?php echo esc_url(home_url('/')); ?>"><span class="logo-mark">e</span>eco<span class="logo-dot">shop.</span></a>
        <p class="footer-copy">EcoShop giới thiệu các sản phẩm mẫu dành cho học tập, làm việc và sinh hoạt hằng ngày.</p>
        <span class="eyebrow" style="color:#91d2ac">Thông tin và giá mang tính minh họa.</span>
      </div>
      <div><h3 class="footer-heading">Khám phá</h3><div class="footer-links">
        <a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a>
        <a href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Sản phẩm</a>
        <a href="<?php echo esc_url(eco_demo_navigation('gioi-thieu')); ?>">Giới thiệu</a>
        <a href="<?php echo esc_url(eco_demo_navigation('lien-he')); ?>">Liên hệ</a>
      </div></div>
      <div><h3 class="footer-heading">Thông tin demo</h3><div class="footer-links">
        <span>WordPress + MySQL</span><span>Docker Compose</span><span>Grafana · Prometheus</span><span>Loki · Promtail</span>
        <span>Website học tập · Không phải cửa hàng thật</span>
      </div></div>
    </div>
    <div class="footer-bottom"><span>© <?php echo esc_html(date('Y')); ?> EcoShop · Dự án môn Triển khai & Quản trị Hệ thống Phần mềm</span><span>Giao diện demo · Đề 3</span></div>
  </div>
</footer>
<?php wp_footer(); ?>
</body></html>
