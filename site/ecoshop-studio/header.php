<?php defined('ABSPATH') || exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Đi tới nội dung</a>
<div class="top-strip">EcoShop – Website giới thiệu sản phẩm</div>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="EcoShop - Trang chủ">
      <span class="logo-mark">e</span><span class="logo-word">eco<span class="logo-dot">shop.</span></span>
    </a>
    <nav class="navigation" id="eco-menu" aria-label="Menu chính">
      <a <?php echo is_front_page() ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a>
      <a <?php echo is_page('san-pham') || is_singular('eco_product') ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Sản phẩm</a>
      <a <?php echo is_page('gioi-thieu') ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(eco_demo_navigation('gioi-thieu')); ?>">Giới thiệu</a>
      <a <?php echo is_page('lien-he') ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(eco_demo_navigation('lien-he')); ?>">Liên hệ</a>
      <a <?php echo is_page('gio-hang') ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(eco_cart_url()); ?>">Giỏ hàng (<?php echo esc_html(eco_cart_count()); ?>)</a>
    </nav>
    <a class="header-action" href="<?php echo esc_url(eco_demo_navigation('san-pham')); ?>">Khám phá sản phẩm <span aria-hidden="true">↗</span></a>
    <button class="mobile-menu-btn" type="button" aria-controls="eco-menu" aria-expanded="false" aria-label="Mở menu">☰</button>
  </div>
</header>
<main id="main">
