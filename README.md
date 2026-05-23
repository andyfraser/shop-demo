# Demo Shop

A demo e-commerce application written in PHP with support for SQLite and MySQL. No frameworks, no Composer, no build step — just PHP 8 and a browser.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-Proprietary-orange)

---

## Features

**Storefront**
- Hierarchical product categories with dropdown navigation
- Product listings with breadcrumbs, detailed attribute-based related products, and detail pages. **Note:** Out-of-stock products are automatically hidden from the featured homepage list.
- **Product Reviews:** Customer rating and comment system with admin moderation workflow.
- **Recently Viewed Products:** Session-based tracking of browsing history on product pages with automatic sorting by time.
- **Smart Recommendations:** A weighted relevance algorithm that prioritizes products based on shared attributes (e.g., Brand, Color) and categories.
- Full-text product search with sorting and configurable pagination (12 / 24 / 48 / all)
- **Persistent Shopping Cart:** Database-backed cart that persists across sessions and devices, with automatic merging upon login.
- **Quantity-Based Tiered Pricing:** Bulk discounts (fixed amount off) that apply automatically as quantities increase. Tier prices update dynamically on the product page when different variants are selected.
- **Wishlist:** Authenticated users can save products to a personal wishlist for later viewing. **Wishlist Sharing:** Users can toggle their wishlist to public and share a unique link with others.
- **Product Bundles:** Create product packages that combine multiple items into a single SKU. Inventory is automatically tracked and subtracted from the individual component products when a bundle is sold.
- **Advanced Promotions:** Automatic and code-based discounts (percentage, fixed, free shipping, and BOGO) with real-time AJAX cart updates.
- Checkout with saved shipping address pre-fill and dynamic delivery options.
- **Virtual Products Support:** Full lifecycle for digital goods including secure file downloads, automatically generated and emailable gift cards, software license keys from dynamic inventory pools, user role memberships, and downloadable event tickets.
- **Frictionless Digital Checkout:** Automatically bypasses shipping address inputs and delivery option selection if the cart is 100% digital.
- **Role-Based Delivery Options:** Restrict delivery options to specific customer roles (e.g. VIP-only shipping options) and unlock them dynamically.
- **Secure Payment Integration:** Pluggable payment gateway system with a demo manual payment gateway for checkout processing.
- **Multi-Currency Support:** Customers can switch between different currencies with real-time price conversion based on admin-defined rates.
- **Enhanced Address Book:** Customer management of multiple shipping addresses with labels and default selection.
- Customer accounts with order history and **order cancellation for pending orders**.
- User registration with email verification, **resend verification functionality**, and login.
- **PSR-3 compliant logging** with file-based output and conditional debug mode.
- Privacy-compliant cookie consent banner with persistence logic.
- **Maintenance Mode:** Quickly take the store offline for updates using CLI commands, displaying a dedicated maintenance page to visitors while allowing admin access.
- **Task Scheduling:** Centralized system for background jobs (e.g., abandoned cart recovery, log rotation, image cleanup, background queue processing) with a single crontab entry and database-backed state tracking.
- **Asynchronous Background Queue:** A database-backed job queue for deferred event processing. Event listeners can implement `ShouldQueue` to run asynchronously with support for retries, delays, exponential backoff, and daily CLI-based database cleanup.
- **Timezone Management:** The application strictly uses **UTC** for all internal operations and database storage. Display timezones are configurable via the Admin panel, with a built-in `format_local_time()` helper for localized rendering.


**Admin panel** (`/admin/`)
- Dashboard with live stats (products, customers, orders, revenue) and low-stock alerts.
- Full CRUD for products (including image upload, featured status, and variant-only mode), categories, users, delivery options, and orders.
- **Attributes Management:** Define custom product attributes (e.g., Brand, Color, Material) and manage their values.
- **Database Backup & Restore:** Export the entire database (SQLite or MySQL) and restore from a backup file directly through the interface.
- **Promotions Management:** Create complex discount rules targeting specific products, categories, or entire orders (percentage, fixed, or Buy X Get Y) with date and usage limits.
- **Currency Management:** Manage multiple currencies, set exchange rates, and define the base store currency.
- **Review Moderation:** Approve or reject customer product reviews.
- **Audit Logs:** Comprehensive tracking of all administrative actions (POST requests) and system events, allowing for easy troubleshooting and accountability.
- Order management with status workflow (pending → confirmed → shipped → delivered / cancelled).
- **Detailed Order History:** Comprehensive tracking of all status changes, return requests, and refunds, with clear attribution to the customer or admin who performed the action.
- **Return & Refund Tracking:** Admin visibility into return requests, item-level return details, and automated refund status updates.
- **Abandoned Cart Emails:** Automated transactional emails for users with items left in their carts.
- **Role-Based Access Control:** Manage user roles and permissions (admin / customer).
- Configurable site settings: name, currency symbol, password policy, rate-limit thresholds, and low-stock threshold for inventory badges and alerts.

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

### Database Setup
Run the migrations to create the schema and then seed initial data:
```bash
php cli/console.php migrate
php cli/console.php db:seed
```

---

## Quick Start

```bash
git clone https://github.com/yourname/shop-demo.git
cd shop-demo
cp config/config.example.php config/config.php
php cli/console.php migrate
php cli/console.php db:seed
php -S localhost:8080 -t public
```

Open **http://localhost:8080** in your browser.

---

## Testing

The project includes a custom vanilla PHP unit testing framework (no external dependencies).

```bash
php tests/run.php
```

The test suite covers Core components, Repositories, Services, and Models, including specific tests for verifying the Dependency Injection container's ability to map interfaces to concrete implementations.

---

## Demo Accounts

| Role     | Email               | Password   |
|----------|---------------------|------------|
| Admin    | `admin@shop.local`  | `password` |
| Customer | `jane@example.com`  | `password` |

---

## Project Structure

```
shop-demo/
│
├── cli/                    # Command-line scripts
│   └── console.php         # Central CLI entry point (migrations, seeding, tasks, maintenance)
│
├── config/                 # Application configuration files
│   ├── config.php          # Local configuration (ignored by Git)
│   ├── routes.php          # Route definitions
│   ├── services.php        # DI service registrations (Interface to Implementation)
│   └── services/           # Granular service registration files (commands, core, events, etc.)
│
├── src/
│   ├── Core/
│   │   ├── Autoloader.php  # PSR-4 style class autoloader
│   │   ├── Container.php   # DI container with autowiring and interface mapping
│   │   ├── Database.php    # Multi-driver PDO connection factory
│   │   ├── Events/         # Event dispatcher core and ShouldQueue interface
│   │   ├── Request.php     # HTTP request abstraction
│   │   ├── Response.php    # HTTP response abstraction (HTML, JSON, Redirect)
│   │   ├── Router.php      # HTTP router with middleware and DI support
│   │   └── Scheduler.php   # Centralized task scheduling logic
│   │
│   ├── Commands/           # CLI Command implementations (migrate, seed, cleanup, etc.)
│   │
│   ├── Controllers/        # Request handlers (Account, Admin*, Auth, Cart, Checkout, etc.)
│   │
│   ├── Events/             # Event DTOs (OrderPlaced, UserRegistered, etc.)
│   │
│   ├── Listeners/          # Event listeners (OrderListener, AuthListener, etc.)
│   │
│   ├── Models/             # Data models (Product, Order, Category, Review, Attribute, etc.)
│   │
│   ├── Repositories/       # Data access layer (Interfaces and PDO-based implementations)
│   │
│   ├── Services/           # Business logic layer (Auth, Cart, Order, Promotion, Payment, etc.)
│   │
│   ├── Middleware/         # Request filters (Auth, Admin, Csrf, Guest, Verified)
│   │
│   └── Psr/                # PSR-3 standard logging interfaces
│
├── migrations/             # Database schema migrations
│
├── tests/                  # Custom vanilla PHP testing framework
│
├── templates/              # HTML templates (Storefront, Admin, Emails, Partials)
│
└── public/                 # Static assets (CSS, JS, Images)
```

---

## Architecture

**Dependency Injection & Interfaces:** The application follows the **Dependency Inversion Principle**. 
- **Centralized Mapping:** Service and Repository registrations are managed in `config/services.php`.
- **Constructor Injection:** Controllers, middlewares, and services receive their dependencies via interfaces.
- **Autowiring:** The custom DI Container automatically resolves and injects dependencies based on constructor type-hints.

**Repository Pattern:** A dedicated data access layer decouples business logic from SQL queries. 
- Every entity has a Repository (e.g., `ProductRepository`) that implements a corresponding interface.
- Repositories handle all PDO-based database operations, ensuring that Services remain focused on business rules.

**Service Layer:** Business logic is encapsulated in Service classes. 
- Services coordinate between Repositories, other Services (e.g., `EmailService`, `PaymentService`), and external interfaces.
- **Specialized Domain Services:** To maintain SRP, complex calculations are isolated in dedicated services like `PricingService` (multi-currency and tier-aware pricing) and `VatService` (tax calculation logic).

**Middleware System:** Decouples security and cross-cutting concerns from business logic.
- **`CsrfMiddleware`**: Automatically validates CSRF tokens for all protected POST routes.
- **`AuthMiddleware` / `AdminMiddleware`**: Enforce authentication and role-based access.
- **`VerifiedMiddleware`**: Enforces email verification for sensitive actions.
- **`AuditLogMiddleware`**: Automatically records administrative actions (POST requests) for the audit trail.

**Event System:** A decoupled, event-driven architecture using a central `EventDispatcher`.
- **Decoupled Logic:** Cross-cutting concerns like sending emails, updating stock, or logging audits are handled by Listeners reacting to dispatched Events (e.g., `OrderPlaced`, `UserRegistered`).
- **Extensibility:** New functionality can be added by creating new Listeners without modifying the core business logic.
- **Asynchronous Event Processing:** Listeners can implement the `ShouldQueue` interface to offload time-consuming tasks (like `AsyncDemoListener` or email notifications) from the main request/response cycle. The event is automatically serialized into a database-backed job queue and executed asynchronously by a command-line background worker, with support for automatic retries, retry delays, and exponential backoff.

**Front Controller:** All requests enter through `public/index.php`, which bootstraps the application and dispatches to the router. The application uses a custom `Request` and `Response` system to handle HTTP communication cleanly.

---

## Browser Compatibility

This application uses modern CSS (Grid, Flexbox gap, CSS variables) and JavaScript (Optional chaining).

| Browser | Minimum Version | Release Date |
| :--- | :--- | :--- |
| **Chrome** | 88+ | Jan 2021 |
| **Edge** | 88+ | Jan 2021 |
| **Firefox** | 84+ | Dec 2020 |
| **Safari** | 14.1+ | Apr 2021 |

---

## No External Dependencies

This project is strictly **no-dependency**. Do not add Composer packages or any external libraries. Use only PHP 8 built-ins.
