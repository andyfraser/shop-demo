# Meridian Shop

A demo e-commerce application written in PHP with SQLite. No frameworks, no Composer, no build step — just PHP 8 and a browser.

![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

**Storefront**
- Hierarchical product categories with dropdown navigation
- Product listings, detail pages, search
- Session-based shopping cart
- Checkout and order placement (no payment required)
- Customer accounts with order history
- User registration and login

**Admin panel** (`/admin/`)
- Dashboard with live stats and low-stock alerts
- Full CRUD for products, categories, and users
- Order management with status updates
- Role-based access control (admin / customer)

---

## Requirements

- PHP 8.0 or higher
- Extensions: `pdo_sqlite` — enabled by default in most PHP installs

On Ubuntu / Linux Mint:

```bash
sudo apt install php php-sqlite3
```

---

## Quick Start

```bash
git clone https://github.com/yourname/meridian-shop.git
cd meridian-shop
php -S localhost:8080 router.php
```

Open **http://localhost:8080** in your browser. The SQLite database is created and seeded automatically on first run.

> **Note:** Use `router.php` with the built-in server so static files (CSS, images) are served correctly.

---

## Demo Accounts

| Role     | Email               | Password   |
|----------|---------------------|------------|
| Admin    | `admin@shop.local`  | `password` |
| Customer | `jane@example.com`  | `password` |

To access the admin panel, log in as admin and visit `/admin/` — or simply navigate there directly and you'll be redirected to the login page first.

---

## Project Structure

```
meridian-shop/
│
├── public/css/
│   ├── shop.css            # Storefront styles
│   └── admin.css           # Admin panel styles
│
├── templates/              # HTML templates — no business logic
│   ├── header.php
│   ├── footer.php
│   ├── home.php
│   ├── product.php
│   ├── category.php
│   ├── search.php
│   ├── cart.php
│   ├── checkout.php
│   ├── order_confirm.php
│   ├── account.php
│   ├── login.php
│   ├── register.php
│   └── admin/
│       ├── header.php
│       ├── footer.php
│       ├── dashboard.php
│       ├── products_list.php
│       ├── products_form.php
│       ├── categories_list.php
│       ├── categories_form.php
│       ├── orders_list.php
│       ├── orders_detail.php
│       ├── users_list.php
│       └── users_form.php
│
├── admin/                  # Admin controllers
│   ├── render.php
│   ├── index.php
│   ├── products.php
│   ├── categories.php
│   ├── orders.php
│   └── users.php
│
├── bootstrap.php           # Database, session, cart, helpers
├── render.php              # Template renderer
├── router.php              # Built-in server router
├── schema.sql              # Schema and seed data
│
├── index.php               # Homepage
├── product.php             # Product detail
├── category.php            # Category listing
├── search.php              # Search results
├── cart.php                # Shopping cart
├── checkout.php            # Checkout
├── order_confirm.php       # Order confirmation
├── account.php             # Account and order history
├── login.php               # Sign in
├── register.php            # Register
└── logout.php              # Sign out
```

---

## Architecture

Controllers and templates are kept strictly separate:

| Layer | Files | Responsibility |
|---|---|---|
| Controllers | `*.php`, `admin/*.php` | Database queries, validation, redirects |
| Templates | `templates/**/*.php` | HTML output only — no queries or redirects |
| Styles | `public/css/*.css` | CSS only |

### How rendering works

Each controller fetches its data, then hands it to `render()`:

```php
// product.php
$product = db()->prepare("SELECT ...")->fetch();
$breadcrumb = get_breadcrumb($product['category_id']);

render('product', [
    'page_title' => $product['name'],
    'product'    => $product,
    'breadcrumb' => $breadcrumb,
]);
```

`render()` injects shared variables (`$current_user`, `$cart_count`, `$nav_tree`) and includes `header.php`, the named template, then `footer.php`. Templates receive variables via `extract()` and contain only HTML.

The admin panel works the same way via `admin_render()` in `admin/render.php`.

---

## Security Notes

This is a demonstration project. For production use you would need to:

- Move `shop.db` outside the web root
- Add CSRF tokens to all forms
- Add rate limiting on login and registration
- Enforce HTTPS
- Add `Content-Security-Policy` and other security headers
