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
- Product listings with breadcrumbs, detailed attribute-based related products, and detail pages. **Note:** Out-of-stock products are automatically hidden from the featured homepage list.
- **Product Reviews:** Customer rating and comment system with admin moderation workflow.
- **Recently Viewed Products:** Session-based tracking of browsing history on product pages with automatic sorting by time.
- **Smart Recommendations:** A weighted relevance algorithm that prioritizes products based on shared attributes (e.g., Brand, Color) and categories.
- Full-text product search with sorting and configurable pagination (12 / 24 / all)
- **Persistent Shopping Cart:** Database-backed cart that persists across sessions and devices, with automatic merging upon login.
- Checkout with saved shipping address pre-fill and dynamic delivery options
- **Enhanced Address Book:** Customer management of multiple shipping addresses with labels and default selection.
- Customer accounts with order history and **order cancellation for pending orders**
- User registration with email verification, **resend verification functionality**, and login
- **PSR-3 compliant logging** with file-based output and conditional debug mode
- Privacy-compliant cookie consent banner with persistence logic
- **Task Scheduling:** Centralized system for background jobs (e.g., abandoned cart recovery, log rotation) with a single crontab entry and database-backed state tracking. Supports various frequencies:
    - High-frequency: `everyMinute`, `everyFiveMinutes`, `everyFifteenMinutes`, `everyThirtyMinutes`
    - Standard: `hourly`, `twiceDaily`, `daily`, `weekdays`
    - Long-term: `weekly`, `monthly`, `yearly`

**Admin panel** (`/admin/`)
- Dashboard with live stats (products, customers, orders, revenue) and low-stock alerts
- Full CRUD for products (including image upload and featured status), categories, users, delivery options (with minimum order thresholds), and orders
- **Review Moderation:** Approve or reject customer product reviews.
- Order management with status workflow (pending → confirmed → shipped → delivered / cancelled)
- **Detailed Order History:** Comprehensive tracking of all status changes, return requests, and refunds, with clear attribution to the customer or admin who performed the action.
- **Return & Refund Tracking:** Admin visibility into return requests, item-level return details, and automated refund status updates.
- **Abandoned Cart Emails:** Automated transactional emails for users with items left in their carts.
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

1. Copy `config/config.example.php` to `config/config.php`.
2. Update the `config/config.php` settings with your preferred driver (`sqlite` or `mysql`) and credentials.

### SQLite Setup
By default, the application is configured to use SQLite with a database file named `shop.db`. 

1. Run the migrations to create the schema and seed initial data:
   ```bash
   php cli/console.php migrate
   ```

### MySQL / MariaDB Setup
1. Ensure your MySQL server is running.
2. Provide your server host, user, and password in `config/config.php`.
3. The application will automatically create the database if it doesn't exist, but you must run the migrations to apply the schema and seed data:
   ```bash
   php cli/console.php migrate
   ```

---

## Quick Start

```bash
git clone https://github.com/yourname/shop-demo.git
cd shop-demo
cp config/config.example.php config/config.php
php cli/console.php migrate
php -S localhost:8080 index.php
```

Open **http://localhost:8080** in your browser.

---

## Testing

The project includes a custom vanilla PHP unit testing framework (no external dependencies).

```bash
php tests/run.php
```

This will run all tests in the `tests/Unit/` directory and report on pass/fail status and assertion counts. The test suite includes specific tests for verifying the Dependency Injection container's ability to map interfaces to concrete implementations.

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
├── migrate.php             # Deprecated migration runner (wraps cli/console.php)
├── migrations/             # Database migration files
├── shop.db                 # SQLite database (auto-created if using SQLite)
├── logs/                   # Application log files (ignored by Git)
│   └── app.log             # Main application log
│
├── cli/                    # Command-line scripts
│   └── console.php         # Central CLI entry point and task scheduler runner
│
├── config/                 # Application configuration files
│   ├── config.php          # Local configuration (ignored by Git)
│   ├── config.example.php  # Configuration template
│   ├── routes.php          # Route definitions
│   └── services.php        # DI service registrations (Interface to Implementation)
│
├── tests/                  # Custom unit testing framework
│   ├── run.php             # CLI test runner
│   ├── TestCase.php        # Base test class with assertions
│   └── Unit/               # Unit test suites
│
├── src/
│   ├── Core/
│   │   ├── Autoloader.php  # PSR-4 style class autoloader (supports App\ and Psr\)
│   │   ├── Container.php   # DI container with autowiring and interface mapping
│   │   ├── Database.php    # Multi-driver PDO connection factory + migrations
│   │   ├── FileLogger.php  # PSR-3 compliant file-based logger
│   │   ├── Renderer.php    # Template renderer (injects shared vars, wraps layout)
│   │   ├── Router.php      # HTTP router with middleware and DI support
│   │   ├── Scheduler.php   # Centralized task scheduling logic
│   │   ├── Validator.php   # Field validation logic
│   │   └── ViewComposer.php # Shared view data logic
│   │
│   ├── Psr/
│   │   └── Log/            # Standard PSR-3 logging interfaces
│   │
│   ├── Models/             # Data models (User, Product, Order, Category, etc.)
│   │
│   ├── Commands/           # CLI Command implementations
│   │   ├── CommandInterface.php
│   │   ├── MigrateCommand.php
│   │   ├── MigrateRollbackCommand.php
│   │   ├── RecoverCartsCommand.php
│   │   └── RotateLogsCommand.php
│   │
│   ├── Controllers/
│   │   ├── AccountController.php      # Customer account, order history, address
│   │   ├── AdminBackupController.php
│   │   ├── AdminCategoriesController.php
│   │   ├── AdminDashboardController.php
│   │   ├── AdminDeliveryController.php
│   │   ├── AdminOrdersController.php
│   │   ├── AdminProductsController.php
│   │   ├── AdminSettingsController.php
│   │   ├── AdminUsersController.php
│   │   ├── AuthController.php         # Login, register, logout, email verification
│   │   ├── CartController.php         # Cart view, add, update (AJAX + form)
│   │   ├── CheckoutController.php     # Checkout form and order processing
│   │   └── StorefrontController.php   # Home, search, category, product pages
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php          # Requires authenticated session
│   │   └── AdminMiddleware.php         # Requires admin role
│   │
│   ├── Services/
│   │   ├── *ServiceInterface.php       # Contract definitions for all services
│   │   ├── AuthService.php             # Session login / logout / current user
│   │   ├── BackupService.php           # DB backup and restore logic
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
│   └── Helpers.php         # Global helpers
│
├── templates/              # HTML-only templates — no queries or redirects
│   ├── header.php / footer.php
│   ├── home.php / category.php / product.php / products.php / search.php
│   ├── cart.php / checkout.php / order_confirm.php / account.php
│   ├── login.php / register.php / 404.php / 500.php
│   ├── admin/
│   │   ├── header.php / footer.php / dashboard.php
│   │   ├── categories_list.php / categories_form.php
│   │   ├── products_list.php / products_form.php
│   │   ├── delivery_list.php / delivery_form.php
│   │   ├── orders_list.php / orders_detail.php
│   │   ├── users_list.php / users_form.php
│   │   ├── settings.php / backup.php
│   └── emails/
│       ├── layout.php / verification.php / order_confirmation.php / order_status.php
│
└── public/
    ├── css/                # Storefront and Admin styles
    ├── js/                 # AJAX cart and UI logic
    └── images/             # Uploaded and demo product images
```

---

## Architecture

**Dependency Injection & Interfaces:** The application follows the **Dependency Inversion Principle**. Every service (e.g., `ProductService`) has a corresponding interface (e.g., `ProductServiceInterface`). 
- **Centralized Mapping:** Service registrations are managed in `config/services.php`, mapping interfaces to their concrete implementations.
- **Constructor Injection:** Controllers, middlewares, and services receive their dependencies via interfaces in their constructors. 
- **Decoupling:** This design decouples business logic from specific implementations, allowing for easier testing and future extensibility (e.g., swapping a local `FileLogger` for a different PSR-3 implementation).

**Front Controller:** All requests enter through `index.php`, which bootstraps the **Dependency Injection (DI) Container**, registers the autoloader, defines constants, and dispatches to `src/Core/Router`. 

**Error Handling:** The application includes a global error and exception handler. In production, errors are logged to `logs/app.log` and a user-friendly `500.php` template is rendered.

**Models:** Data is represented by Model classes in `src/Models/`. These classes encapsulate data structure and provide helper methods (e.g., `Order::getStatusBadgeClass()`).

**Rendering:** Controllers fetch data and call `$this->renderer->render()`. The renderer auto-injects shared vars (`$current_user`, `$cart_count`, `$nav_tree`).

**Database:** Support for SQLite and MySQL via PDO. The appropriate schema runs automatically on first connection. 

**Security:** Every POST form includes a CSRF token (`csrf_field()`). Login and registration are rate-limited. Admin routes are protected by `AdminMiddleware`.

---

## Adding a New Service

1. Create a new interface in `src/Services/` (e.g., `ReportingServiceInterface.php`).
2. Create a concrete class that implements this interface (e.g., `ReportingService.php`).
3. Register the mapping in `config/services.php`.
4. Type-hint the interface in the constructor of any controller or service that needs it.

---

## Adding a New Route

1. Register the route in `config/routes.php`.
2. Add the action method to the appropriate controller in `src/Controllers/`. Ensure the controller defines its dependencies (via interfaces) in the constructor for autowiring.
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
 |
| **Chrome** | 88+ | Jan 2021 |
| **Edge** | 88+ | Jan 2021 |
| **Firefox** | 84+ | Dec 2020 |
| **Safari** | 14.1+ | Apr 2021 |
| **Opera** | 74+ | Feb 2021 |

**Internet Explorer is not supported.**

---

## No External Dependencies

Do not add Composer packages or any external libraries. Use only PHP 8 built-ins.
