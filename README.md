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
- Product listings with breadcrumbs, related products, and detail pages. **Note:** Out-of-stock products are automatically hidden from the featured homepage list.
- Full-text product search with sorting and configurable pagination (12 / 24 / all)
- Session-based shopping cart with AJAX add/update/remove and live badge counter
- Checkout with saved shipping address pre-fill and dynamic delivery options
- Customer accounts with order history, saved shipping address, and **order cancellation for pending orders**
- User registration with email verification, **resend verification functionality**, and login
- **PSR-3 compliant logging** with file-based output and conditional debug mode
- Privacy-compliant cookie consent banner with persistence logic

**Admin panel** (`/admin/`)
- Dashboard with live stats (products, customers, orders, revenue) and low-stock alerts
- Full CRUD for products (including image upload and featured status), categories, users, delivery options (with minimum order thresholds), and orders
- Order management with status workflow (pending → confirmed → shipped → delivered / cancelled)
- Hierarchical category management with parent/child relationships
- Role-based access control (admin / customer)
- Configurable site settings: name, currency symbol, password policy, rate-limit thresholds, and low-stock threshold for inventory badges and alerts

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

## Testing

The project includes a custom vanilla PHP unit testing framework (no external dependencies).

```bash
php tests/run.php
```

This will run all tests in the `tests/Unit/` directory and report on pass/fail status and assertion counts.

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
├── logs/                   # Application log files (ignored by Git)
│   └── app.log             # Main application log
│
├── config/                 # Application configuration files
│   ├── routes.php          # Route definitions
│   └── services.php        # DI service registrations
│
├── tests/                  # Custom unit testing framework
│   ├── run.php             # CLI test runner
│   ├── TestCase.php        # Base test class with assertions
│   └── Unit/               # Unit test suites
│
├── src/
│   ├── Core/
│   │   ├── Autoloader.php  # PSR-4 style class autoloader (supports App\ and Psr\)
│   │   ├── Container.php   # Dependency Injection container with autowiring
│   │   ├── Database.php    # Multi-driver PDO connection factory + migrations
│   │   ├── FileLogger.php  # PSR-3 compliant file-based logger
│   │   ├── Renderer.php    # Template renderer (injects shared vars, wraps layout)
│   │   ├── Router.php      # HTTP router with middleware and DI support
│   │   └── Validator.php   # Field validation logic
│   │
│   ├── Psr/
│   │   └── Log/            # Standard PSR-3 logging interfaces
│   │
│   ├── Models/             # Data models (User, Product, Order, Category, etc.)
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
│   │   ├── CategoryService.php         # Category hierarchy and CRUD
│   │   ├── DeliveryService.php         # DB-backed delivery options management
│   │   ├── EmailService.php            # Transactional emails (verification, orders)
│   │   ├── OrderService.php            # Order creation and management
│   │   ├── ProductService.php          # Product catalog and search
│   │   ├── SecurityService.php         # CSRF tokens and rate limiting
│   │   ├── SettingsService.php         # DB-backed key/value settings with defaults
│   │   └── UserService.php             # User management and profile updates
│   │
│   └── Helpers.php         # Global helpers: h(), money(), settings(), redirect(), flash(),
│                           #   csrf_field(), csrf_token(), current_user(), is_ajax(),
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
│   ├── 404.php             # Custom 404 error page
│   ├── 500.php             # Custom 500 error page
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

All requests enter through `index.php` (front controller), which bootstraps the **Dependency Injection (DI) Container**, registers the autoloader, defines constants, and dispatches to `src/Core/Router`. Common icon routes (like `/favicon.ico` or `/apple-touch-icon.png`) are explicitly handled to prevent 404 errors. 

**Error Handling:** The application includes a global error and exception handler. In debug mode, detailed errors are displayed. In production, errors are logged to `logs/app.log` and a user-friendly `500.php` template is rendered.

**Dependency Injection:** The application uses a custom `Container` for managing object lifecycles and dependencies. Service registrations are centralized in `config/services.php`. Controllers and services receive their dependencies via their constructors using PHP 8 property promotion. The `Router` uses the container to automatically instantiate controllers with all required dependencies (autowiring).

**Models:** Data is represented by Model classes in `src/Models/`. These classes encapsulate data structure and provide helper methods (e.g., `Order::getStatusBadgeClass()`), moving away from raw associative arrays.

**Rendering:** Controllers fetch data and call `$this->renderer->render('template_name', ['var' => $val])`. The renderer extracts the data array, auto-injects shared vars (`$current_user`, `$cart_count`, `$nav_tree`), and wraps the template with `header.php` / `footer.php`. Admin pages use `adminRender()`.

**Database:** Support for SQLite and MySQL via PDO. The `PDO` instance is registered as a singleton in the DI container. The appropriate schema (`sqlite_schema.sql` or `mysql_schema.sql`) runs automatically on first connection. Additive column migrations also run on init so existing databases are upgraded without data loss.

**Session cart:** Stored in `$_SESSION['cart']` as `[product_id => quantity]`, managed by the instance-based `CartService`.

**Security:** Every POST form includes a CSRF token (`csrf_field()`), verified by `$this->securityService->verifyCsrf()`. Login and registration are rate-limited. Admin routes are protected by `AdminMiddleware`, which also uses DI to access `AuthService`.

---

## Adding a New Route

1. Register the route in `index.php` with `$router->get()` or `$router->post()`.
2. Add the action method to the appropriate controller in `src/Controllers/`. Ensure the controller defines its dependencies in the constructor for autowiring.
3. Create a template in `templates/` and call `$this->renderer->render()` from the controller.

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

