# Product Quick View Implementation Plan

We will implement a premium **Product Quick View** feature. Clicking a glassmorphic preview button on the product card image will load a split-layout modal with product details, stock status, options/variants, and a functional AJAX "Add to Cart" button.

---

## 🛠️ Proposed Changes

### 1. View Component updates
*   **[ProductCard.php](file:///Users/andy/dev/shop-demo/src/View/Components/ProductCard.php)**: Insert a new quick view button in the `.img-wrap` container of each product card.
    ```html
    <button type="button" class="quickview-btn" data-slug="<?= $slug ?>" aria-label="Quick View">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quickview-icon">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
    </button>
    ```

### 2. Backend Routing & Controller (AJAX Endpoint)
*   **[StorefrontController.php](file:///Users/andy/dev/shop-demo/src/Controllers/StorefrontController.php)**: Update `product()` method to detect `?quickview=1` or AJAX requests with quickview parameter, rendering only the quick view partial without header/footer.
*   **[templates/partials/product_quickview.php](file:///Users/andy/dev/shop-demo/templates/partials/product_quickview.php)**: Create a new partial containing the structured split-column layout for the quick view modal:
    *   **Left Column**: Product image (lazy loaded).
    *   **Right Column**: Category breadcrumb, Title, Price, Active Promotion badge, Short Description, Stock Status, Options Selector (variants), Quantity selector, and AJAX "Add to Cart" form.

### 3. Styling & Aesthetics
*   **[shop.css](file:///Users/andy/dev/shop-demo/public/css/shop.css)**:
    *   **Hover Eye Icon**: Position the `.quickview-btn` in the bottom-right corner of `.img-wrap`. On desktop, style it with glassmorphic backing (`backdrop-filter: blur(...)`, semi-transparent background), transition it with ease (opacity and transform scale), and show it on card hover. On mobile, ensure it is always visible.
    *   **Modal & Backdrop**: Style a modal overlay (`.quickview-modal-overlay` with soft dark fade-in background) and the content block (`.quickview-modal-content` with premium border-radius, clean spacing, and a scale-up pop-in animation).
    *   **Split Layout**: CSS Grid/Flexbox layout for image + details.

### 4. JavaScript Logic
*   **[shop.js](file:///Users/andy/dev/shop-demo/public/js/shop.js)**:
    *   **Event Delegation**: Attach event listeners for clicks on `.quickview-btn`.
    *   **Dynamic Loading**: Fetch `/product/{slug}?quickview=1` using `fetch` with AJAX headers.
    *   **Modal Management**: Inject the modal wrapper if it doesn't exist, populate its body with the returned HTML partial, and open it with animations.
    *   **Dismissal**: Close the modal when clicking the `×` button, pressing `Escape`, or clicking the backdrop overlay.
    *   **Dynamic Interactions**: Re-bind variant changing behavior (updating price, stock status, active discounts) and AJAX add-to-cart submit behavior specifically within the active modal.

---

## 📅 Testing & Verification
1. Verify the quick view button shows up on hover on product cards in Home, Category, and Search results pages.
2. Click the quick view button to verify the modal loads and opens with a smooth animation.
3. Test variant selection changes (price, stock badges, etc. should update).
4. Test "Add to Cart" inside the quick view modal (should trigger AJAX request, update cart badge, show success message, and keep modal open).
5. Verify keyboard close (`Esc`) and click-outside close work correctly.
