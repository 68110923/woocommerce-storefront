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

/* ---------------- SEO 标题优化:避免品牌名重复/过长 ---------------- */
add_filter( 'document_title_separator', function() { return '|'; } );
add_filter( 'pre_get_document_title', function( $title ) {
    if ( is_front_page() ) {
        return get_bloginfo( 'name' );  // 首页仅保留品牌名 "ShopFront"
    }
    return $title;
} );

/* ---------------- 满150包邮,不满150收9.9:免费配送可用时只保留免费 ---------------- */
add_filter( 'woocommerce_package_rates', function( $rates, $package ) {
    $has_free = false;
    foreach ( (array) $rates as $rate ) { if ( $rate->method_id === 'free_shipping' ) { $has_free = true; break; } }
    if ( $has_free ) {
        foreach ( (array) $rates as $key => $rate ) { if ( $rate->method_id !== 'free_shipping' ) { unset( $rates[ $key ] ); } }
    }
    return $rates;
}, 10, 2 );

function shopfront_enqueue_assets() {
    if ( is_admin() ) return;
    $base = content_url( 'mu-plugins/shopfront-assets' );
    wp_enqueue_style( 'pg-common',  $base . '/common.css',  array(), '8.37' );
    wp_enqueue_style( 'pg-desktop', $base . '/desktop.css', array(), '8.37' );
    wp_enqueue_style( 'pg-tablet',  $base . '/tablet.css',  array(), '8.37' );
    wp_enqueue_style( 'pg-mobile',  $base . '/mobile.css',  array(), '8.37' );
    wp_enqueue_script( 'pg-common',  $base . '/common.js',  array( 'jquery' ), '8.37', true );
    wp_enqueue_script( 'pg-tablet',  $base . '/tablet.js',  array( 'jquery' ), '8.37', true );
    wp_enqueue_script( 'pg-desktop', $base . '/desktop.js', array( 'jquery' ), '8.37', true );
    wp_enqueue_script( 'pg-mobile',  $base . '/mobile.js',  array( 'jquery' ), '8.37', true );
}
add_action( 'wp_enqueue_scripts', 'shopfront_enqueue_assets', 100 );

/* 抽屉购物车数量 AJAX 所需的前端数据(必须在 pg-common 入队之后,否则 handle 未注册 localize 会失败) */
add_action( 'wp_enqueue_scripts', function() {
    if ( is_admin() ) return;
    wp_localize_script( 'pg-common', 'PGCART', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'pg_cart_qty' ),
    ) );
}, 101 );

/* 抽屉购物车数量加减 AJAX */
add_action( 'wp_ajax_pg_cart_qty', 'shopfront_cart_qty_ajax' );
add_action( 'wp_ajax_nopriv_pg_cart_qty', 'shopfront_cart_qty_ajax' );
function shopfront_cart_qty_ajax() {
    check_ajax_referer( 'pg_cart_qty', 'nonce' );
    if ( function_exists( 'wc_load_cart' ) ) { wc_load_cart(); }   // 强制从会话加载购物车
    $key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
    $qty = isset( $_POST['qty'] ) ? absint( $_POST['qty'] ) : 1;
    if ( $key && function_exists( 'WC' ) && WC()->cart ) {
        if ( $qty < 1 ) {
            WC()->cart->remove_cart_item( $key );
        } else {
            WC()->cart->set_quantity( $key, $qty );
        }
        WC()->cart->calculate_totals();
    }
    ob_start();
    shopfront_mini_cart();
    $mini = ob_get_clean();
    wp_send_json( array(
        'mini'  => $mini,
        'count' => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
        'total' => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_subtotal() : '',
    ) );
}

/* 抽屉购物车已改用自定义容器,不由 WooCommerce fragment 替换(避免 replaceWith 把容器移除)。
   角标数量仍由下方的 shopfront_cart_fragment 单独更新。 */

/* 自建抽屉 mini-cart：图片在左 / 标题+单价+数量在右 / 删除在最右侧 */
function shopfront_mini_cart() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) return;
    if ( WC()->cart->is_empty() ) {
        echo '<p class="woocommerce-mini-cart__empty-message">' . esc_html__( 'No products in the cart.', 'woocommerce' ) . '</p>';
        return;
    }
    echo '<ul class="woocommerce-mini-cart cart_list product_list_widget">';
    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product = $cart_item['data'];
        if ( ! $_product || ! $_product->exists() ) continue;
        $name  = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
        $thumb = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
        $price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
        $qty   = (int) $cart_item['quantity'];
        $plink = get_permalink( $_product->get_id() );
        $remove_url = esc_url( wc_get_cart_remove_url( $cart_item_key ) );
        echo '<li class="pg-mc-item" data-cart_item_key="' . esc_attr( $cart_item_key ) . '">';
        echo '<a class="pg-mc-remove" href="' . $remove_url . '" aria-label="Remove" data-cart_item_key="' . esc_attr( $cart_item_key ) . '">&times;</a>';
        echo '<a class="pg-mc-link" href="' . esc_url( $plink ) . '">' . $thumb . '</a>';
        echo '<div class="pg-mc-body">';
        echo '<a class="pg-mc-title" href="' . esc_url( $plink ) . '">' . esc_html( $name ) . '</a>';
        echo '<div class="pg-mc-price">' . wp_kses_post( $price ) . '</div>'; // 单价
        echo '<div class="pg-mc-qty"><button type="button" class="mc-qty-btn mc-minus" aria-label="Decrease">&#8722;</button><span class="mc-qty">' . $qty . '</span><button type="button" class="mc-qty-btn mc-plus" aria-label="Increase">&#43;</button></div>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
    echo '<p class="woocommerce-mini-cart__total"><strong>' . esc_html__( 'Subtotal', 'woocommerce' ) . ':</strong> ' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</p>';
    echo '<div class="woocommerce-mini-cart__buttons">'
       . '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . esc_html__( 'View cart', 'woocommerce' ) . '</a>'
       . '<a href="' . esc_url( wc_get_checkout_url() ) . '" class="button checkout wc-forward">' . esc_html__( 'Checkout', 'woocommerce' ) . '</a>'
       . '</div>';
}


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
      <div class="pg-mini-cart-content"><?php shopfront_mini_cart(); ?></div>
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
            <p style="color:#94a3b8;margin:0;">Melbourne-based store for premium tobacco &amp; vape. Free shipping over <b>AUD $150</b>.</p>
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
        <div class="pg-bottom">
          <span>&copy; 2026 ShopFront. All rights reserved. Melbourne, Victoria, Australia. <a href="mailto:support@example.com">support@example.com</a>.</span>
          <span class="pg-pay">&#127183; AUD &#183; 18+ Only</span>
          <span class="pg-payments" aria-label="Accepted payment methods">
            <span class="pg-pay-chip" title="Visa"><svg viewBox="0 0 32 20" width="30" height="18"><rect width="32" height="20" rx="2.5" fill="#1a1f71"/><text x="16" y="14.5" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="11" font-weight="800" font-style="italic" fill="#fff">VISA</text></svg></span>
            <span class="pg-pay-chip" title="Mastercard"><svg viewBox="0 0 32 20" width="30" height="18"><circle cx="12" cy="10" r="8" fill="#eb001b"/><circle cx="20" cy="10" r="8" fill="#f79e1b"/></svg></span>
            <span class="pg-pay-chip" title="PayPal"><svg viewBox="0 0 32 20" width="30" height="18"><text x="16" y="14" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="11" font-weight="800" fill="#003087">Pay<tspan fill="#009cde">Pal</tspan></text></svg></span>
            <span class="pg-pay-chip" title="Apple Pay"><svg viewBox="0 0 32 20" width="30" height="18"><text x="16" y="14" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="12" fill="#000">&#63743;<tspan font-size="11" font-weight="600"> Pay</tspan></text></svg></span>
            <span class="pg-pay-chip" title="Google Pay"><svg viewBox="0 0 32 20" width="30" height="18"><text x="16" y="14" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="11" font-weight="800"><tspan fill="#4285f4">G</tspan><tspan fill="#5f6368"> Pay</tspan></text></svg></span>
            <span class="pg-pay-chip" title="Stripe"><svg viewBox="0 0 32 20" width="30" height="18"><text x="16" y="14" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="11" font-weight="800" fill="#635bff">stripe</text></svg></span>
          </span>
        </div>
      </div>
    </footer>
    <?php
}
add_action( 'wp_footer', 'shopfront_footer', 20 );

/* 抽屉购物车完整刷新 AJAX(加购后重绘整个抽屉,确保显示全部商品) */
add_action( 'wp_ajax_pg_cart_refresh', 'shopfront_cart_refresh' );
add_action( 'wp_ajax_nopriv_pg_cart_refresh', 'shopfront_cart_refresh' );
function shopfront_cart_refresh() {
    if ( function_exists( 'wc_load_cart' ) ) { wc_load_cart(); }
    check_ajax_referer( 'pg_cart_qty', 'nonce' );
    ob_start();
    shopfront_mini_cart();
    $mini = ob_get_clean();
    wp_send_json( array(
        'mini'  => $mini,
        'count' => function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
    ) );
}
