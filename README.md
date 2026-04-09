# Demo Shop

A demo e-commerce application written in PHP with support for SQLite and MySQL. No frameworks, no Composer, no build step — just PHP 8 and a browser.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

**Storefront**
- Hierarchical product categories with dropdown navigation
- Product listings with breadcrumbs, related products, and detail pages
- Full-text product search with sorting and configurable pagination (12 / 24 / all)
- Session-based shopping cart with AJAX add/update/remove and live badge counter
- Checkout with saved shipping address pre-fill and dynamic delivery options
- Customer accounts with order history and saved shipping address
- User registration with email verification and login

**Admin panel** (`/admin/`)
- Dashboard with live stats (products, customers, orders, revenue) and low-stock alerts
- Full CRUD for products (including image upload and featured status), categories, users, delivery options (with minimum order thresholds), and orders
- Order management with status workflow (pending → confirmed → shipped → delivered / cancelled)
- Hierarchical category management with parent/child relationships
- Role-based access control (admin / customer)
- Configurable site settings: name, currency symbol, password policy, and rate-limit thresholds

---

## Requirements

- PHP 8.0 or higher
- Extensions: `pdo_sqlite` (for SQLite) or `pdo_mysql` (for MySQL)
- A local mail server or `mail()` support (for verification and order emails)

---

## Configuration

The application uses a `config.php` file for database and site settings. 

1. Copy `config.example.php` to `config.php`.
2. Update the `db` settings with your preferred driver (`sqlite` or `mysql`) and credentials.

### SQLite Setup
By default, the application is configured to use SQLite with a database file named `shop.db`. This file will be created and seeded automatically on the first run.

### MySQL / MariaDB Setup
1. Ensure your MySQL server is running.
2. Provide your server host, user, and password in `config.php`.
3. The application will automatically create the database (if it doesn't exist), apply the schema, and seed the initial data.

---

## Quick Start

```bash
git clone https://github.com/yourname/shop-demo.git
cd shop-demo
cp config.example.php config.php
php -S localhost:8080
```

Open **http://localhost:8080** in your browser.

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
├── config.php              # Local configuration (ignored by Git)
├── config.example.php      # Configuration template
├── sqlite_schema.sql       # SQLite database schema and seed data
├── mysql_schema.sql        # MySQL database schema and seed data
├── shop.db                 # SQLite database (auto-created if using SQLite)
│
├── src/
│   ├── Core/
│   │   ├── Autoloader.php  # PSR-4 style class autoloader
│   │   ├── Database.php    # Multi-driver PDO singleton + migrations
│   │   ├── Renderer.php    # Template renderer (injects shared vars, wraps layout)
│   │   ├── Router.php      # HTTP router with middleware support
│   │   └── Validator.php   # Field validation helpers
│   │
│   ├── Controllers/
│   │   ├── StorefrontController.php   # Home, search, category, product pages
│   │   ├── AuthController.php         # Login, register, logout, email verification
│   │   ├── CartController.php         # Cart view, add, update (AJAX + form)
│   │   ├── CheckoutController.php     # Checkout form and order processing
│   │   ├── AccountController.php      # Customer account, order history, address
│   │   ├── AdminDashboardController.php
│   │   ├── AdminCategoriesController.php
│   │   ├── AdminProductsController.php
│   │   ├── AdminOrdersController.php
│   │   ├── AdminUsersController.php
│   │   ├── AdminSettingsController.php
│   │   └── AdminDeliveryController.php
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php          # Requires authenticated session
│   │   └── AdminMiddleware.php         # Requires admin role
│   │
│   ├── Services/
│   │   ├── AuthService.php             # Session login / logout / current user
│   │   ├── CartService.php             # Session-based cart operations
│   │   ├── DeliveryService.php         # DB-backed delivery options management
│   │   ├── EmailService.php            # Transactional emails (verification, orders)
│   │   ├── SecurityService.php         # CSRF tokens and rate limiting
│   │   └── SettingsService.php         # DB-backed key/value settings with defaults
│   │
│   └── Helpers.php         # Global helpers: h(), money(), setting(), redirect(), flash(),
│                           #   csrf_field(), csrf_token(), current_user(), is_ajax(), is_new_product(),
│                           #   product_img(), slugify(), get_category_tree(), get_category_flat(), get_breadcrumb()
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
│       ├── delivery_list.php / delivery_form.php
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

**Database:** Support for SQLite and MySQL via PDO. `Database::getConnection()` returns the singleton; the appropriate schema (`sqlite_schema.sql` or `mysql_schema.sql`) runs automatically on first connection. Additive column migrations also run on init so existing databases are upgraded without data loss. A `settings` table stores editable key/value pairs read via `SettingsService`, which falls back to built-in defaults if a key is not yet in the database.

**Session cart:** Stored in `$_SESSION['cart']` as `[product_id => quantity]`, managed entirely by `CartService`.

**Security:** Every POST form includes a CSRF token (`csrf_field()`), verified by `SecurityService::validateCsrf()`. Login and registration are rate-limited (5 attempts / 15 min and 10 attempts / hour respectively). Admin routes are protected by `AdminMiddleware`.

---

## Adding a New Route

1. Register the route in `index.php` with `$router->get()` or `$router->post()`, passing middleware as the third argument if needed.
2. Add the action method to the appropriate controller in `src/Controllers/`.
3. Create a template in `templates/` and call `Renderer::render()` from the controller.

---

## Browser Compatibility

This application uses modern CSS (Grid, Flexbox gap, CSS variables, `aspect-ratio`) and JavaScript (Optional chaining `?.`).

| Browser | Minimum Version | Release Date |
| :--- | :--- | :--- |
| **Chrome** | 88+ | Jan 2021 |
| **Edge** | 88+ | Jan 2021 |
| **Firefox** | 84+ | Dec 2020 |
| **Safari** | 14.1+ | Apr 2021 |
| **Opera** | 74+ | Feb 2021 |

**Internet Explorer is not supported.**

---

## No External Dependencies

Do not add Composer packages or any external libraries. Use only PHP 8 built-ins.

