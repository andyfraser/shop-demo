# Demoshop User Manual

Welcome to **Demoshop**, a lightweight, high-performance e-commerce demonstration application. Built with vanilla PHP 8, Demoshop is designed for simplicity, speed, and educational clarity, following a "no external dependencies" philosophy.

---

## Table of Contents
1. [Getting Started](#1-getting-started)
2. [Storefront Features](#2-storefront-features)
3. [Admin Panel Guide](#3-admin-panel-guide)
4. [Configuration & Settings](#4-configuration--settings)
5. [Logging & Debugging](#5-logging--debugging)
6. [Technical Architecture](#6-technical-architecture)
7. [Security & Privacy](#7-security--privacy)
8. [Running Tests](#8-running-tests)

---

## 1. Getting Started

### System Requirements
*   **PHP:** Version 8.0 or higher.
*   **Database:** SQLite 3 (via `pdo_sqlite`) or MySQL 8.0+ (via `pdo_mysql`).
*   **Mail Server:** A local mail server or functional `mail()` support for verification and order emails.
*   **Browser:** Modern versions of Chrome, Edge, Firefox, or Safari (IE is not supported).

### Installation
1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/your-repo/shop-demo.git
    cd shop-demo
    ```
2.  **Configure the Environment:**
    *   Copy `config.example.php` to `config.php`.
    *   Edit `config.php` to select your database driver (`sqlite` or `mysql`) and provide credentials.
3.  **Start the Server:**
    *   Using the PHP built-in server:
        ```bash
        php -S localhost:8080
        ```
    *   Visit `http://localhost:8080` in your browser. The database and seed data will be initialized automatically.

### Demo Accounts
| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@shop.local` | `password` |
| **Customer** | `jane@example.com` | `password` |

---

## 2. Storefront Features

### Browsing & Search
*   **Hierarchical Navigation:** Explore products through a multi-tier category menu.
*   **Advanced Search:** Use the search bar to find products. Sort results by price, name, or date.
*   **Product Details:** View high-quality images, descriptions, inventory status, and related products.
*   **Inventory Logic:** Out-of-stock products are automatically hidden from the "Featured" homepage list but remain accessible via direct links or category pages.
*   **SEO Friendly URLs:** Account and order detail pages use clean URL structures (e.g., `/account/orders/4`).

### Shopping Experience
*   **Dynamic Cart:** Add, update, or remove items without page reloads using the AJAX-powered cart.
*   **Seamless Checkout:** Logged-in users benefit from pre-filled shipping addresses. Choose from multiple delivery tiers based on order total.
*   **Order Tracking:** Registered customers can view their complete order history and track status (Pending, Confirmed, Shipped, Delivered).
*   **Self-Service Cancellation:** Customers can cancel orders directly while they are in the `pending` status.

### Account Management
*   **Email Verification:** New accounts require verification via a secure token sent to their email.
*   **Profile Updates:** Manage shipping addresses and account details from the "My Account" dashboard.

---

## 3. Admin Panel Guide

Access the management interface at `/admin/`.

### Dashboard
The dashboard provides an immediate overview of your business:
*   **Live Metrics:** Total products, active customers, order count, and total revenue.
*   **Low-Stock Alerts:** Automatically identifies products below the configured threshold.
*   **Recent Orders:** A quick-view list of the latest customer activity.

### Inventory Management
*   **Products:** CRUD operations for items, including image uploads (JPEG, PNG, GIF, WebP up to 5MB), pricing, and "Featured" status.
*   **Categories:** Create complex parent-child relationships with custom icons.

### Order Fulfillment
*   **Status Workflow:** Transition orders through `Pending` → `Confirmed` → `Shipped` → `Delivered`.
*   **Inventory Sync:** Stock levels are automatically adjusted when orders are placed or cancelled.

### User & Delivery Management
*   **Role Control:** Manage users and assign `Admin` or `Customer` roles.
*   **Delivery Tiers:** Configure shipping options with specific costs and "Free Shipping" thresholds.

---

## 4. Configuration & Settings

Demoshop stores its core settings in the database, manageable via **Admin > Settings**.

| Setting | Description | Default |
| :--- | :--- | :--- |
| `site_name` | Name displayed in headers and emails. | `Demo\|shop` |
| `currency_symbol` | The symbol used for all pricing. | `£` |
| `low_stock_threshold` | When to trigger alerts/badges. | `10` |
| `password_min_length` | Minimum characters for user passwords. | `6` |
| `login_max_attempts` | Rate limiting for login security. | `5` |

---

## 5. Logging & Debugging

Demoshop features a built-in PSR-3 compliant logging system to help monitor application activity and troubleshoot issues.

### Log Files
Logs are stored in the `logs/` directory. The primary log file is `logs/app.log`. This file contains timestamped entries for various system events, such as 404 errors or security alerts.

### Debug Mode
You can control the verbosity of the logs and the detail of error messages via the `config.php` file. 

*   **Production Mode (`'debug' => false`):** Errors are logged to `logs/app.log`, and users are shown a friendly `500 Internal Server Error` page. Detailed technical information is hidden for security.
*   **Debug Mode (`'debug' => true`):** Detailed error messages, stack traces, and environment information are displayed directly in the browser to assist with development.

### Log Rotation & Retention
Logs are automatically rotated daily. By default, rotated log files are kept for **30 days**. You can adjust this threshold in the `config.php` file:

```php
'app' => [
    'debug' => false,
    'log_retention_days' => 30, // Adjust this value as needed
],
```

### PSR-3 Compliance
The logging system follows the PHP Standard Recommendation for logging (PSR-3). Developers can inject `\Psr\Log\LoggerInterface` into any service or controller via the constructor to start logging events immediately.

---

## 6. Technical Architecture

### Design Philosophy
*   **Vanilla PHP 8:** Zero external dependencies (No Laravel, Symfony, or Composer).
*   **Dependency Inversion:** Use of Interfaces for all service logic to ensure decoupling and follow the SOLID principles.
*   **DI Container:** Custom container (`App\Core\Container`) with autowiring and interface mapping support. Service registrations are centralized in `config/services.php`.
*   **MVC Pattern:** Strict separation of Controllers, Services, Models, and Views.
*   **Front Controller:** All traffic routes through `index.php`, which initializes the DI system and sets up global error handling.

### Directory Structure Highlights
*   `src/Core/`: Foundation classes including the `Container`, `Router`, `Database`, and `Renderer`.
*   `src/Models/`: Data objects (User, Product, Order, etc.) that represent core business entities.
*   `src/Services/`: Interface-driven business logic (Auth, Cart, Category, Email, Order, Product, Security, Settings, User).
*   `config/`: Route and Service (Interface to Implementation) definitions.
*   `templates/`: Pure PHP/HTML view files, utilizing Model objects for data representation.

### Database Migrations
Demoshop uses a migration system to manage database schema and seed data.
*   **Applying Migrations:** To apply new updates, run `php migrate.php` from the project root.
*   **Rollback:** To undo the last migration, run `php migrate.php --rollback`.
*   **Files:** Migration logic is stored in the `migrations/` directory.

---

## 7. Security & Privacy

### Security Measures
*   **CSRF Protection:** All state-changing forms require a unique session token, verified by `SecurityService`.
*   **Rate Limiting:** Protects login and registration routes against brute-force attacks by tracking attempts in the `rate_limits` table.
*   **Secure Sessions:** Automatic session regeneration and role-based access control (RBAC).
*   **Input Handling:** Rigorous use of `h()` for HTML escaping and PDO prepared statements for database queries to prevent XSS and SQL injection.

### Privacy Compliance
*   **Cookie Consent:** A built-in, persistence-aware banner handles user consent for non-essential cookies.
*   **Email Communications:** All transactional emails are managed through `EmailService` and use native PHP `mail()` functionality.

---

## 8. Running Tests

Demoshop includes a custom-built unit testing framework that requires no external dependencies. Developers can verify the integrity of core logic by running the test suite from the command line.

### Executing Tests
To run the full test suite, execute the following command from the project root:
```bash
php tests/run.php
```

### Framework Features
*   **Automatic Discovery:** The runner automatically finds all `*Test.php` files in the `tests/Unit/` directory.
*   **Interface Testing:** Specific tests verify that the DI Container correctly maps interfaces to implementations.
*   **Service Testing:** Unit tests for all services verify logic via their interface contracts.
*   **Isolation:** `setUp()` ensures each test runs in a clean environment.
*   **Detailed Reporting:** The suite reports total tests, assertions, and detailed failure messages with file and line references.

---
*For developer support or feature requests, please consult the `README.md`.*
