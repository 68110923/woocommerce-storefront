/* Puffgoods 公共 JS — 跨端共享函数 */
(function ($) {
  'use strict';

  /* 吸顶页头:始终可见,滚动后实色+阴影 */
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

  /* 滚动进场 */
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

  /* Toast */
  function toast(msg, type) {
    var t = $('<div class="pg-toast ' + (type || 'ok') + '"></div>').text(msg);
    $('body').append(t);
    requestAnimationFrame(function () { t.addClass('show'); });
    setTimeout(function () { t.removeClass('show'); }, 2600);
    setTimeout(function () { t.remove(); }, 3000);
  }

  /* 18+ age gate */
  function ageGate() {
    var KEY = 'pg_age_ok';
    try { if (localStorage.getItem(KEY) === '1') return; } catch (e) {}
    var $m = $('<div class="pg-agemail-overlay"><div class="pg-agemail"><div class="pg-agemail-emoji">&#127882;</div><h2>Are you 18 or older?</h2><p>This site sells tobacco &amp; e-cigarette products. You must be at least 18 years old to continue.</p><div class="pg-agemail-btns"><button class="pg-agemail-yes" type="button">Yes, I am 18+</button><button class="pg-agemail-no" type="button">No, leave</button></div></div></div>');
    $('body').append($m);
    $m.addClass('show');
    $m.find('.pg-agemail-yes').on('click', function () { try { localStorage.setItem(KEY, '1'); } catch (e) {} $m.removeClass('show'); setTimeout(function () { $m.remove(); }, 300); });
    $m.find('.pg-agemail-no').on('click', function () { window.location.href = 'https://www.google.com'; });
  }

  /* 购物车抽屉 */
  function cartDrawer() {
    /* 购物车移进页头右侧 flex 行,与菜单/汉堡同一级(不再悬浮/覆盖) */
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
    $(document).on('click', '.pg-open-cart', function (e) { e.preventDefault(); openDrawer(); });
    $(document).on('click', '.pg-cartmask', closeDrawer);
    $(document).on('click', '.pg-drawer-close', closeDrawer);
    function openDrawer() { $('.pg-cartmask').addClass('show'); $('.pg-drawer').addClass('open'); $('body').addClass('pg-no-scroll'); }
    function closeDrawer() { $('.pg-cartmask').removeClass('show'); $('.pg-drawer').removeClass('open'); $('body').removeClass('pg-no-scroll'); }
  }

  /* 加购飞入 + 抽屉 + toast */
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
    jQuery(document.body).on('added_to_cart', function () {
      setTimeout(function () { $('.pg-cartmask').addClass('show'); $('.pg-drawer').addClass('open'); $('body').addClass('pg-no-scroll'); }, 500);
    });
  }

  /* init */
  
  /* 产品轮播 */
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

  $(function () { stickyHeader(); scrollReveal(); ageGate(); cartDrawer(); addToCartFx(); productsCarousel(); });
})(jQuery);
