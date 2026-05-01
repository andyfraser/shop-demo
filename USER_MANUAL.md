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
9. [Task Scheduling](#9-task-scheduling)

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
3.  **Initialize the Database:**
    *   Run the migration script to create tables and seed initial data:
        ```bash
        php migrate.php
        ```
4.  **Start the Server:**
    *   Using the PHP built-in server:
        ```bash
        php -S localhost:8080
        ```
    *   Visit `http://localhost:8080` in your browser.

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
*   **Customer Reviews:** Read and submit ratings (1-5 stars) and comments. New reviews are moderated by admins before appearing publicly.
*   **Smart Related Products:** Discovers items using a weighted relevance algorithm (prioritizing shared attributes like Brand/Color and same-category items).
*   **Recently Viewed:** Automatically tracks your last 7 viewed items in a dedicated section at the bottom of the page, sorted with the most recent items on the left.
*   **Inventory Logic:** Out-of-stock products are automatically hidden from the "Featured" homepage list but remain accessible via direct links or category pages.
*   **SEO Friendly URLs:** Account and order detail pages use clean URL structures (e.g., `/account/orders/4`).

### Shopping Experience
*   **Dynamic Cart:** Add, update, or remove items without page reloads using the AJAX-powered cart.
*   **Persistent Cart:** Your items stay in your cart even if you close your browser or switch devices. Cart contents are automatically merged when you log in.
*   **Seamless Checkout:** Logged-in users can choose from their saved address book. Choose from multiple delivery tiers based on order total.
*   **Order Tracking:** Registered customers can view their complete order history and track status (Pending, Confirmed, Shipped, Delivered).
*   **Self-Service Cancellation:** Customers can cancel orders directly while they are in the `pending` status.

### Account Management
*   **Email Verification:** New accounts require verification via a secure token sent to their email.
*   **Address Book:** Manage multiple shipping addresses with identifying labels (e.g., "Home", "Work"). Set a default address for faster checkout.

---

## 3. Admin Panel Guide

Access the management interface at `/admin/`.

### Dashboard
The dashboard provides an immediate overview of your business:
*   **Live Metrics:** Total products, active customers, order count, and total revenue.
*   **Low-Stock Alerts:** Automatically identifies products below the configured threshold.
*   **Recent Orders:** A quick-view list of the latest customer activity.

### Inventory & Reviews
*   **Products:** CRUD operations for items, including image uploads (JPEG, PNG, GIF, WebP up to 5MB), pricing, and "Featured" status.
*   **Categories:** Create complex parent-child relationships with custom icons.
*   **Review Moderation:** Review, approve, or reject customer product ratings and comments.

### Order Fulfillment
*   **Status Workflow:** Transition orders through `Pending` → `Confirmed` → `Shipped` → `Delivered`.
*   **Enhanced History:** View a detailed timeline of all order events, including status updates, return requests, and refund processing.
*   **Action Attribution:** Every status change is logged with the name of the user or administrator who authorized it.
*   **Abandoned Carts:** Automated transactional emails reach out to users who leave items in their carts without completing checkout.
*   **Return Management:** Review and approve or reject customer return requests with optional notes.
*   **Refund Tracking:** Monitor refund status (Partially Refunded, Fully Refunded) and amounts directly within the order detail view.
*   **Inventory Sync:** Stock levels are automatically adjusted when orders are placed, cancelled, or when items are returned and approved.

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
Logs are stored in the `logs/` directory. The primary log file is `logs/app.log`, which contains timestamped entries for system events. If enabled, `logs/recovery.log` tracks cart recovery operations. Both files are automatically rotated daily and subject to the configured retention policy.

### Debug Mode
You can control the verbosity of the logs and the detail of error messages via the `config.php` file. 

*   **Production Mode (`'debug' => false`):** Errors are logged to `logs/app.log`, and users are shown a friendly `500 Internal Server Error` page. Detailed technical information is hidden for security.
*   **Debug Mode (`'debug' => true`):** Detailed error messages, stack traces, and environment information are displayed directly in the browser to assist with development.

### Log Rotation & Retention
Logs are automatically rotated daily by the task scheduler. By default, rotated log files are kept for **30 days**. You can also trigger rotation manually:

```bash
php cli/console.php logs:rotate
```

You can adjust the retention threshold in the `config.php` file:

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

## 9. Task Scheduling

Demoshop includes a centralized task scheduler to handle background operations like abandoned cart recovery. Instead of managing multiple cron jobs, you only need to add a single entry to your server's crontab.

### Setting up the Cron Job
To run the scheduler every minute, add the following line to your server's crontab (replace `/path/to/shop-demo` with your actual project path):

```bash
* * * * * php /path/to/shop-demo/cli/console.php schedule:run >> /dev/null 2>&1
```

### Manual Execution
You can also run specific commands manually via the console:

```bash
# List all available commands
php cli/console.php

# Run a specific command immediately
php cli/console.php recover-carts
```

### How it Works
The scheduler tracks the execution of each task in the `scheduled_tasks` database table. It ensures that tasks registered as `daily` or `hourly` only run once during their respective periods, even if the `schedule:run` command is executed every minute.

---
*For developer support or feature requests, please consult the `README.md`.*
