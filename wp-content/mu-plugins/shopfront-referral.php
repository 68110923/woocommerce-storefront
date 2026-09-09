<?php
/**
 * Plugin Name: ShopFront Referral & Commission
 * Description: 邀请拉新：通过邀请链接注册的用户下单，推荐人按商品小计的 25% 获得购物金并计入钱包；钱包可在结账时全额支付。
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- 1) 访问邀请链接 ?ref=CODE → 记录到 cookie ---------- */
add_action( 'init', function() {
    if ( ! empty( $_GET['ref'] ) ) {
        $rid = absint( $_GET['ref'] );
        if ( $rid > 0 ) {
            setcookie( 'pg_ref', (string) $rid, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
            $_COOKIE['pg_ref'] = (string) $rid;
        }
    }
}, 0 );

/* ---------- 2) 新用户注册时，绑定邀请关系（防自荐/防重复） ---------- */
add_action( 'user_register', function( $user_id ) {
    if ( empty( $_COOKIE['pg_ref'] ) ) return;
    $referrer = absint( $_COOKIE['pg_ref'] );
    if ( ! $referrer || $referrer === $user_id ) return; // 防自荐
    if ( get_user_meta( $user_id, 'pg_referred_by', true ) ) return; // 已绑定过
    update_user_meta( $user_id, 'pg_referred_by', $referrer );
}, 10, 1 );

/* ---------- 3) 订单完成 → 推荐人得商品小计的 25%，计入钱包 ---------- */
add_action( 'woocommerce_order_status_completed', function( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $buyer = $order->get_customer_id();
    if ( ! $buyer ) return;

    $referrer = absint( get_user_meta( $buyer, 'pg_referred_by', true ) );
    if ( ! $referrer || $referrer === $buyer ) return; // 无推荐人或自荐
    if ( $order->get_meta( '_pg_referral_credited' ) ) return; // 防重复入账

    if ( ! function_exists( 'woo_wallet' ) ) return; // TeraWallet 未启用

    $subtotal   = (float) $order->get_subtotal(); // 商品小计（不含运费/税）
    $commission = round( $subtotal * 0.25, 2 );
    if ( $commission <= 0 ) return;

    // 计入推荐人的钱包（购物金）
    woo_wallet()->wallet->credit( $referrer, $commission, sprintf( 'Referral commission (25%%) for order #%d', $order_id ), array( 'currency' => $order->get_currency() ) );

    // 累计到推荐人
    $total = (float) get_user_meta( $referrer, '_pg_referral_total', true ) + $commission;
    $cnt   = (int) get_user_meta( $referrer, '_pg_referral_count', true ) + 1;
    update_user_meta( $referrer, '_pg_referral_total', $total );
    update_user_meta( $referrer, '_pg_referral_count', $cnt );

    $order->update_meta_data( '_pg_referral_credited', $commission );
    $order->save();
}, 10, 1 );

/* ---------- 4) 会员中心：My Referrals 页签（邀请链接 + 数据） ---------- */
add_action( 'init', function() {
    add_rewrite_endpoint( 'my-referrals', EP_ROOT | EP_PAGES );
    if ( get_option( '_pg_referral_flushed' ) !== '1' ) {
        flush_rewrite_rules( false );
        update_option( '_pg_referral_flushed', '1' );
    }
} );

add_filter( 'woocommerce_account_menu_items', function( $items ) {
    $new = array();
    foreach ( $items as $k => $v ) {
        $new[ $k ] = $v;
        if ( 'orders' === $k ) { $new['my-referrals'] = 'My Referrals'; }
    }
    if ( ! isset( $new['my-referrals'] ) ) { $new['my-referrals'] = 'My Referrals'; }
    return $new;
} );

add_action( 'woocommerce_account_my-referrals_endpoint', function() {
    $uid = get_current_user_id();
    if ( ! $uid ) { echo '<p>Please log in.</p>'; return; }

    $link           = home_url( '/?ref=' . $uid );
    $referred_count = count( get_users( array( 'meta_key' => 'pg_referred_by', 'meta_value' => $uid, 'fields' => 'ID' ) ) );
    $total          = (float) get_user_meta( $uid, '_pg_referral_total', true );
    $bal            = function_exists( 'woo_wallet' ) ? woo_wallet()->wallet->get_wallet_balance( $uid ) : 0;
    ?>
    <div class="pg-referral">
        <h3 class="pg-referral-title">My Referrals</h3>
        <p class="pg-referral-desc">Invite friends &amp; earn. When someone registers through your link and completes an order, you earn <b>25% of their order's product subtotal</b> as shopping credit in your wallet.</p>

        <div class="pg-referral-box">
            <label class="pg-referral-label" for="pg-invite-link">Your unique invite link</label>
            <div class="pg-referral-row">
                <input type="text" readonly id="pg-invite-link" class="pg-referral-input" value="<?php echo esc_attr( $link ); ?>">
                <button type="button" class="pg-referral-copy" data-copytarget="pg-invite-link">Copy link</button>
            </div>
            <p class="pg-referral-hint">Click &ldquo;Copy link&rdquo;, then share it with a friend. We track the reward automatically.</p>
        </div>

        <div class="pg-referral-stats">
            <div class="pg-referral-stat"><span>Friends referred</span><strong><?php echo intval( $referred_count ); ?></strong></div>
            <div class="pg-referral-stat"><span>Commission earned</span><strong><?php echo wc_price( $total ); ?></strong></div>
            <div class="pg-referral-stat"><span>Wallet balance</span><strong><?php echo wc_price( $bal ); ?></strong></div>
        </div>
    </div>
    <?php
} );
