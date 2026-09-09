<?php
/**
 * Plugin Name: ShopFront Brand Filter
 * Description: Shop / Cigarettes / Vapes 品牌筛选器:注册 product_brand 分类法、给商品打品牌、商品格上方品牌筛选链接(?brand=slug)、查询过滤。
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- 1) 注册 product_brand 分类法 ---------- */
add_action( 'init', function() {
    register_taxonomy( 'product_brand', array( 'product' ), array(
        'labels' => array( 'name' => 'Brands', 'singular_name' => 'Brand' ),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => false,
        'rewrite' => false,
    ) );
} );

/* ---------- 2) 给商品打品牌(按标题关键词;幂等) ---------- */
add_action( 'init', function() {
    if ( get_option( '_pg_brand_mapped' ) === '1' ) return;
    require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
    $brands = array(
        'Marlboro'           => array( 'Marlboro' ),
        'Manchester'         => array( 'Manchester' ),
        'Oscar'              => array( 'Oscar' ),
        'Double Happiness'   => array( 'Double Happiness' ),
        'Winfield'           => array( 'Winfield' ),
        'ESSE'               => array( 'ESSE' ),
        'JPS'                => array( 'JPS' ),
        'Benson & Hedges'    => array( 'Benson & Hedges' ),
        'MAC'                => array( 'MAC' ),
        'Cocopalm'           => array( 'Cocopalm' ),
        'Peony'              => array( 'Peony' ),
        'IGET'               => array( 'IGET' ),
    );
    foreach ( $brands as $brand => $keys ) {
        if ( ! term_exists( $brand, 'product_brand' ) ) {
            wp_insert_term( $brand, 'product_brand' );
        }
    }
    $ps = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ) );
    foreach ( $ps as $pid ) {
        $title = get_the_title( $pid );
        foreach ( $brands as $brand => $keys ) {
            foreach ( $keys as $k ) {
                if ( stripos( $title, $k ) !== false ) {
                    wp_set_object_terms( $pid, $brand, 'product_brand', false );
                    break 2;
                }
            }
        }
    }
    update_option( '_pg_brand_mapped', '1' );
}, 20 );

/* ---------- 3) 查询过滤(?brand=slug) ---------- */
add_action( 'woocommerce_product_query', function( $q ) {
    if ( empty( $_GET['brand'] ) ) return;
    $slugs = array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET['brand'] ) ) ) );
    if ( ! $slugs ) return;
    $q->set( 'tax_query', array_merge( (array) $q->get( 'tax_query' ), array(
        array( 'taxonomy' => 'product_brand', 'field' => 'slug', 'terms' => $slugs, 'operator' => 'IN' ),
    ) ) );
} );

/* ---------- 4) 品牌筛选条(Shop / 分类页商品格上方) ---------- */
add_action( 'woocommerce_before_shop_loop', function() {
    if ( ! ( is_shop() || is_product_taxonomy() ) ) return;

    // 当前上下文的品牌(Shop=全部;分类页=该分类下有的品牌)
    $args = array( 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' );
    if ( is_product_category() ) {
        $term = get_queried_object();
        $args['tax_query'] = array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id ) );
    }
    $ids   = get_posts( $args );
    $terms = array();
    foreach ( $ids as $pid ) {
        foreach ( wp_get_object_terms( $pid, 'product_brand', array( 'fields' => 'names' ) ) as $b ) {
            $terms[ $b ] = true;
        }
    }
    if ( ! $terms ) return;
    $brands = array_keys( $terms );
    sort( $brands, SORT_STRING );

    $current = isset( $_GET['brand'] ) ? sanitize_title( wp_unslash( $_GET['brand'] ) ) : '';
    $base = remove_query_arg( array( 'brand', 'paged', 'product-page' ) );
    echo '<div class="pg-brand-filter">';
    echo '<span class="pg-brand-filter-label">Filter by brand:</span>';
    echo '<a class="pg-brand-link' . ( $current ? '' : ' active' ) . '" href="' . esc_url( $base ) . '">All</a>';
    foreach ( $brands as $b ) {
        $slug = sanitize_title( $b );
        $cls  = ( $current === $slug ) ? ' active' : '';
        echo '<a class="pg-brand-link' . $cls . '" href="' . esc_url( add_query_arg( 'brand', $slug, $base ) ) . '">' . esc_html( $b ) . '</a>';
    }
    echo '</div>';
} );
