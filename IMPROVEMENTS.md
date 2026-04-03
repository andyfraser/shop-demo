# Demoshop Improvement Suggestions

Based on an analysis of the `Demoshop` codebase, here are recommendations for improving the project, ranked roughly by priority. These aim to bring the application closer to modern security, scalability, and maintainability standards while keeping its vanilla PHP roots.

## 1. Security Improvements

Security is critical for an e-commerce application. A few essential protections are currently missing:

*   **CSRF Protection**: Forms (like login, checkout, cart operations) currently do not implement Cross-Site Request Forgery (CSRF) tokens. This leaves users vulnerable to malicious actors submitting forms on their behalf.
*   **Session Security**: Upon successful login in `login.php`, the application does not regenerate the session ID. Adding `session_regenerate_id(true)` is a necessary protection to prevent session fixation attacks.
*   **Rate Limiting**: There are no brute-force protections on the login or registration endpoints. Adding basic rate-limiting or account lockouts after failed attempts would secure user accounts.

## 2. Architecture and Code Organization

Currently, the application relies on global functions in `bootstrap.php` and handles request logic globally in root-level scripts.

*   **Front Controller & Routing**: Instead of having separate files like `/login.php`, `/search.php`, and `/index.php` handling requests directly, implement a Front Controller. Routing everything through one `index.php` using path evaluation (e.g., `/login`, `/product/123`) would unify routing and allow middleware (like auth/session handling) to be applied globally.
*   **Encapsulation (Classes/Services)**: Global state operations, such as the `cart()` and `current_user()` helpers, could be refactored into classes (e.g., `CartService`, `AuthManager`). This makes it much easier to write unit tests, manage dependencies, and separate concerns.
*   **Autoloading**: Implementing a custom vanilla PHP autoloader using `spl_autoload_register` (following PSR-4 conventions) would eliminate the need to manually include `require_once __DIR__ . '/bootstrap.php';` in every entry script, all without needing any external dependencies like Composer.

## 3. Database and Performance

While SQLite is perfectly suitable for this type of demo, the schema and queries could be optimized for scale:

*   **Pagination**: Queries like the search functionality (`search.php`) or category views will load all matching database products into memory at once. Implementing a simple pagination mechanism using `LIMIT` and `OFFSET` would make the app scalable as the catalog grows.
*   **Missing Database Indexes**: In `schema.sql`, there are no specific indexes added other than primary/unique keys constraint defaults. Adding individual indexes on columns like `users.email`, `products.category_id`, and `products.active` will vastly improve read performance for catalog browsing and authenticating users.

## 4. Application Features

*   **Input Validation**: A centralized validation structure (verifying email formats, ensuring quantities are positive integers, checking field requiredness) would be cleaner and more secure than writing individual `trim($_POST[...])` and checking constraints manually per file.
*   **Payment Gateway**: Currently, the system uses a mock checkout process. Integrating a library like Stripe's PHP SDK would turn it into a fully functional store.
*   **UI/UX Enhancements**: You could add AJAX to the "Add to Cart" functionality and other form submissions so the page doesn't require a full reload, dramatically improving the user experience.
