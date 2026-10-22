<?php
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post();
$id=get_the_ID();
$price=(int)get_post_meta($id,'eco_price',true);
$old=(int)get_post_meta($id,'eco_old_price',true);
$art=get_post_meta($id,'eco_art',true);
$category=get_post_meta($id,'eco_category',true);
$features=get_post_meta($id,'eco_features',true);
$image=get_the_post_thumbnail_url($id, 'large');
if (!$image) $image=eco_demo_img($art ?: 'lamp');
?>
<section class="detail"><div class="container">
  <div class="breadcrumbs"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a> / <a href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Sản phẩm</a> / <?php the_title(); ?></div>
  <div class="detail-grid">
    <div class="detail-art"><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"></div>
    <div class="detail-copy">
      <span class="kicker"><span class="pulse"></span> <?php echo esc_html($category); ?></span>
      <h1 class="page-title"><?php the_title(); ?></h1>
      <p class="detail-desc"><?php echo esc_html(get_the_excerpt()); ?></p>
      <div class="detail-price"><?php echo esc_html(eco_demo_money($price)); ?><?php if($old): ?><del><?php echo esc_html(eco_demo_money($old)); ?></del><?php endif; ?></div>
      <h3 style="margin:25px 0 0">Điểm nổi bật</h3>
      <ul class="check-list">
        <?php if(is_array($features)): foreach($features as $item): ?><li><?php echo esc_html($item); ?></li><?php endforeach; endif; ?>
      </ul>
      <?php if (trim(get_the_content()) !== '') : ?>
        <div class="detail-description">
          <h3>Mô tả sản phẩm</h3>
          <?php the_content(); ?>
        </div>
      <?php endif; ?>
      <p class="notice-info">* Giá chỉ mang tính minh họa. Giỏ hàng và thanh toán khi nhận hàng đều là bản thử nghiệm, không thu tiền hoặc giao hàng thật.</p>
      <div class="eco-detail-action"><?php eco_cart_add_button($id, 'Thêm vào giỏ hàng'); ?></div>
      <a class="eco-back-link" href="<?php echo esc_url(eco_cart_url()); ?>">Xem giỏ hàng →</a>
      <a style="margin-left:15px;color:#26674c;font-weight:700;font-size:13px" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">← Quay lại bộ sưu tập</a>
    </div>
  </div>
</div></section>
<?php endwhile; get_footer(); ?>
