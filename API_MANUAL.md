# API Manual (RESTful JSON API)

Welcome to the Demoshop RESTful JSON API documentation. This document serves as the guide for connecting decoupled frontends (such as single-page web applications or mobile apps) to the Demoshop backend.

---

## 🚦 General Information

### Base URL
All API requests must be prefixed with:
```
http://<your-domain>/api/v1
```
For local environments, this is typically `http://localhost:8000/api/v1` or `http://shop-demo.test/api/v1`.

### Request Headers
Every request to the API must include the following headers where appropriate:
*   `Accept: application/json` — Instructs the server to return JSON.
*   `Content-Type: application/json` — Required for any requests carrying payloads (`POST`, `PUT`, etc.).
*   `Authorization: Bearer <token>` — Used to authenticate request sessions (acquired via the Login or Register endpoints).
*   `X-Cart-UUID: <uuid>` — Used to track guest shopping carts across sessions.

---

## 🔒 Authentication & Session Flow

The API utilizes **stateless token-based authentication**. You must pass the token received upon login in the `Authorization` header of all protected requests.

```
Frontend                          API Endpoint
   │                                   │
   ├─────── POST /auth/login ─────────>│
   │        (email & password)         │
   │                                   │
   │<────── 200 OK ────────────────────┤
   │        (returns raw API Token)    │
   │                                   │
   ├─────── GET /auth/me ─────────────>│
   │        (Header: Bearer <token>)   │
   │                                   │
   │<────── 200 OK ────────────────────┤
   │        (returns user details)     │
```

---

## 📍 Endpoint Reference

### 1. Authentication

#### Log In
*   **Method**: `POST`
*   **Path**: `/api/v1/auth/login`
*   **Payload**:
    ```json
    {
      "email": "customer@example.com",
      "password": "securepassword"
    }
    ```
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": {
        "token": "a1b2c3d4e5...",
        "expires_at": "2026-06-12T08:00:00Z",
        "user": {
          "id": 1,
          "name": "Jane Doe",
          "email": "customer@example.com",
          "is_verified": true
        }
      }
    }
    ```

#### Register
*   **Method**: `POST`
*   **Path**: `/api/v1/auth/register`
*   **Payload**:
    ```json
    {
      "name": "Jane Doe",
      "email": "customer@example.com",
      "password": "securepassword",
      "password_confirmation": "securepassword"
    }
    ```
*   **Response (201 Created)**:
    ```json
    {
      "success": true,
      "data": {
        "token": "x9y8z7...",
        "user": {
          "id": 2,
          "name": "Jane Doe",
          "email": "customer@example.com",
          "is_verified": false
        }
      }
    }
    ```

---

### 2. Products & Catalog

#### List Products
*   **Method**: `GET`
*   **Path**: `/api/v1/products`
*   **Query Parameters**:
    *   `category` (string, optional) - Filter by category slug.
    *   `search` (string, optional) - Fulltext search term.
    *   `sort` (string, optional) - `price_asc`, `price_desc`, `newest`, `popular`.
    *   `page` (int, optional) - Defaults to `1`.
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": {
        "products": [
          {
            "id": 4,
            "name": "Minimalist Leather Backpack",
            "slug": "minimalist-leather-backpack",
            "price": 120.00,
            "sku": "BAG-LTHR-01",
            "stock": 15,
            "image": "/uploads/bag.jpg",
            "image_thumb": "/uploads/bag_thumb.webp",
            "image_medium": "/uploads/bag_medium.webp",
            "image_large": "/uploads/bag_large.webp",
            "featured": false,
            "is_purchasable": true,
            "force_variant": false,
            "active_promotions": []
          }
        ],
        "currency": {
          "code": "GBP",
          "symbol": "£"
        },
        "pagination": {
          "current_page": 1,
          "total_pages": 3,
          "total_items": 15,
          "limit": 12
        }
      }
    }
    ```

#### Product Detail
*   **Method**: `GET`
*   **Path**: `/api/v1/products/:slug`
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": {
        "id": 4,
        "name": "Minimalist Leather Backpack",
        "slug": "minimalist-leather-backpack",
        "sku": "BAG-LTHR-01",
        "price": 120.00,
        "stock": 15,
        "image": "/uploads/bag.jpg",
        "image_thumb": "/uploads/bag_thumb.webp",
        "image_medium": "/uploads/bag_medium.webp",
        "image_large": "/uploads/bag_large.webp",
        "featured": false,
        "is_purchasable": true,
        "force_variant": false,
        "active_promotions": [],
        "description": "Premium leather, everyday companion.",
        "is_bundle": false,
        "is_virtual": false,
        "average_rating": 4.5,
        "variants": [
          { "id": 12, "sku": "BAG-LTHR-01-BLK", "name": "Color: Black", "price_modifier": 0.00, "stock": 5 },
          { "id": 13, "sku": "BAG-LTHR-01-TAN", "name": "Color: Tan", "price_modifier": 10.00, "stock": 10 }
        ],
        "attributes": [
          { "attribute_id": 1, "attribute_name": "Brand", "value_id": 2, "value": "Aether" }
        ],
        "reviews": [
          { "id": 1, "user_name": "Jane Doe", "rating": 5, "comment": "Great bag!", "created_at": "2026-06-11 10:00:00" }
        ],
        "tiers": [],
        "bundle_items": [],
        "currency": {
          "code": "GBP",
          "symbol": "£"
        }
      }
    }
    ```

---

### 3. Shopping Cart

#### Get Cart Details
*   **Method**: `GET`
*   **Path**: `/api/v1/cart`
*   **Headers**:
    *   `X-Cart-UUID` (optional) - Links back to a persistent guest cart.
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": {
        "items": [
          {
            "key": "4_12",
            "product_id": 4,
            "variant_id": 12,
            "name": "Minimalist Leather Backpack (Black)",
            "sku": "BAG-LTHR-01-BLK",
            "image": "/uploads/bag.jpg",
            "image_thumb": "/uploads/bag_thumb.webp",
            "image_medium": "/uploads/bag_medium.webp",
            "image_large": "/uploads/bag_large.webp",
            "quantity": 1,
            "unit_price": 120.00,
            "subtotal": 120.00,
            "is_virtual": false
          }
        ],
        "applied_promotions": [],
        "summary": {
          "subtotal": 120.00,
          "discount": 0.00,
          "total_vat": 20.00,
          "grand_total": 120.00,
          "item_count": 1
        },
        "currency": {
          "code": "GBP",
          "symbol": "£"
        }
      }
    }
    ```

---

## 🧪 API Testing Strategy

Because this API runs within a zero-dependency PHP environment, testing is divided into backend Unit/Integration tests and manual CLI testing commands.

### A. Core Unit & Integration Tests
Every API endpoint will have corresponding unit test assertions under `tests/Unit/Api/`.

To execute all tests, run the custom zero-dependency test harness:
```bash
php tests/run.php
```

Example Unit Test structure (`tests/Unit/Api/ApiProductTest.php`):
```php
namespace Tests\Unit\Api;

use App\Core\Request;
use App\Controllers\Api\ApiProductController;

class ApiProductTest {
    public function testGetProductDetailsReturnsJson() {
        $request = new Request(['slug' => 'minimalist-leather-backpack'], [], [], [], [], []);
        $controller = new ApiProductController(/* mock dependencies */);
        
        $response = $controller->show($request, 'minimalist-leather-backpack');
        
        assert($response->getStatusCode() === 200);
        assert(str_contains($response->getHeaders()['Content-Type'], 'application/json'));
    }
}
```

### B. Manual Command Line Testing (cURL)
You can mock calls and verify API behavior directly from the terminal.

#### 1. Retrieve Products List
```bash
curl -i -H "Accept: application/json" http://localhost:8000/api/v1/products
```

#### 2. User Authentication (Login)
```bash
curl -i -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@demoshop.com","password":"password"}' \
  http://localhost:8000/api/v1/auth/login
```

---

### 4. Checkout & Orders

#### Saved Addresses
*   **Method**: `GET`
*   **Path**: `/api/v1/account/addresses`
*   **Headers**:
    *   `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id": 1,
          "label": "Home",
          "name": "John Doe",
          "address": "123 Main St",
          "city": "Metropolis",
          "postcode": "12345",
          "country": "US",
          "is_default": true
        }
      ]
    }
    ```

#### Save Address
*   **Method**: `POST`
*   **Path**: `/api/v1/account/addresses/save`
*   **Headers**:
    *   `Authorization: Bearer <token>`
*   **Payload**:
    ```json
    {
      "id": 0,
      "label": "Office",
      "name": "John Office",
      "address": "456 Corp Blvd",
      "city": "Gotham",
      "postcode": "54321",
      "country": "US",
      "is_default": false
    }
    ```
*   **Response (201 Created)**:
    ```json
    {
      "success": true,
      "message": "Address created.",
      "data": {
        "id": 2
      }
    }
    ```

#### Checkout Process
*   **Method**: `POST`
*   **Path**: `/api/v1/checkout`
*   **Headers**:
    *   `Authorization: Bearer <token>` (optional, required if placing order for user account)
    *   `X-Cart-UUID: <uuid>` (optional, required if guest cart is used)
*   **Payload**:
    ```json
    {
      "name": "Jane Doe",
      "email": "customer@example.com",
      "address": "123 Main St",
      "city": "Metropolis",
      "postcode": "12345",
      "country": "US",
      "delivery_option_id": 1,
      "card_number": "4111111111111111",
      "card_expiry": "12/28",
      "card_cvc": "123"
    }
    ```
*   **Response (201 Created)**:
    ```json
    {
      "success": true,
      "message": "Order placed successfully.",
      "data": {
        "order_id": 12,
        "order_reference": "#000012",
        "status": "paid",
        "transaction_id": "ch_mock_..."
      }
    }
    ```

#### Get User Orders
*   **Method**: `GET`
*   **Path**: `/api/v1/orders`
*   **Headers**:
*   `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id": 12,
          "order_reference": "#000012",
          "total": 120.00,
          "status": "paid",
          "created_at": "2026-06-11 08:30:00"
        }
      ]
    }
    ```

#### Guest Order Lookup
*   **Method**: `POST`
*   **Path**: `/api/v1/orders/lookup`
*   **Payload**:
    ```json
    {
      "order_reference": "#000012",
      "email": "customer@example.com"
    }
    ```
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": {
        "id": 12,
        "order_reference": "#000012",
        "status": "paid",
        "customer_name": "John Doe",
        "name": "John Doe",
        "customer_email": "customer@example.com",
        "email": "customer@example.com",
        "total": 120.00,
        "total_vat": 20.00,
        "discount": 0.00,
        "shipping_address": "123 Main St\nLondon\nSW1A 1AA\nUnited Kingdom",
        "delivery_method": "Standard Delivery",
        "delivery_cost": 5.00,
        "created_at": "2026-06-11 08:30:00",
        "items": [
          {
            "product_id": 4,
            "variant_id": 2,
            "variant_name": "Black",
            "name": "Minimalist Leather Backpack",
            "sku": "BAG-LTHR-01",
            "qty": 1,
            "quantity": 1,
            "price": 120.00,
            "unit_price": 120.00,
            "total": 120.00,
            "is_bundle": false,
            "bundle_components": []
          }
        ]
      }
    }
    ```

#### Get Delivery Options
*   **Method**: `GET`
*   **Path**: `/api/v1/delivery-options`
*   **Response (200 OK)**:
    ```json
    {
      "success": true,
      "data": [
        {
          "id": 1,
          "name": "Standard Delivery",
          "price": 5.00,
          "min_order_total": 0.00
        },
        {
          "id": 2,
          "name": "Next Day Express",
          "price": 10.00,
          "min_order_total": 50.00
        }
      ]
    }
    ```

