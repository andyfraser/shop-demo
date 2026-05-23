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
10. [Asynchronous Queue System](#10-asynchronous-queue-system)
11. [Virtual Products & Role-Restricted Delivery](#11-virtual-products--role-restricted-delivery)

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
    *   Copy `config/config.example.php` to `config/config.php`.
    *   Edit `config/config.php` to select your database driver (`sqlite` or `mysql`) and provide credentials.
3.  **Initialize the Database:**
    *   Run the migration script to create tables and seed initial data:
        ```bash
        php cli/console.php migrate
        php cli/console.php db:seed
        ```
4.  **Start the Server:**
    *   Using the PHP built-in server:
        ```bash
        php -S localhost:8080 -t public
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
*   **Multi-Currency Switcher:** Toggle between available currencies via the dropdown in the header. Prices across the store, including cart and checkout, will automatically convert based on current exchange rates.
*   **Promotions & Discounts:** Real-time visibility of active deals via badges on listings and callouts on product pages. Supports both automatic application and manual promo codes. 
    *   **Promotion Stacking:** Benefit from multiple offers at once (e.g., Free Shipping + 10% OFF coupon).
    *   **Tiered Rewards:** Dynamic "Spend more, save more" discounts (e.g., 10% off over $50, 20% off over $100).
    *   **URL Application:** Visiting a promotion URL (e.g., `/promotion/CODE`) automatically applies the code to your cart.
*   **SEO Friendly URLs:** Account and order detail pages use clean URL structures (e.g., `/account/orders/4`).

### Shopping Experience
*   **Dynamic Cart:** Add, update, or remove items without page reloads using the AJAX-powered cart.
*   **Persistent Cart:** Your items stay in your cart even if you close your browser or switch devices. Cart contents are automatically merged when you log in.
*   **Wishlist & Sharing:** Save products you're interested in for later. Authenticated users can manage their wishlist from a dedicated page.
    *   **Privacy Control:** Toggle your wishlist between "Private" and "Public".
    *   **Social Sharing:** When public, a unique sharing URL is generated, allowing you to share your wishlist with friends and family.
*   **Seamless Checkout:** Logged-in users can choose from their saved address book. Choose from multiple delivery tiers based on order total.
*   **Secure Payment Integration:** Pluggable payment gateway system with a demo manual payment gateway for checkout processing.
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
*   **Audit Trail:** A preview of the most recent administrative actions logged by the system.

### Inventory & Reviews
*   **Products:** CRUD operations for items, including image uploads (JPEG, PNG, GIF, WebP up to 5MB), pricing, and "Featured" status. 
    *   **Force Variant Mode:** Restricts customers to purchasing only specific variants (e.g., Size/Color) instead of the base product.
    *   **Product Bundles:** Create composite products by selecting multiple individual items. When a bundle is purchased, the system automatically deducts stock from each component product.
*   **Attributes Management:** Define custom product attributes (e.g., Brand, Color, Material) and manage their values. These are used for smart recommendations and product details.
*   **Quantity Tiers:** Implement bulk quantity discounts (e.g., "Buy 10, save £1.00 each"). 
    *   **Logic:** Tiers are defined as a fixed discount amount subtracted from the base or variant price.
    *   **Variant Support:** Tier prices update dynamically on the storefront based on the selected product variant.
*   **Categories:** Create complex parent-child relationships with custom icons.
*   **Currencies:** Manage the store's supported currencies. Define exchange rates relative to the base currency and toggle visibility for customers.
*   **Promotions Management:** Design complex marketing campaigns with ease:
    *   **Types:** Percentage, fixed amount, free shipping, or "Buy X Get Y" (BOGO) discounts.
    *   **Advanced Targeting:** Support for specific products, categories, or entire orders with granular **exclusion rules** (e.g., "All Categories except Electronics").
    *   **Tiered Thresholds:** Create multi-level rewards within a single promotion.
    *   **Usage Control:** Set global usage limits, **per-user limits**, and target specific user roles (e.g., "VIP Only" or "First-time Customers").
    *   **Prioritization:** Assign priority scores to control which automatic promotions take precedence.
    *   **Multi-Code Issuance:** Create multiple unique codes (e.g., for different influencers) that all link to the same underlying promotion logic.
    *   **Stackability:** Explicitly control whether a promotion can be used in conjunction with other offers.
*   **Review Moderation:** Review, approve, or reject customer product ratings and comments.

### Order Fulfillment & Accountability
*   **Status Workflow:** Transition orders through `Pending` → `Confirmed` → `Shipped` → `Delivered`.
*   **Enhanced History:** View a detailed timeline of all order events, including status updates, return requests, and refund processing. Includes a full breakdown of every applied promotion and its individual discount amount.
*   **Audit Logs:** A dedicated section to review all administrative actions (POST requests). Each log entry includes the timestamp, user, action performed, and relevant entity IDs.
*   **Action Attribution:** Every status change is logged with the name of the user or administrator who authorized it.
*   **Abandoned Carts:** Automated transactional emails reach out to users who leave items in their carts without completing checkout.
*   **Return Management:** Review and approve or reject customer return requests with optional notes.
*   **Refund Tracking:** Monitor refund status (Partially Refunded, Fully Refunded) and amounts directly within the order detail view.
*   **Inventory Sync:** Stock levels are automatically adjusted when orders are placed, cancelled, or when items are returned and approved.

### User & Delivery Management
*   **Role Control:** Manage users and assign `Admin` or `Customer` roles.
*   **Delivery Tiers:** Configure shipping options with specific costs and "Free Shipping" thresholds.
*   **Backup & Restore:** Safeguard your data by exporting the entire database to your local machine. Restore your shop's state by uploading a previous backup file.

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
| `queue_cleanup_completed_hours` | Hours to retain completed background jobs in history. | `24` |
| `queue_cleanup_failed_days` | Days to retain failed background jobs in history before pruning. | `7` |

---

## 5. Logging & Debugging

Demoshop features a built-in PSR-3 compliant logging system to help monitor application activity and troubleshoot issues.

### Log Files
Logs are stored in the `logs/` directory:
*   `logs/app.log`: Contains timestamped entries for general system events and activity (configured via `log_path`).
*   `logs/error.log`: Contains detailed logs for PHP errors, warnings, and uncaught exceptions (configured via `error_log_path`).
*   `logs/recovery.log`: If enabled, tracks cart recovery operations.

All log files are automatically rotated daily and subject to the configured retention policy.

### Debug Mode
You can control the verbosity of the logs and the detail of error messages via the `config.php` file. 

*   **Production Mode (`'debug' => false`):** Errors and exceptions are logged to `logs/error.log`, and users are shown a friendly `500 Internal Server Error` page. Detailed technical information is hidden for security.
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
*   **Dependency Inversion:** Use of Interfaces for all service and repository logic to ensure decoupling and follow the SOLID principles.
*   **Repository Pattern:** A dedicated data access layer that decouples business logic from SQL queries. Every entity has a Repository that implements a corresponding interface.
*   **DI Container:** Custom container (`App\Core\Container`) with autowiring and interface mapping support. Service registrations are centralized in `config/services.php`.
*   **Event-Driven Architecture:** Decouples core business logic from side effects. For example, when an order is placed, an `OrderPlaced` event is dispatched, which triggers the `OrderListener` to send emails and the `InventoryListener` to update stock levels.
*   **MVC Pattern:** Strict separation of Controllers, Services, Repositories, Models, and Views.
*   **Front Controller:** All traffic routes through `public/index.php`, which initializes the DI system and sets up global error handling.

### Directory Structure Highlights
*   `src/Core/`: Foundation classes including the `Container`, `Router`, `Database`, `Request`, `Response`, and `Renderer`.
*   `src/Events/` & `src/Listeners/`: Decoupled event-driven system components.
*   `src/Models/`: Data objects (User, Product, Order, etc.) that represent core business entities.
*   `src/Repositories/`: Data access layer with interface-driven PDO implementations.
*   `src/Services/`: Interface-driven business logic layer.
*   `config/`: Route and Service definitions, including granular configuration in the `services/` subdirectory.
*   `templates/`: Pure PHP/HTML view files, utilizing Model objects for data representation.

### Database Migrations
Demoshop uses a migration system to manage database schema and seed data.
*   **Applying Migrations:** To apply new updates, run `php cli/console.php migrate` from the project root.
*   **Rollback:** To undo the last migration, run `php cli/console.php migrate:rollback`.
*   **Files:** Migration logic is stored in the `migrations/` directory.

---

## 7. Security & Privacy

### Security Measures
*   **Middleware-Based Protection:** All routes are protected by a centralized middleware system that enforces authentication, role-based access, and security policies.
*   **CSRF Protection:** All state-changing forms require a unique session token. Verification is automatically enforced via `CsrfMiddleware` for all protected POST routes.
*   **Rate Limiting:** Protects login and registration routes against brute-force attacks by tracking attempts in the `rate_limits` table, managed by `SecurityService`.
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

### Manual Execution & Control
You can also run specific commands manually or control the scheduler via the console:

```bash
# List all available commands
php cli/console.php

# Pause/Resume the entire scheduler
php cli/console.php schedule:pause
php cli/console.php schedule:resume

# Maintenance Mode
php cli/console.php maintenance:down        # Take the store offline
php cli/console.php maintenance:up          # Bring the store back online

# Run a specific command immediately
php cli/console.php recover-carts
php cli/console.php images:cleanup
php cli/console.php logs:rotate
php cli/console.php queue:work              # Process pending background jobs
php cli/console.php queue:cleanup           # Clean up completed/failed jobs from queue
```

### How it Works
The scheduler tracks the execution of each task in the `scheduled_tasks` database table. It ensures that tasks registered with a specific frequency only run once during their respective periods, even if the `schedule:run` command is executed every minute.

If the scheduler is paused via `schedule:pause`, it will skip all tasks and log a notification until `schedule:resume` is called.

**Maintenance Mode:** When the store is "down", all non-admin visitors are redirected to a maintenance page. Admins can still access the site to perform updates or tests. This state is tracked via a `storage/framework/down` file (or similar, depending on implementation).

Common tasks include:
*   **`recover-carts`**: Sends email reminders for abandoned carts (Daily).
*   **`logs:rotate`**: Rotates and compresses application logs daily using a memory-efficient chunked stream reader (Daily).
*   **`images:cleanup`**: Removes orphaned images that are no longer referenced by any product (Weekly).
*   **`queue:work`**: Processes pending background jobs in the database queue (Every Minute).
*   **`queue:cleanup`**: Purges completed and failed background jobs based on Admin retention settings (Daily).

Supported frequencies include:
*   **High-Frequency:** `everyMinute`, `everyFiveMinutes`, `everyFifteenMinutes`, `everyThirtyMinutes`
*   **Standard:** `hourly`, `twiceDaily` (every 12 hours), `daily`, `weekdays` (Mon-Fri)
*   **Long-Term:** `weekly`, `monthly`, `yearly`

---

## 10. Asynchronous Queue System

Demoshop features a database-backed asynchronous background job queue. This architecture decouples slow, resource-heavy operations (e.g. sending transactional emails, synchronizing external APIs, or complex data updates) from the main web request cycle, resulting in maximum storefront responsiveness.

### Decoupled Events & Queueing
The asynchronous queue is fully integrated with Demoshop's event system:
- **`ShouldQueue` Interface:** Any event listener can implement the `App\Core\Events\ShouldQueue` interface.
- **Automatic Serialization:** When an event is dispatched, the `EventDispatcher` inspects the listener. If it implements `ShouldQueue`, the dispatcher serializes the event payload and writes it directly to the `jobs` database table as a pending job, instead of executing it synchronously.
- **Performance Benefits:** Users experience zero delay. For example, order completion completes instantly, while emails are queued and dispatched in the background.

### Queue Worker (`queue:work`)
Pending background jobs are processed by the console command:
```bash
php cli/console.php queue:work
```
This command runs automatically every minute under the central scheduler. It picks up pending jobs, deserializes the event payloads, resolves the listener via constructor autowiring (Dependency Injection), and executes them.

### Automatic Retries & Backoff
If a background job fails (e.g., due to a temporary network issue or database lock), the queue system has built-in resilience:
- **Configurable Retries:** Listeners specify their maximum attempt limit via the `getTries()` method.
- **Retry Delays:** The retry delay is customizable via the `getRetryDelay()` method.
- **Exponential Backoff:** If `useExponentialBackoff()` returns `true`, the wait duration doubles after every failed attempt (e.g., 1 min -> 2 min -> 4 min), minimizing system load during outages.

### Queue Cleanup & Retention
Completed and failed background jobs are stored in history for logging and debugging. To prevent infinite database growth:
- **`queue:cleanup` Command:** Runs daily to purge old job records from the database.
- **Retention Settings:** Managed dynamically in **Admin > Settings > Background Jobs**:
  - *Cleanup Completed (hours):* Keep completed jobs in history (Default: `24` hours).
  - *Cleanup Failed (days):* Keep failed jobs in history for troubleshooting (Default: `7` days).

---

## 11. Virtual Products & Role-Restricted Delivery

Demoshop supports full lifecycle management for **Virtual Products**—digital-only goods requiring no physical shipping—as well as **Role-Restricted Delivery Options** that can be unlocked dynamically.

### Virtual Product Types

The system supports five types of virtual products, each with distinct behaviors:

1.  **Digital File Downloads (`file`):**
    *   *Description:* Securely sells digital files like PDFs, E-books, videos, or software ZIP files.
    *   *Setup (Admin):*
        1. Create or edit a product in the Admin Panel.
        2. Toggle the **Is Virtual** checkbox.
        3. Under **Virtual Type**, select `Digital File Download`.
        4. Enter the secure file path in the **Digital File Path** input field (e.g., `ebooks/sample-guide.pdf`).
    *   *Fulfillment (Customer):*
        *   Upon order confirmation, the system automatically generates a unique, secure 32-character download token for each file purchased.
        *   Customers can view and download their files from the **Downloads** tab in their account dashboard.
        *   Downloading streams the file securely from the private storage location using the path `/download/{token}`, preventing direct access to files.

2.  **Gift Cards (`giftcard`):**
    *   *Description:* Digital store credits sent directly to recipients. Gift cards support persistent balance tracking and partial deductions.
    *   *Setup (Admin):*
        1. Create a product (e.g., "Digital Gift Card").
        2. Toggle the **Is Virtual** checkbox and set the type to `Gift Card`.
    *   *Purchase Flow (Customer):*
        *   When adding the gift card to their cart, the customer is prompted to specify the recipient's email, sender's name, and a custom message.
        *   These details are stored as dynamic metadata inside the cart and order items.
    *   *Fulfillment & Usage:*
        *   Upon confirmation, the system generates a secure code (e.g., `GIFT-A2B4-C6D8`) with the total purchased amount.
        *   An HTML email containing the code, sender details, and message is automatically dispatched to the recipient.
        *   The code can be applied by the recipient during checkout to decrease their order total. If the order total is lower than the gift card balance, the remaining balance is persisted for future purchases.

3.  **Software License Keys (`license`):**
    *   *Description:* Distribute serial numbers, activation codes, or license keys from a pre-allocated inventory pool.
    *   *Setup (Admin):*
        1. Create a product, check **Is Virtual**, and set type to `Software License Key`.
        2. In the Admin Panel product editing view, locate the **License Keys Pool** section to manage and import keys into the product's pool.
    *   *Fulfillment (Customer):*
        *   When purchased, the system automatically retrieves a key marked as unassigned from the product's license pool, marks it as assigned, and attaches it to the order item.
        *   *Fallback:* If the pre-allocated pool is completely empty, the system automatically generates a secure random serial code (`LIC-XXXX-XXXX-XXXX`) to ensure uninterrupted customer fulfillment.
        *   The customer can instantly access their assigned license key from the **Downloads** tab in their portal.

4.  **User Memberships & Role Upgrades (`membership`):**
    *   *Description:* Upgrades a customer's role in the database to grant access to special delivery options or role-restricted promotions.
    *   *Setup (Admin):*
        1. Create a product (e.g., "VIP Monthly Membership").
        2. Check **Is Virtual** and set type to `User Membership / Role Upgrade`.
        3. Under **Granted Role**, choose the target user role (e.g., `vip`).
    *   *Fulfillment (Customer):*
        *   Upon successful payment, the system automatically updates the customer's role in the database to the granted role.
        *   The change takes effect instantly, unlocking role-restricted options for all future browse and checkout cycles.

5.  **Event Tickets (`event_ticket`):**
    *   *Description:* Digital booking references or admittance passes for virtual or physical events.
    *   *Setup (Admin):*
        1. Create a product, check **Is Virtual**, and set type to `Event Ticket`.
    *   *Fulfillment (Customer):*
        *   Fulfillment automatically generates a secure unique booking ticket reference (e.g., `TKT-D9E4-F7A1`).
        *   The customer can view and download their ticket codes directly from their dashboard's **Downloads & Tickets** page.

---

### Frictionless Digital Checkout

To ensure the highest possible conversion rate, Demoshop implements smart checkout bypassing:
*   **Automatic Check:** If a customer's cart contains *only* virtual products (or bundles made up entirely of virtual items), the checkout system automatically detects it.
*   **Form Simplification:** The shipping address forms (Address, City, Postcode, Country) and delivery method selections are dynamically hidden from both the checkout template and verified out of controller inputs.
*   **Digital Delivery Setup:** The shipping cost is instantly set to `0.00` and the delivery method is recorded as `'Digital Delivery'`, making checkout incredibly fast and friction-free.

---

### Role-Restricted Delivery Options

Demoshop allows administrators to restrict specific shipping methods to selected customer groups (e.g., free express shipping for VIP members):
*   **Configuration (Admin):** Go to **Admin > Delivery Options**. When creating or editing a shipping method, use the **Restrict to User Role** dropdown to select the target role (e.g., `vip`). If left empty, the method is visible to all customers.
*   **Storefront Filtering:** During checkout, the delivery service automatically filters available delivery options. Customers who do not possess the required role will not see or be able to select that restricted delivery option.
*   **Combination with Memberships:** Customers can buy a "VIP Membership" virtual product, checkout (shipping form bypassed), and immediately gain access to VIP-restricted delivery methods on subsequent physical orders.

---
*For developer support or feature requests, please consult the `README.md`.*
