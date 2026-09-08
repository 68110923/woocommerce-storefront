/* Puffgoods 移动端 JS (移动特有交互) */
(function ($) {
  'use strict';
  $(function () {
    // 移动端:点开汉堡时给 body 加状态(便于调试/样式)
    $(document).on('click', '.menu-toggle, .main-header-menu-toggle', function () {
      $('body').toggleClass('pg-mobile-menu-open');
    });
  });
})(jQuery);
