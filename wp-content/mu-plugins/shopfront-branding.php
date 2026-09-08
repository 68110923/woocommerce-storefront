<?php
/**
 * Plugin Name: ShopFront Frontend Design
 * Description: ShopFront 前台设计(公共/桌面/移动 css+js 分离)与购物车抽屉、页脚。
 * Version: 3.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------------- 加载 assets(公共/桌面/移动 css+js) ---------------- */

/* ---------------- 给前端首页加 body class ---------------- */
add_filter( 'body_class', function( $classes ) {
    if ( is_front_page() ) { $classes[] = 'pg-front'; }
    return $classes;
} );

function shopfront_enqueue_assets() {
    if ( is_admin() ) return;
    $base = content_url( 'mu-plugins/shopfront-assets' );
    wp_enqueue_style( 'pg-common',  $base . '/common.css',  array(), '5.5' );
    wp_enqueue_style( 'pg-desktop', $base . '/desktop.css', array(), '5.5' );
    wp_enqueue_style( 'pg-tablet',  $base . '/tablet.css',  array(), '5.5' );
    wp_enqueue_style( 'pg-mobile',  $base . '/mobile.css',  array(), '5.5' );
    wp_enqueue_script( 'pg-common',  $base . '/common.js',  array( 'jquery' ), '5.5', true );
    wp_enqueue_script( 'pg-tablet',  $base . '/tablet.js',  array( 'jquery' ), '5.5', true );
    wp_enqueue_script( 'pg-desktop', $base . '/desktop.js', array( 'jquery' ), '5.5', true );
    wp_enqueue_script( 'pg-mobile',  $base . '/mobile.js',  array( 'jquery' ), '5.5', true );
}
add_action( 'wp_enqueue_scripts', 'shopfront_enqueue_assets' );


/* ---------------- 产品轮播短代码 ---------------- */
function shopfront_products_carousel( $atts ) {
    $atts = shortcode_atts( array( 'limit' => 8 ), $atts );
    $q = new WP_Query( array( 'post_type' => 'product', 'posts_per_page' => (int)$atts['limit'], 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ) );
    if ( ! $q->have_posts() ) return '';
    $out = '<div class="pg-carousel"><div class="pg-carousel-track">';
    $n = 0;
    while ( $q->have_posts() ) {
        $q->the_post();
        $id = get_the_ID();
        $p = wc_get_product( $id );
        $img = get_the_post_thumbnail_url( $id, 'large' );
        if ( ! $img ) $img = wc_placeholder_img_src();
        $price = $p ? $p->get_price_html() : '';
        $name = get_the_title();
        $link = get_permalink( $id );
        $out .= '<div class="pg-carousel-slide' . ( $n === 0 ? ' active' : '' ) . '">'
              . '<div class="pg-carousel-img"><img loading="lazy" src="' . esc_url( $img ) . '" alt="' . esc_attr( $name ) . '"></div>'
              . '<div class="pg-carousel-info"><h3 class="pg-carousel-name">' . esc_html( $name ) . '</h3>'
              . '<div class="pg-carousel-price">' . $price . '</div>'
              . '<a class="pg-carousel-btn" href="' . esc_url( $link ) . '">View Product</a></div>'
              . '</div>';
        $n++;
    }
    wp_reset_postdata();
    $out .= '</div>';
    $out .= '<button class="pg-carousel-prev" type="button" aria-label="Previous">&#10094;</button>';
    $out .= '<button class="pg-carousel-next" type="button" aria-label="Next">&#10095;</button>';
    $out .= '<div class="pg-carousel-dots"></div>';
    $out .= '</div>';
    return $out;
}
add_shortcode( 'pg_products_carousel', 'shopfront_products_carousel' );

/* ---------------- 页脚:购物车抽屉 + 顶部页头购物车按钮 ---------------- */
function shopfront_footer_drawer() {
    if ( is_admin() ) return;
    ?>
    <div class="pg-cartmask"></div>
    <div class="pg-drawer">
      <button class="pg-drawer-close" type="button">&times;</button>
      <div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
    </div>
    <button class="pg-header-cart pg-open-cart" type="button" aria-label="Cart">
      <span class="pg-cart-ico">&#128722;</span>
      <span class="pg-cart-count"><?php echo ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0; ?></span>
    </button>
    <?php
}
add_action( 'wp_footer', 'shopfront_footer_drawer' );

/* 顶部购物车数量角标随加购实时更新(WooCommerce fragments) */
function shopfront_cart_fragment( $fragments ) {
    $count = ( function_exists( 'WC' ) && WC()->cart ) ? (int) WC()->cart->get_cart_contents_count() : 0;
    $fragments['span.pg-cart-count'] = '<span class="pg-cart-count">' . esc_html( $count ) . '</span>';
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'shopfront_cart_fragment' );

/* ---------------- 自定义页脚 ---------------- */
function shopfront_footer() {
    if ( is_admin() ) return;
    ?>
    <footer class="pg-footer">
      <div class="pg-container">
        <div class="pg-grid">
          <div>
            <h4>ShopFront</h4>
            <p style="color:#94a3b8;margin:0;">Melbourne-based store for premium tobacco &amp; vape. Free shipping over <b>AUD $80</b>.</p>
          </div>
          <div>
            <h4>Shop</h4>
            <ul>
              <li><a href="/product-category/cigarettes/">Cigarettes</a></li>
              <li><a href="/product-category/e-cigarettes-vapes/">E-Cigarettes &amp; Vapes</a></li>
              <li><a href="/shop/">All Products</a></li>
            </ul>
          </div>
          <div>
            <h4>Support</h4>
            <ul>
              <li><a href="/shipping-policy/">Shipping Policy</a></li>
              <li><a href="/about/">About Us</a></li>
              <li><a href="/contact-us/">Contact Us</a></li>
            </ul>
          </div>
          <div>
            <h4>Legal</h4>
            <ul>
              <li><a href="/refund_returns/">Returns</a></li>
              <li><a href="/privacy-policy/">Privacy</a></li>
              <li><a href="/terms-and-conditions/">Terms</a></li>
            </ul>
          </div>
        </div>
        <div class="pg-payments">
          <span>VISA</span><span>Mastercard</span><span>PayPal</span><span>Stripe</span><span>Apple Pay</span><span>Google Pay</span>
        </div>
        <div class="pg-bottom">
          <span>&copy; 2026 ShopFront. All rights reserved. Melbourne, Victoria, Australia. <a href="mailto:hello@example.com">hello@example.com</a>.</span>
          <span class="pg-pay">&#127183; AUD &#183; 18+ Only</span>
        </div>
      </div>
    </footer>
    <?php
}
add_action( 'wp_footer', 'shopfront_footer', 20 );
