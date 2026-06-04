# Developer Manual

This manual documents specific development workflows, architecture guidelines, coding patterns, and security mandates for developing on the Demoshop codebase.

---

## 🚀 Essential Commands

*   **Apply Database Migrations:** `php cli/console.php migrate`
*   **Seed Default Demo Database:** `php cli/console.php db:seed`
*   **Run All Unit Tests:** `php tests/run.php`
*   **Run Local Dev Server:** `php -S localhost:8080 -t public`
*   **Run Scheduler Tasks (Every Min):** `php cli/console.php schedule:run`
*   **Process Pending Background Jobs:** `php cli/console.php queue:work`
*   **Clean Up Background Jobs History:** `php cli/console.php queue:cleanup`

---

## 🛠️ Core Principles

### 1. No External Dependencies
*   **Zero-Dependency Policy:** Absolutely no external libraries or composer packages are allowed.
*   Only use standard library capabilities shipped with **PHP 8.0+** by default.

### 2. Server Agnosticism
*   All web routing, request intercepts, and static asset fallback behaviors must work seamlessly across Nginx, Apache, IIS, and the PHP built-in CLI server.
*   Never write server-specific fixes (e.g. relying solely on SAPI checks like `php_sapi_name() === 'cli-server'` to intercept public assets) when server-agnostic solutions are achievable.

---

## 🏗️ Architectural Mandates

### 1. Dependency Injection & Service Orientation
*   **Interface-Driven Architecture:** All business services and repositories must be defined as interfaces in their respective directories (`src/Services/` or `src/Repositories/`) and implemented as concrete classes.
*   **Constructor Injection:** Controllers and services must type-hint the interface in their constructor. Use **PHP 8 Constructor Property Promotion** for all clean dependency storage:
    ```php
    public function __construct(
        protected ProductServiceInterface $productService
    ) {}
    ```
*   **Service Registration:** You MUST register all new interface-to-concrete mappings in `config/services.php` (or granular files under `config/services/`) so the custom Dependency Injection container (`App\Core\Container`) can resolve and autowire them.

### 2. Repository Pattern
*   All database interactions must be completely isolated in Repository classes.
*   Services, controllers, and models must **never** contain raw SQL or direct PDO calls; they must delegate all data access and querying to the appropriate repository interface.

### 3. Database Migrations
*   Any change to the database structure, schema additions, or essential seed data MUST be implemented as a new migration file in the `migrations/` directory.
*   Run `php cli/console.php migrate` to execute new migrations.

### 4. Middleware Stack
*   Cross-cutting concerns like Authentication, Admin checking, CSRF validation, and Email Verification must be implemented as Middleware classes in `src/Middleware/`.
*   When adding new routes in `config/routes.php`, always apply the pre-defined middleware stack variables (e.g., `$adminPostMiddleware`, `$authMiddleware`) to ensure consistent security enforcement.

---

## 🔒 Security Standards

### 1. CSRF Protection
*   All state-changing requests (POST/PUT/DELETE) must be protected by session-based CSRF tokens.
*   Ensure every HTML form in views includes a CSRF token input field:
    ```html
    <input type="hidden" name="csrf_token" value="<?= esc($csrf_token) ?>">
    ```

### 2. Rate Limiting
*   Protects against brute force and scraping.
*   **Driver Agnosticism**: Keep calculations database-driver agnostic by performing all datetime math on the PHP side rather than using driver-specific SQL functions (e.g. `NOW()` or `DATE_SUB()`).

---

## 📬 Direct Background Job Queueing

This details how to queue background tasks in Demoshop without dispatching a global event through the event dispatcher.

### Context
By default, the queue worker ([QueueWorkCommand](file:///Users/andy/dev/shop-demo/src/Commands/QueueWorkCommand.php)) strictly enforces that queued job payloads are serialized instances of [Event](file:///Users/andy/dev/shop-demo/src/Core/Events/Event.php), and their handlers implement [ListenerInterface](file:///Users/andy/dev/shop-demo/src/Core/Events/ListenerInterface.php).

### How to Queue Directly

1. **Define the Event Envelope**: Create an Event class that acts as the data container for your job.
   ```php
   namespace App\Events;

   use App\Core\Events\Event;

   class MyCustomJobEvent extends Event {
       public function __construct(
           public array $data
       ) {}
   }
   ```

2. **Define the Job Handler**: Create a class implementing `ListenerInterface` to process the job. Optionally, implement `ShouldQueue` to specify retry and delay parameters.
   ```php
   namespace App\Listeners;

   use App\Core\Events\ListenerInterface;
   use App\Core\Events\ShouldQueue;
   use App\Core\Events\Event;

   class MyCustomJobHandler implements ListenerInterface, ShouldQueue {
       public function handle(Event $event): void {
           /** @var \App\Events\MyCustomJobEvent $event */
           $data = $event->data;

           // Run background task logic here
       }

       public function getTries(): int {
           return 3;
       }

       public function getRetryDelay(): int {
           return 5; // Minutes
       }

       public function useExponentialBackoff(): bool {
           return true;
       }
   }
   ```

3. **Queue the Job Directly**: Inject `QueueServiceInterface` and push your job:
   ```php
   use App\Services\QueueServiceInterface;
   use App\Listeners\MyCustomJobHandler;
   use App\Events\MyCustomJobEvent;

   // ... inside your controller, service, or console command ...
   $this->queueService->push(
       MyCustomJobHandler::class,
       new MyCustomJobEvent($jobDataPayload)
   );
   ```

---

## 🧪 Testing Framework

*   Every new feature, service, or bug fix MUST include corresponding unit tests in the `tests/Unit/` directory.
*   Execute all tests using the custom zero-dependency unit test runner:
    ```bash
    php tests/run.php
    ```
