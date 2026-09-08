/* Puffgoods 桌面端 JS (桌面特有交互) */
(function ($) {
  'use strict';
  $(function () {
    // 桌面:页头悬浮购物车 hover 强化
    $('.pg-cart-icon').on('mouseenter', function () { $(this).css('transform', 'scale(1.08)'); });
    $('.pg-cart-icon').on('mouseleave', function () { $(this).css('transform', ''); });
  });
})(jQuery);
