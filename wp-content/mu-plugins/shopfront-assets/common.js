/* ==========================================================================
   ShopFront 公共 JS(跨端共享)
   --------------------------------------------------------------------------
   Sections:
   01. Sticky header
   02. Scroll reveal (IntersectionObserver)
   03. Toast
   04. Age gate (18+)
   05. Cart drawer (open/close + header-cart placement)
   06. Add-to-cart fly + toast
   07. Cart / checkout quantity steppers
   08. Mini-cart quantity + remove (AJAX)
   09. Product carousel
   10. Init
   ========================================================================== */
(function ($) {
  'use strict';

  /* ------------------------------------------------------------------
   * 01. Sticky header:始终可见,滚动后实色+阴影
   * ---------------------------------------------------------------- */
  /* 首屏大字 logo:滚动时缩小/淡出,像"缩进顶部菜单" */
  function heroLogo() {
    var $l = $('.pg-hero-logo');
    if (!$l.length) return;
    var baseH = 200;
    var onScroll = function () {
      var y = window.scrollY;
      var p = Math.min(y / 380, 1);
      var maxH = Math.max(baseH * (1 - 0.82 * p), 46);
      $l.css('max-height', maxH + 'px');
      $l.css('opacity', (1 - 0.95 * p).toFixed(3));       // 大字logo随滚动淡出,交由菜单logo接住
      $('body').toggleClass('pg-logo-landed', p >= 0.55); // 衔接点:菜单logo淡入
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function stickyHeader() {
    var $h = $('#masthead');
    if (!$h.length) return;
    var onScroll = function () {
      var y = window.scrollY;
      $h.toggleClass('pg-sticky', y > 40);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ------------------------------------------------------------------
   * 02. Scroll reveal:区块进入视口淡入/上移
   * ---------------------------------------------------------------- */
  function scrollReveal() {
    var $els = $('.pg-reveal');
    if (!$els.length) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          var d = parseInt(e.target.getAttribute('data-pg-delay') || '0', 10);
          setTimeout(function () { e.target.classList.add('pg-in'); }, d);
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    $els.each(function () { io.observe(this); });
  }

  /* ------------------------------------------------------------------
   * 03. Toast:右上角滑入提示,自动淡出
   * ---------------------------------------------------------------- */
  function toast(msg, type) {
    var t = $('<div class="pg-toast ' + (type || 'ok') + '"></div>').text(msg);
    $('body').append(t);
    requestAnimationFrame(function () { t.addClass('show'); });
    setTimeout(function () { t.removeClass('show'); }, 2600);
    setTimeout(function () { t.remove(); }, 3000);
  }

  /* ------------------------------------------------------------------
   * 04. Age gate(18+):首次进入平滑弹出,记住选择
   * ---------------------------------------------------------------- */
  function ageGate() {
    var KEY = 'pg_age_ok';
    try { if (localStorage.getItem(KEY) === '1') return; } catch (e) {}
    var $m = $('<div class="pg-agemail-overlay"><div class="pg-agemail"><div class="pg-agemail-emoji">&#127882;</div><h2>Are you 18 or older?</h2><p>This site sells tobacco &amp; e-cigarette products. You must be at least 18 years old to continue.</p><div class="pg-agemail-btns"><button class="pg-agemail-yes" type="button">Yes, I am 18+</button><button class="pg-agemail-no" type="button">No, leave</button></div></div></div>');
    $('body').append($m);
    $m.addClass('show');
    $m.find('.pg-agemail-yes').on('click', function () { try { localStorage.setItem(KEY, '1'); } catch (e) {} $m.removeClass('show'); setTimeout(function () { $m.remove(); }, 300); });
    $m.find('.pg-agemail-no').on('click', function () { window.location.href = 'https://www.google.com'; });
  }

  /* ------------------------------------------------------------------
   * 05. Cart drawer:打开/关闭 + 把页头购物车放进右侧 flex 行(与菜单同级)
   * ---------------------------------------------------------------- */
  /* 从服务器重绘整个抽屉(规避 WooCommerce fragment 竞态,保证显示全部商品) */
  function pgRefreshCart() {
    if (!window.PGCART) return;
    var $c = $('.pg-drawer .pg-mini-cart-content');
    if ($c.length && !$c.find('.pg-mc-item').length) { $c.html('<div class="pg-cart-loading">Loading&#8230;</div>'); }
    $.post(PGCART.ajaxurl, { action: 'pg_cart_refresh', nonce: PGCART.nonce }).done(function (res) {
      if (res.mini) { $c.html(res.mini); }
      if (res.count != null) { $('.pg-header-cart .pg-cart-count').text(res.count); }
    });
  }

  function cartDrawer() {
    var $hc = $('.pg-header-cart');

    function placeCart() {
      if (!$hc.length) return;
      var desktop = window.matchMedia('(min-width:922px)').matches;
      var $right = desktop
        ? $('#ast-desktop-header .site-header-primary-section-right')
        : $('#ast-mobile-header .site-header-primary-section-right');
      if ($right.length && !$right.find('.pg-header-cart').length) { $right.append($hc); }
    }
    placeCart();
    $(window).on('resize', placeCart);

    function openDrawer() { $('.pg-cartmask').addClass('show'); $('.pg-drawer').addClass('open'); $('body').addClass('pg-no-scroll'); pgRefreshCart(); }
    function closeDrawer() { $('.pg-cartmask').removeClass('show'); $('.pg-drawer').removeClass('open'); $('body').removeClass('pg-no-scroll'); }
    $(document).on('click', '.pg-open-cart', function (e) { e.preventDefault(); openDrawer(); });
    $(document).on('click', '.pg-cartmask', closeDrawer);
    $(document).on('click', '.pg-drawer-close', closeDrawer);
  }

  /* ------------------------------------------------------------------
   * 06. Add-to-cart fly + toast(不自动弹抽屉,角标由 WooCommerce fragment 刷新)
   * ---------------------------------------------------------------- */
  function addToCartFx() {
    function fly(src, x, y) {
      var $f = $('<img class="pg-fly" src="' + src + '" alt="">');
      var $ci = $('.pg-header-cart').first();
      var ty = $ci.length ? $ci.offset().top + $ci.height() / 2 : 40;
      var tx = $ci.length ? $ci.offset().left + $ci.width() / 2 : window.innerWidth - 40;
      $('body').append($f);
      $f.css({ left: x, top: y, transform: 'translate(0,0)' });
      requestAnimationFrame(function () { $f.addClass('fly').css({ transform: 'translate(' + (tx - x) + 'px,' + (ty - y) + 'px) scale(0.2)', opacity: .35 }); });
      setTimeout(function () { $f.remove(); $('.pg-header-cart').addClass('bump'); setTimeout(function () { $('.pg-header-cart').removeClass('bump'); }, 400); }, 850);
    }
    $(document).on('click', '.add_to_cart_button, .single_add_to_cart_button, a.add_to_cart_button', function () {
      var $img = $(this).closest('li.product').find('img').first();
      var src = $img.attr('src') || '';
      var r = this.getBoundingClientRect();
      if (src) fly(src, r.left + window.scrollX, r.top + window.scrollY);
      toast('Added to cart', 'ok');
    });
    /* 每次加购后,强制重绘整个抽屉(确保连续加购显示全部商品) */
    $(document.body).on('added_to_cart', function () {
      setTimeout(pgRefreshCart, 800);
    });
  }

  /* ------------------------------------------------------------------
   * 07. Cart / checkout quantity steppers(add ± 到 .quantity .qty)
   * ---------------------------------------------------------------- */
  function quantitySteppers() {
    $('.quantity').each(function () {
      var $q = $(this);
      if ($q.find('.qty-btn').length || !$q.find('.qty').length) return;
      $('<button type="button" class="qty-btn qty-minus" aria-label="Decrease">&#8722;</button>').prependTo($q);
      $('<button type="button" class="qty-btn qty-plus" aria-label="Increase">&#43;</button>').appendTo($q);
    });
    $(document).on('click', '.qty-btn', function () {
      var $q = $(this).closest('.quantity');
      var $input = $q.find('.qty');
      var val = parseFloat($input.val()) || 1;
      var step = parseFloat($input.attr('step')) || 1;
      var min = parseFloat($input.attr('min')) || 1;
      var max = parseFloat($input.attr('max'));
      if ($(this).hasClass('qty-plus')) { val += step; } else { val -= step; }
      if (val < min) { val = min; }
      if (!isNaN(max) && val > max) { val = max; }
      $input.val(val).trigger('change');
    });
  }

  /* ------------------------------------------------------------------
   * 08. Mini-cart quantity + remove(AJAX 更新抽屉/角标;数量为0自动删)
   * ---------------------------------------------------------------- */
  function miniCartQty() {
    function pgAjaxQty(key, qty) {
      $.post(PGCART.ajaxurl, { action: 'pg_cart_qty', nonce: PGCART.nonce, key: key, qty: qty }).done(function (res) {
        if (res.mini) { $('.pg-drawer .pg-mini-cart-content').html(res.mini); }
        if (res.count != null) { $('.pg-header-cart .pg-cart-count').text(res.count); }
        $(document.body).trigger('pg_cart_updated');
      });
    }
    $(document).on('click', '.mc-qty-btn', function () {
      var $btn = $(this), $li = $btn.closest('.pg-mc-item');
      var key = $li.attr('data-cart_item_key') || '';
      if (!key || !window.PGCART) return;
      var cur = parseInt($li.find('.mc-qty').text(), 10) || 1;
      var qty = $btn.hasClass('mc-plus') ? cur + 1 : cur - 1;
      if (qty < 0) qty = 0;
      $li.find('.mc-qty').text(Math.max(1, qty));
      pgAjaxQty(key, qty);
    });
    $(document).on('click', '.pg-mc-remove', function (e) {
      e.preventDefault();
      var key = $(this).attr('data-cart_item_key') || $(this).closest('.pg-mc-item').attr('data-cart_item_key');
      if (!key || !window.PGCART) return;
      pgAjaxQty(key, 0);
    });
  }

  /* ------------------------------------------------------------------
   * 09. Product carousel(自动轮播 + 箭头 + 圆点)
   * ---------------------------------------------------------------- */
  function productsCarousel() {
    var $c = $('.pg-carousel'); if (!$c.length) return;
    var slides = $c.find('.pg-carousel-slide'), idx = 0, timer;
    var dots = $c.find('.pg-carousel-dots');
    slides.each(function (i) { $('<span>').addClass(i === 0 ? 'active' : '').on('click', function () { go(i); }).appendTo(dots); });
    function go(i) { idx = (i + slides.length) % slides.length; slides.removeClass('active').eq(idx).addClass('active'); dots.children().removeClass('active').eq(idx).addClass('active'); restart(); }
    function restart() { clearInterval(timer); timer = setInterval(function () { go(idx + 1); }, 4200); }
    $c.find('.pg-carousel-next').on('click', function () { go(idx + 1); });
    $c.find('.pg-carousel-prev').on('click', function () { go(idx - 1); });
    restart();
  }

  /* ------------------------------------------------------------------
   * 09b. Copy-to-clipboard(邀请链接一键复制;Clipboard API + 兼容回退)
   * ---------------------------------------------------------------- */
  function copyLinks() {
    function fallbackCopy(t) {
      var ta = document.createElement('textarea');
      ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(ta);
    }
    $(document).on('click', '[data-copytarget]', function () {
      var $btn = $(this);
      var input = document.getElementById($btn.data('copytarget'));
      var text = input ? input.value : '';
      var done = function () {
        var old = $btn.text();
        $btn.addClass('copied').text('Copied!');
        setTimeout(function () { $btn.removeClass('copied').text(old); }, 2000);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text); done(); });
      } else { fallbackCopy(text); done(); }
    });
  }

  /* ------------------------------------------------------------------
   * 10. Init
   * ---------------------------------------------------------------- */
  $(function () {
    stickyHeader();
    heroLogo();
    scrollReveal();
    ageGate();
    cartDrawer();
    addToCartFx();
    quantitySteppers();
    miniCartQty();
    copyLinks();
    productsCarousel();
  });
})(jQuery);
