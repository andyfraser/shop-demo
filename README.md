# Demo Shop

A demo e-commerce application written in PHP with SQLite. No frameworks, no Composer, no build step — just PHP 8 and a browser.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

**Storefront**
- Hierarchical product categories with dropdown navigation
- Product listings with breadcrumbs, related products, and detail pages
- Full-text product search with sorting and configurable pagination (12 / 24 / all)
- Session-based shopping cart with AJAX add/update/remove and live badge counter
- Checkout with saved shipping address pre-fill
- Customer accounts with order history and saved shipping address
- User registration and login

**Admin panel** (`/admin/`)
- Dashboard with live stats (products, customers, orders, revenue) and low-stock alerts
- Full CRUD for products (including image upload), categories, users, and orders
- Order management with status workflow (pending → confirmed → shipped → delivered / cancelled)
- Hierarchical category management with parent/child relationships
- Role-based access control (admin / customer)
- Configurable site settings: name, currency symbol, password policy, and rate-limit thresholds

---

## Requirements

- PHP 8.0 or higher
- Extensions: `pdo_sqlite` — enabled by default in most PHP installs

On Ubuntu / Debian:

```bash
sudo apt install php php-sqlite3
```

---

## Quick Start

```bash
git clone https://github.com/yourname/shop-demo.git
cd shop-demo
php -S localhost:8080
```

Open **http://localhost:8080** in your browser. The SQLite database (`shop.db`) is created and seeded automatically on first run.

---

## Demo Accounts

| Role     | Email               | Password   |
|----------|---------------------|------------|
| Admin    | `admin@shop.local`  | `password` |
| Customer | `jane@example.com`  | `password` |

To access the admin panel, log in as admin and visit `/admin/` — or navigate there directly and you'll be redirected to login first.

---

## Product Images

Product images are stored in `public/images/`. When adding or editing a product in the admin panel you can upload a JPEG, PNG, GIF, or WebP image (max 5 MB). A placeholder is shown automatically when no image is set.

---

## Project Structure

```
shop-demo/
│
├── index.php               # Front controller — bootstraps app and registers all routes
├── schema.sql              # Database schema and seed data
├── shop.db                 # SQLite database (auto-created on first run)
│
├── src/
│   ├── Core/
│   │   ├── Autoloader.php  # PSR-4 style class autoloader
│   │   ├── Database.php    # SQLite PDO singleton + migrations
│   │   ├── Renderer.php    # Template renderer (injects shared vars, wraps layout)
│   │   ├── Router.php      # HTTP router with middleware support
│   │   └── Validator.php   # Field validation helpers
│   │
│   ├── Controllers/
│   │   ├── StorefrontController.php   # Home, search, category, product pages
│   │   ├── AuthController.php         # Login, register, logout
│   │   ├── CartController.php         # Cart view, add, update (AJAX + form)
│   │   ├── CheckoutController.php     # Checkout form and order processing
│   │   ├── AccountController.php      # Customer account, order history, address
│   │   ├── AdminDashboardController.php
│   │   ├── AdminCategoriesController.php
│   │   ├── AdminProductsController.php
│   │   ├── AdminOrdersController.php
│   │   ├── AdminUsersController.php
│   │   └── AdminSettingsController.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php          # Requires authenticated session
│   │   └── AdminMiddleware.php         # Requires admin role
│   │
│   ├── Services/
│   │   ├── AuthService.php             # Session login / logout / current user
│   │   ├── CartService.php             # Session-based cart operations
│   │   ├── SecurityService.php         # CSRF tokens and rate limiting
│   │   └── SettingsService.php         # DB-backed key/value settings with defaults
│   │
│   └── Helpers.php         # Global helpers: h(), money(), setting(), redirect(), flash(),
│                           #   csrf_field(), current_user(), cart_count(), product_img(),
│                           #   slugify(), get_category_tree(), get_category_flat(), get_breadcrumb()
│
├── templates/              # HTML-only templates — no queries or redirects
│   ├── header.php
│   ├── footer.php
│   ├── home.php
│   ├── category.php
│   ├── product.php
│   ├── search.php
│   ├── cart.php
│   ├── checkout.php
│   ├── order_confirm.php
│   ├── account.php
│   ├── login.php
│   ├── register.php
│   └── admin/
│       ├── header.php / footer.php
│       ├── dashboard.php
│       ├── categories_list.php / categories_form.php
│       ├── products_list.php / products_form.php
│       ├── orders_list.php / orders_detail.php
│       ├── users_list.php / users_form.php
│       └── settings.php
│
└── public/
    ├── css/
    │   ├── shop.css        # Storefront styles (design tokens, responsive layout)
    │   └── admin.css       # Admin panel styles
    ├── js/
    │   └── shop.js         # AJAX cart, toast notifications, dynamic UI
    └── images/             # Uploaded and demo product images
```

---

## Architecture

All requests enter through `index.php` (front controller), which registers the autoloader, defines constants, and dispatches to `src/Core/Router`. The router matches `REQUEST_URI` / `REQUEST_METHOD` against registered routes, runs any middleware, then calls the controller action.

**Rendering:** Controllers fetch data and call `Renderer::render('template_name', ['var' => $val])`. The renderer extracts the data array, auto-injects shared vars (`$current_user`, `$cart_count`, `$nav_tree`), and wraps the template with `header.php` / `footer.php`. Admin pages use `Renderer::adminRender()`.

**Database:** SQLite via PDO. `Database::getConnection()` returns the singleton; the schema runs automatically on first connection. Additive column migrations also run on init so existing databases are upgraded without data loss. A `settings` table stores editable key/value pairs read via `SettingsService`, which falls back to built-in defaults if a key is not yet in the database.

**Session cart:** Stored in `$_SESSION['cart']` as `[product_id => quantity]`, managed entirely by `CartService`.

**Security:** Every POST form includes a CSRF token (`csrf_field()`), verified by `SecurityService::validateCsrf()`. Login and registration are rate-limited (5 attempts / 15 min and 10 attempts / hour respectively). Admin routes are protected by `AdminMiddleware`.

---

## Adding a New Route

1. Register the route in `index.php` with `$router->get()` or `$router->post()`, passing middleware as the third argument if needed.
2. Add the action method to the appropriate controller in `src/Controllers/`.
3. Create a template in `templates/` and call `Renderer::render()` from the controller.

---

## No External Dependencies

Do not add Composer packages or any external libraries. Use only PHP 8 built-ins.
