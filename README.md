# Puffgoods — WooCommerce / WordPress Store Frontend

![MIT](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![WordPress](https://img.shields.io/badge/WordPress-7.1-blue?style=flat-square)
![WooCommerce](https://img.shields.io/badge/WooCommerce-11.1-purple?style=flat-square)
![Astra](https://img.shields.io/badge/Theme-Astra-blueviolet?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.0+-777bb4?style=flat-square)
![Frontend](https://img.shields.io/badge/Frontend-HTML%2FCSS%2FJS-orange?style=flat-square)
![Built with ♥](https://img.shields.io/badge/Storefront-Puffgoods-ff69b4?style=flat-square)

> 面向澳大利亚市场的烟草 & 电子烟在线商店前端设计。品牌配色 **绿 + 紫**,风格简约、大气、高级感。基于 **Astra 主题 + WooCommerce**,通过一个 **mu-plugin(站点插件)** 注入整套自定设计系统,不动主题源码,便于升级维护。

> ⚠️ 本仓库只包含**前端/主题定制代码**,不含 WordPress 核心、数据库、`wp-config.php`、上传文件或任何凭据。请勿提交这些。


---

## ✨ 功能亮点

- **统一渐变画布**:全站一整块 绿→紫 渐变背景,从屏幕左到右、顶到底铺满,所有内容在同一背景下,无割裂的白块。
- **全屏内容优先首页**:进入首页第一眼看到核心 —— logo+菜单(吸顶)、品牌标语、**主打产品轮播**(图 + 名称 + 价格 + View Product,左右铺满)。
- **产品轮播短代码** `[pg_products_carousel limit="6"]`:自动轮播、左右箭头、底部圆点,取自最新上架商品。
- **吸顶玻璃页头**:始终可见,滚动后变实色 + 阴影;logo + 菜单 + **购物车(带数量角标)** 同一 flex 行右对齐,与页头融为一体。
- **购物车抽屉**:点击顶部购物车 → 右侧滑出抽屉(不打断浏览);加购产品**飞入购物车**动画。
- **信任徽章 / 分类卡 / 商品网格**:半透明玻璃质感、悬停动效、`display:grid` 控制列数(桌面 4 / 平板 2 / 手机 1)。
- **18+ 年龄确认**(烟类合规)、**toast 反馈**、**滚动进场(IntersectionObserver)**。
- **响应式**:桌面 / 平板(pad) / 手机三端适配;手机端隐藏过大的轮播、优先展示核心。
- **减动效适配** `prefers-reduced-motion`。
- **CSS / JS 分离**:`common` + `desktop` + `tablet` + `mobile`,各自对应文件,便于维护。

---

## 🧱 技术栈

- **WordPress** + **WooCommerce**(托管运行时)
- **Astra 主题**(不修改原主题文件)
- **mu-plugin**(`wp-content/mu-plugins/puffgoods-branding.php`)注入样式/脚本/短代码/页脚
- 原生 **CSS** + 轻量 **jQuery**(`IntersectionObserver` + 少量 DOM 操作,无重动画库)

---

## 📁 仓库结构

```
.
├── README.md                      # 本文件
├── index.html                     # GitHub Pages 项目展示页(仓库首页)
└── wp-content/
    └── mu-plugins/
        ├── puffgoods-branding.php # 设计系统入口:enqueue、短代码、购物车抽屉、页脚
        └── puffgoods-assets/
            ├── common.css         # 设计系统基础:变量、页头、按钮、商品卡、轮播、页脚
            ├── common.js          # 吸顶页头、滚动进场、age-gate、购物车抽屉、加购动效、轮播
            ├── desktop.css        # 桌面端微调
            ├── tablet.css         # 平板端微调
            ├── mobile.css         # 手机端微调
            ├── desktop.js
            ├── tablet.js
            └── mobile.js
```

> 说明:为实现"首页通铺 + 内容优先",我还重写了首页页面内容(post),把首屏包进 `.pg-hero-wrap`,并把信任徽章/分类/商品放到首屏下方的 `.pg-content-after`。这部分是页面内容而非文件,如需迁移可参考 `mu-plugins/puffgoods-branding.php` 里的输出逻辑。

---

## 🚀 使用方式(在 WordPress 站点上)

1. 把 `wp-content/mu-plugins/puffgoods-branding.php` 和 `wp-content/mu-plugins/puffgoods-assets/` 放进目标站点的 `wp-content/mu-plugins/`(mu-plugin 自动生效,无需在后台启用)。
2. 站点需已安装 **WooCommerce** 并开启商品。
3. 首页内容通过区块编辑器写入:
   ```html
   <div class="pg-hero-wrap">
     <div id="pg-bubble">
       <div class="pg-bubble-inner">
         <p class="pg-hero-tagline">…</p>
         <p class="pg-hero-sub">…</p>
         <div class="pg-hero-carousel">[pg_products_carousel limit="6"]</div>
       </div>
     </div>
   </div>
   <div class="pg-content-after"> …信任徽章 / 分类卡 / 商品 / 联系… </div>
   ```
4. 刷新页面即生效。

---

## 🎨 自定义

- **主色/品牌渐变**:改 `common.css` 顶部的 CSS 变量:
  ```css
  :root {
    --pg-green: #16a34a;  --pg-green-d: #0e7a37;
    --pg-purple: #7c3aed; --pg-purple-d: #6d28d9;
    --pg-grad: linear-gradient(135deg, #16a34a, #7c3aed);
  }
  ```
- **首页首屏行为**:首屏为普通全屏封面(A 方案),滚动时整屏向上滚走、露出下方内容,不隐藏任何内容。`#pg-bubble` 的 `min-height:100vh` 控制首屏高度。
- **手机端**:`@media (max-width:600px)` 隐藏产品轮播(`.pg-hero-carousel { display:none }`),优先展示核心信息。

---

## 🔒 安全说明(重要)

- 本仓库**不含** `wp-config.php`、数据库、`wp-content/uploads/`、SMTP/API 密钥、任何账号密码。
- 上线站点涉及 SMTP、支付、代理等凭据,**请勿**写入公开仓库,务必用环境变量 / 排除文件 / `.gitignore` 保护。

---

## 📄 License

- WordPress 核心、Astra 主题、WooCommerce 均为 **GPL** 许可,分发时保留其各自版权与许可证声明。
- 本项目自定义前端代码按 **MIT** 许可发布(`LICENSE`)。欢迎 fork / 参考。
- 参考站点(one-vape.com / fluxaud.com)仅作视觉参考,**不包含**其受版权保护的素材或代码。

---

## 🙏 说明

> 这是一套为指定业务定制的**主题前端代码**,配置与业务数据不在其中。部署、数据库、支付、合规(澳洲烟类 18+)等需由运营者在自己的 WordPress 环境完成。
