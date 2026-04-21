# Demoshop User Manual

Welcome to **Demoshop**, a lightweight, high-performance e-commerce demonstration application. Built with vanilla PHP 8, Demoshop is designed for simplicity, speed, and educational clarity, following a "no external dependencies" philosophy.

---

## Table of Contents
1. [Getting Started](#1-getting-started)
2. [Storefront Features](#2-storefront-features)
3. [Admin Panel Guide](#3-admin-panel-guide)
4. [Configuration & Settings](#4-configuration--settings)
5. [Technical Architecture](#5-technical-architecture)
6. [Security & Privacy](#6-security--privacy)
7. [Running Tests](#7-running-tests)

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

## 5. Technical Architecture

### Design Philosophy
*   **Vanilla PHP 8:** No frameworks (Laravel/Symfony) or package managers (Composer).
*   **MVC Pattern:** Separation of concerns between `Controllers`, `Services`, and `Core` logic.
*   **Front Controller:** All traffic routes through `index.php`.

### Directory Structure Highlights
*   `src/Core/`: Foundation classes (Router, Database, Renderer).
*   `src/Services/`: Business logic (Auth, Cart, Email, Security).
*   `templates/`: Pure PHP/HTML view files.
*   `public/`: Assets (CSS, JS, Images).

### Database Migrations
Demoshop handles updates automatically. `src/Core/Database.php` contains a `migrations()` method that applies additive schema changes (like new columns) on-the-fly, ensuring the application stays up-to-date without manual SQL execution.

---

## 6. Security & Privacy

### Security Measures
*   **CSRF Protection:** All state-changing forms require a unique session token.
*   **Rate Limiting:** Protects login and registration routes against brute-force attacks.
*   **Secure Sessions:** Automatic session regeneration and role-based access control (RBAC).

### Privacy Compliance
*   **Cookie Consent:** A built-in, persistence-aware banner handles user consent for non-essential cookies.
*   **Input Sanitization:** Rigorous use of `h()` for HTML escaping and PDO prepared statements for database queries.

---

## 7. Running Tests

Demoshop includes a custom-built unit testing framework that requires no external dependencies. Developers can verify the integrity of core logic by running the test suite from the command line.

### Executing Tests
To run the full test suite, execute the following command from the project root:
```bash
php tests/run.php
```

### Framework Features
*   **Automatic Discovery:** The runner automatically finds all `*Test.php` files in the `tests/Unit/` directory.
*   **Isolation:** Each test method is executed with a fresh environment via the `setUp()` method.
*   **Detailed Reporting:** The suite reports total tests, assertions, and detailed failure messages with file and line references.

---
*For developer support or feature requests, please consult the `README.md` or contact the system administrator.*
