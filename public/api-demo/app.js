/**
 * Demoshop API client SPA
 */
const App = {
    // API Configuration
    apiBase: '/api/v1',
    
    // Application State
    state: {
        user: null,
        cart: null,
        categories: [],
        products: [],
        currentCategory: null,
        searchQuery: '',
        sortBy: 'newest',
        cartUuid: localStorage.getItem('cart_uuid') || null,
        token: localStorage.getItem('auth_token') || null,
        currencySymbol: '$', // Synced dynamically from API responses
        currentPage: 1
    },

    // Initialize application
    async init() {
        console.log("Initializing App...");
        
        // Setup Route Listeners
        window.addEventListener('hashchange', () => this.router());
        
        // Check current session
        if (this.state.token) {
            await this.fetchCurrentUser();
        }
        
        // Initial Fetch of Cart and Categories
        await this.syncCart();
        await this.fetchCategories();
        
        // Run router on load
        this.router();
    },

    // Toast Notifications
    showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <span style="cursor:pointer;opacity:0.6" onclick="this.parentElement.remove()">✕</span>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s reverse forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // HTTP Helper wrapping REST queries
    async api(path, options = {}) {
        const url = `${this.apiBase}${path}`;
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers
        };

        if (this.state.token) {
            headers['Authorization'] = `Bearer ${this.state.token}`;
        }
        if (this.state.cartUuid) {
            headers['X-Cart-UUID'] = this.state.cartUuid;
        }

        const fetchOptions = {
            ...options,
            headers
        };

        try {
            const response = await fetch(url, fetchOptions);
            const data = await response.json();
            
            // Check for cart UUID returned from API to persist
            if (data?.data?.cart_uuid) {
                this.state.cartUuid = data.data.cart_uuid;
                localStorage.setItem('cart_uuid', data.data.cart_uuid);
            }
            
            // Sync dynamic currency symbol from payloads
            const currencyBlock = data?.data?.currency || data?.currency;
            if (currencyBlock && currencyBlock.symbol) {
                this.state.currencySymbol = currencyBlock.symbol;
            }
            
            if (!response.ok) {
                throw new Error(data.error?.message || data.message || `API Error: ${response.status}`);
            }
            return data;
        } catch (err) {
            console.error(`API Call failed: ${url}`, err);
            throw err;
        }
    },

    // Sync state with current authenticated user
    async fetchCurrentUser() {
        try {
            const response = await this.api('/auth/me');
            if (response.success) {
                this.state.user = response.data.user;
                this.updateAuthWidget();
            }
        } catch (err) {
            console.warn("Session expired or invalid token.");
            this.logoutSession();
        }
    },

    // Sync/Get Cart details
    async syncCart() {
        try {
            const response = await this.api('/cart');
            if (response.success) {
                this.state.cart = response.data;
                this.updateCartBadge();
            }
        } catch (err) {
            console.error("Cart synchronization failed", err);
        }
    },

    // Get product categories list
    async fetchCategories() {
        try {
            const response = await this.api('/categories');
            if (response.success) {
                this.state.categories = response.data.categories || response.data || [];
            }
        } catch (err) {
            console.error("Failed to fetch categories", err);
        }
    },

    // Update headers and profile states
    updateAuthWidget() {
        const widget = document.getElementById('auth-widget');
        if (!widget) return;
        
        if (this.state.user) {
            widget.innerHTML = `
                <div class="user-dropdown-trigger" style="display:flex;align-items:center;gap:12px">
                    <span style="font-size:13px;font-weight:600">👋 ${this.state.user.name}</span>
                    <button class="btn btn-outline btn-sm" id="logout-btn">Sign Out</button>
                </div>
            `;
            document.getElementById('logout-btn')?.addEventListener('click', () => this.handleLogout());
        } else {
            widget.innerHTML = `<a href="#/login" class="btn btn-outline btn-sm">Sign In</a>`;
        }
    },

    updateCartBadge() {
        const badge = document.getElementById('cart-count');
        if (!badge) return;
        let count = 0;
        if (this.state.cart && this.state.cart.items) {
            count = this.state.cart.items.reduce((acc, item) => acc + item.quantity, 0);
        }
        badge.textContent = count;
    },

    // Perform User Sign Out
    async handleLogout() {
        try {
            if (this.state.token) {
                await this.api('/auth/logout', { method: 'POST' });
            }
        } catch (err) {
            console.warn("Logout request failed, clearing local session anyway.");
        }
        this.logoutSession();
        this.showToast("Signed out successfully.", "success");
    },

    logoutSession() {
        this.state.token = null;
        this.state.user = null;
        localStorage.removeItem('auth_token');
        this.updateAuthWidget();
        window.location.hash = '#/';
    },

    // SPA client-side hash router
    router() {
        const hash = window.location.hash || '#/';
        const viewContainer = document.getElementById('app-view');
        if (!viewContainer) return;
        
        // Remove active class from navigation links
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        
        // Handle routes
        if (hash === '#/' || hash.startsWith('#/category/') || hash.startsWith('#/search')) {
            document.getElementById('nav-products')?.classList.add('active');
            
            // Extract category slug if applicable
            const catMatch = hash.match(/#\/category\/([^?]+)/);
            const category = catMatch ? catMatch[1] : null;
            if (this.state.currentCategory !== category) {
                this.state.currentCategory = category;
                this.state.currentPage = 1;
            }
            
            this.renderCatalogView(viewContainer);
        } else if (hash.startsWith('#/product/')) {
            const slug = hash.replace('#/product/', '').split('?')[0];
            this.renderProductDetailView(viewContainer, slug);
        } else if (hash === '#/cart') {
            this.renderCartView(viewContainer);
        } else if (hash === '#/checkout') {
            this.renderCheckoutView(viewContainer);
        } else if (hash === '#/wishlist') {
            document.getElementById('nav-wishlist')?.classList.add('active');
            this.renderWishlistView(viewContainer);
        } else if (hash === '#/orders' || hash.startsWith('#/orders/')) {
            document.getElementById('nav-orders')?.classList.add('active');
            const parts = hash.startsWith('#/orders/') ? hash.substring(9).split('?') : [null];
            const orderId = parts[0] ? decodeURIComponent(parts[0]) : null;
            let email = null;
            if (parts[1]) {
                const params = new URLSearchParams(parts[1]);
                email = params.get('email');
            }
            this.renderOrdersView(viewContainer, orderId, email);
        } else if (hash === '#/login' || hash === '#/register') {
            this.renderAuthView(viewContainer, hash === '#/register');
        } else {
            viewContainer.innerHTML = `
                <div class="glass-panel" style="text-align:center;padding:48px">
                    <h2>Page Not Found</h2>
                    <p style="margin: 16px 0;color:var(--text-secondary)">The page you requested does not exist.</p>
                    <a href="#/" class="btn btn-primary">Back to Catalog</a>
                </div>
            `;
        }
    },

    // Loading helper
    showSpinner(container) {
        container.innerHTML = `
            <div class="loading-view">
                <div class="spinner"></div>
                <p>Fetching data from server...</p>
            </div>
        `;
    },

    getPromotionBadgeLabel(product) {
        if (!product.active_promotions || product.active_promotions.length === 0) {
            return null;
        }
        const promos = [...product.active_promotions].sort((a, b) => b.value - a.value);
        const promo = promos[0];
        
        if (promo.type === 'percentage') {
            return `${parseInt(promo.value, 10)}% OFF`;
        } else if (promo.type === 'fixed_amount') {
            return 'SALE';
        } else if (promo.type === 'free_shipping') {
            return 'FREE SHIPPING';
        } else if (promo.type === 'buy_x_get_y') {
            return `BUY ${promo.buy_qty} GET ${promo.get_qty}`;
        }
        return null;
    },

    /* =========================================================================
       VIEW 1: Catalog (Products & Filters)
       ========================================================================= */
    async renderCatalogView(container) {
        this.showSpinner(container);
        
        try {
            // Build products endpoint URL query
            let query = `?sort=${this.state.sortBy}`;
            if (this.state.currentCategory) {
                query += `&category=${this.state.currentCategory}`;
            }
            if (this.state.searchQuery) {
                query += `&search=${encodeURIComponent(this.state.searchQuery)}`;
            }
            query += `&page=${this.state.currentPage || 1}`;

            const response = await this.api(`/products${query}`);
            const products = response.data.products || [];
            const pagination = response.data.pagination || { current_page: 1, total_pages: 1 };
            
            // Build Side category lists
            let categoriesHtml = `<button class="category-btn ${!this.state.currentCategory ? 'active' : ''}" data-slug="">✨ All Products</button>`;
            this.state.categories.forEach(c => {
                const isActive = this.state.currentCategory === c.slug;
                categoriesHtml += `
                    <button class="category-btn ${isActive ? 'active' : ''}" data-slug="${c.slug}">
                        <span>${c.icon || '📦'}</span> ${c.name}
                    </button>
                `;
            });

            // Build products grid
            let productsHtml = '';
            if (products.length === 0) {
                productsHtml = `
                    <div class="glass-panel" style="grid-column: 1/-1; text-align: center; padding: 48px 0;">
                        <p style="color:var(--text-secondary)">No products found matching the criteria.</p>
                    </div>
                `;
            } else {
                products.forEach(p => {
                    const imgUrl = p.image_thumb || p.image || '/images/placeholder.svg';
                    
                    let badgeHtml = '';
                    const promoLabel = this.getPromotionBadgeLabel(p);
                    if (promoLabel) {
                        badgeHtml = `<span class="product-badge badge-promo">${promoLabel}</span>`;
                    } else if (p.featured) {
                        badgeHtml = `<span class="product-badge badge-featured">Featured</span>`;
                    } else if (p.is_new) {
                        badgeHtml = `<span class="product-badge badge-new">New</span>`;
                    }

                    productsHtml += `
                        <div class="glass-panel product-card">
                            <a href="#/product/${p.slug}" style="text-decoration:none">
                                <div class="product-image-wrapper">
                                    ${badgeHtml}
                                    <img src="${imgUrl}" class="product-image" alt="${p.name}">
                                </div>
                            </a>
                            <a href="#/product/${p.slug}" style="text-decoration:none">
                                <h3 class="product-title">${p.name}</h3>
                            </a>
                            <div class="product-meta">
                                <span class="product-price">${this.state.currencySymbol}${parseFloat(p.price).toFixed(2)}</span>
                                <button class="btn btn-secondary btn-sm quick-add-btn" data-product-id="${p.id}">
                                    🛒 Add
                                </button>
                            </div>
                        </div>
                    `;
                });
            }

            // Build pagination HTML
            let paginationHtml = '';
            if (pagination.total_pages > 1) {
                paginationHtml += `<div class="pagination" style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:32px;">`;
                
                if (pagination.current_page > 1) {
                    paginationHtml += `
                        <button class="btn btn-outline btn-sm page-btn" data-page="${pagination.current_page - 1}">
                            &larr; Prev
                        </button>
                    `;
                }
                
                for (let i = 1; i <= pagination.total_pages; i++) {
                    const isActive = pagination.current_page === i;
                    paginationHtml += `
                        <button class="btn ${isActive ? 'btn-primary' : 'btn-outline'} btn-sm page-btn" data-page="${i}" ${isActive ? 'disabled' : ''}>
                            ${i}
                        </button>
                    `;
                }
                
                if (pagination.current_page < pagination.total_pages) {
                    paginationHtml += `
                        <button class="btn btn-outline btn-sm page-btn" data-page="${pagination.current_page + 1}">
                            Next &rarr;
                        </button>
                    `;
                }
                
                paginationHtml += `</div>`;
            }

            container.innerHTML = `
                <div class="view-header">
                    <div>
                        <h1 class="view-title">Product Catalog</h1>
                        <p style="color:var(--text-secondary);font-size:14px">Browse our premium collection</p>
                    </div>
                </div>

                <div class="catalog-layout">
                    <aside class="filters-sidebar">
                        <div class="glass-panel">
                            <h3 class="filter-group-title">Categories</h3>
                            <div class="category-list">
                                ${categoriesHtml}
                            </div>
                        </div>
                    </aside>
                    
                    <section class="catalog-content">
                        <div class="catalog-controls">
                            <div class="search-input-wrapper">
                                <svg class="search-icon icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text" class="search-input" id="catalog-search" placeholder="Search products..." value="${this.state.searchQuery}">
                            </div>
                            <select class="select-input" id="catalog-sort">
                                <option value="newest" ${this.state.sortBy === 'newest' ? 'selected' : ''}>Newest</option>
                                <option value="price_asc" ${this.state.sortBy === 'price_asc' ? 'selected' : ''}>Price: Low to High</option>
                                <option value="price_desc" ${this.state.sortBy === 'price_desc' ? 'selected' : ''}>Price: High to Low</option>
                                <option value="popular" ${this.state.sortBy === 'popular' ? 'selected' : ''}>Popular</option>
                            </select>
                        </div>

                        <div class="products-grid">
                            ${productsHtml}
                        </div>
                        ${paginationHtml}
                    </section>
                </div>
            `;

            // Register Event Handlers
            container.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const slug = e.currentTarget.getAttribute('data-slug');
                    this.state.currentPage = 1;
                    window.location.hash = slug ? `#/category/${slug}` : '#/';
                });
            });

            container.querySelectorAll('.page-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const page = parseInt(e.currentTarget.getAttribute('data-page'), 10);
                    if (page) {
                        this.state.currentPage = page;
                        this.router();
                    }
                });
            });

            const searchInput = document.getElementById('catalog-search');
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.state.searchQuery = searchInput.value;
                    this.router();
                }
            });

            document.getElementById('catalog-sort').addEventListener('change', (e) => {
                this.state.sortBy = e.target.value;
                this.router();
            });

            container.querySelectorAll('.quick-add-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const pid = e.currentTarget.getAttribute('data-product-id');
                    await this.handleAddToCart(pid);
                });
            });

        } catch (err) {
            container.innerHTML = `<div class="glass-panel text-center"><h3>Error</h3><p>${err.message}</p></div>`;
        }
    },

    /* =========================================================================
       VIEW 2: Product Detail (Includes Variants, Attributes, and Reviews)
       ========================================================================= */
    async renderProductDetailView(container, slug) {
        this.showSpinner(container);
        
        try {
            const response = await this.api(`/products/${slug}`);
            const product = response.data;
            
            // Fetch related products
            let relatedResponse = { data: { products: [] } };
            try {
                relatedResponse = await this.api(`/products/${slug}/related`);
            } catch (err) {
                console.warn("No related products could be fetched.");
            }
            const related = relatedResponse.data?.products || [];

            // Image path helper
            const imgUrl = product.image_medium || product.image || '/images/placeholder.svg';

            // Attributes build
            let attributesHtml = '';
            if (product.attributes && product.attributes.length > 0) {
                attributesHtml += `<table class="attributes-table">`;
                product.attributes.forEach(attr => {
                    attributesHtml += `<tr><td>${attr.attribute_name}</td><td>${attr.value}</td></tr>`;
                });
                attributesHtml += `</table>`;
            } else {
                attributesHtml = `<p style="color:var(--text-muted);font-size:14px">No custom specifications available.</p>`;
            }

            // Variants selector build
            let variantsHtml = '';
            if (product.variants && product.variants.length > 0) {
                variantsHtml += `
                    <div class="variant-selector">
                        <span class="form-label">Choose Options</span>
                        <div class="variant-options">
                `;
                // If not forced, display base/default version selection option
                if (!product.force_variant) {
                    variantsHtml += `
                        <div class="variant-option selected" 
                             data-variant-id="" data-price-modifier="0" data-stock="${product.stock}">
                            Default (Base)
                        </div>
                    `;
                }
                product.variants.forEach((v, index) => {
                    const isSelected = product.force_variant && index === 0;
                    variantsHtml += `
                        <div class="variant-option ${isSelected ? 'selected' : ''} ${v.stock <= 0 ? 'out-of-stock' : ''}" 
                             data-variant-id="${v.id}" data-price-modifier="${v.price_modifier || 0}" data-stock="${v.stock}">
                            ${v.name} ${v.price_modifier > 0 ? `(+${this.state.currencySymbol}${parseFloat(v.price_modifier).toFixed(2)})` : ''}
                        </div>
                    `;
                });
                variantsHtml += `</div></div>`;
            }

            // Tiered pricing table build
            let tiersHtml = '';
            if (product.tiers && product.tiers.length > 0) {
                tiersHtml += `
                    <div style="margin-top: 16px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: var(--radius-md); border: 1px solid var(--border-color)">
                        <span class="form-label" style="margin-bottom:8px; display:block">💡 Volume Discounts</span>
                        <div style="display:flex; flex-direction:column; gap:6px">
                `;
                product.tiers.forEach(t => {
                    tiersHtml += `
                        <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-secondary)">
                            <span>Buy ${t.min_qty}+ units</span>
                            <span style="font-weight:600; color:var(--status-success)">Get ${this.state.currencySymbol}${parseFloat(t.discount).toFixed(2)} off each!</span>
                        </div>
                    `;
                });
                tiersHtml += `</div></div>`;
            }

            // Bundle component components build
            let bundleHtml = '';
            if (product.is_bundle && product.bundle_items && product.bundle_items.length > 0) {
                bundleHtml += `
                    <div style="margin-top: 24px;">
                        <h3 class="filter-group-title" style="border-bottom: 1px solid var(--border-color);padding-bottom:8px">Bundle Components</h3>
                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:12px">
                `;
                product.bundle_items.forEach(bi => {
                    bundleHtml += `
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;background:rgba(255,255,255,0.02);border-radius:var(--radius-sm)">
                            <span style="font-size:14px;font-weight:600">${bi.name} (x${bi.quantity})</span>
                            <span style="font-family:var(--font-heading);font-weight:700;color:var(--accent-secondary)">${this.state.currencySymbol}${parseFloat(bi.price).toFixed(2)}</span>
                        </div>
                    `;
                });
                bundleHtml += `</div></div>`;
            }

            // Review section build
            let reviewsHtml = '';
            if (product.reviews && product.reviews.length > 0) {
                reviewsHtml += `<div class="reviews-grid">`;
                product.reviews.forEach(r => {
                    const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
                    reviewsHtml += `
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-author">${r.user_name || 'Anonymous'}</span>
                                <span class="review-stars">${stars}</span>
                            </div>
                            <p class="review-text">${r.comment}</p>
                        </div>
                    `;
                });
                reviewsHtml += `</div>`;
            } else {
                reviewsHtml = `<p style="color:var(--text-muted);font-size:14px;grid-column:1/-1">No reviews yet. Be the first to review this product!</p>`;
            }

            // Related products build
            let relatedHtml = '';
            if (related.length > 0) {
                relatedHtml += `
                    <div style="margin-top: 48px;">
                        <h2 class="reviews-title">You May Also Like</h2>
                        <div class="products-grid">
                `;
                related.slice(0, 4).forEach(rp => {
                    relatedHtml += `
                        <div class="glass-panel product-card">
                            <a href="#/product/${rp.slug}" style="text-decoration:none">
                                <div class="product-image-wrapper">
                                    <img src="${rp.image_thumb || rp.image || '/images/placeholder.svg'}" class="product-image" alt="${rp.name}">
                                </div>
                            </a>
                            <a href="#/product/${rp.slug}" style="text-decoration:none">
                                <h3 class="product-title" style="height:38px">${rp.name}</h3>
                            </a>
                            <div class="product-meta">
                                <span class="product-price">${this.state.currencySymbol}${parseFloat(rp.price).toFixed(2)}</span>
                                <a href="#/product/${rp.slug}" class="btn btn-outline btn-sm">View Details</a>
                            </div>
                        </div>
                    `;
                });
                relatedHtml += `</div></div>`;
            }

            // Active Promotion callout
            let promoHtml = '';
            if (product.active_promotions && product.active_promotions.length > 0) {
                const promos = [...product.active_promotions].sort((a, b) => b.value - a.value);
                const promo = promos[0];
                
                let badgeLabel = 'OFFER';
                if (promo.type === 'percentage') {
                    badgeLabel = `${parseInt(promo.value, 10)}% OFF`;
                } else if (promo.type === 'fixed_amount') {
                    badgeLabel = 'SALE';
                } else if (promo.type === 'buy_x_get_y') {
                    badgeLabel = 'BOGO';
                } else if (promo.type === 'free_shipping') {
                    badgeLabel = 'SHIP';
                }

                let detailsText = '';
                if (promo.type === 'buy_x_get_y') {
                    const freeOrOff = promo.value >= 100 ? 'FREE' : `${parseInt(promo.value, 10)}% OFF`;
                    detailsText = `<strong>Buy ${promo.buy_qty}, Get ${promo.get_qty} ${freeOrOff}</strong>`;
                    if (promo.description) {
                        detailsText += `<br>${promo.description}`;
                    }
                } else {
                    detailsText = promo.description || '';
                }

                if (promo.min_order_amount > 0) {
                    const separator = (promo.description || promo.type === 'buy_x_get_y') ? ' &bull; ' : '';
                    detailsText += `${separator}Min. spend ${this.state.currencySymbol}${parseFloat(promo.min_order_amount).toFixed(2)}`;
                }

                let promoCodeBox = '';
                if (promo.code) {
                    promoCodeBox = `
                        <div class="promo-code-box">
                            <span class="promo-code-label">Use code:</span>
                            <span class="promo-code-value">${promo.code}</span>
                        </div>
                    `;
                }

                promoHtml = `
                    <div class="promo-callout">
                        <div class="promo-icon">🏷️</div>
                        <div class="promo-text">
                            <h4>${promo.name}</h4>
                            <p>${detailsText}</p>
                            ${promoCodeBox}
                        </div>
                        <div class="promo-badge">${badgeLabel}</div>
                    </div>
                `;
            }

            container.innerHTML = `
                <div style="margin-bottom: 24px;">
                    <a href="#/" class="btn btn-outline btn-sm">← Back to Catalog</a>
                </div>

                <div class="glass-panel product-detail-layout">
                    <div class="detail-image-wrapper">
                        <img src="${imgUrl}" class="detail-image" alt="${product.name}">
                    </div>
                    
                    <div class="detail-info">
                        <h1 class="detail-name">${product.name}</h1>
                        <div class="detail-price" id="detail-display-price">${this.state.currencySymbol}${parseFloat(product.price).toFixed(2)}</div>
                        <p class="detail-description">${product.description || 'No description available for this product.'}</p>
                        
                        ${promoHtml}
                        ${variantsHtml}
                        ${tiersHtml}

                        <div style="display:flex;gap:16px;margin-top:16px">
                            <button class="btn btn-primary" id="add-to-cart-detail" style="flex:1">🛒 Add To Cart</button>
                            <button class="btn btn-outline" id="add-to-wishlist-detail" title="Add to Wishlist">❤️ Wishlist</button>
                        </div>
                        
                        ${bundleHtml}
                        
                        <div style="margin-top: 24px;">
                            <h3 class="filter-group-title" style="border-bottom: 1px solid var(--border-color);padding-bottom:8px">Product Specifications</h3>
                            ${attributesHtml}
                        </div>
                    </div>
                </div>

                <div class="glass-panel reviews-section">
                    <h2 class="reviews-title">Customer Reviews</h2>
                    ${reviewsHtml}

                    ${this.state.user ? `
                        <form class="review-form" id="submit-review-form">
                            <h3 class="filter-group-title">Write a Review</h3>
                            <div class="form-group">
                                <label class="form-label">Rating</label>
                                <select class="form-control" name="rating" required>
                                    <option value="5">⭐⭐⭐⭐⭐ 5 Stars</option>
                                    <option value="4">⭐⭐⭐⭐ 4 Stars</option>
                                    <option value="3">⭐⭐⭐ 3 Stars</option>
                                    <option value="2">⭐⭐ 2 Stars</option>
                                    <option value="1">⭐ 1 Star</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Review Comments</label>
                                <textarea class="form-control" name="comment" rows="4" placeholder="Share your experience with this product..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    ` : `
                        <div style="margin-top:24px;padding:16px;background:rgba(255,255,255,0.02);border-radius:var(--radius-md);text-align:center">
                            <p style="color:var(--text-secondary);font-size:14px">Please <a href="#/login" style="color:var(--accent-primary)">Sign In</a> to write a review.</p>
                        </div>
                    `}
                </div>

                ${relatedHtml}
            `;

            // Handle Variant switching and prices
            let selectedVariantId = null;
            const options = container.querySelectorAll('.variant-option');
            if (options.length > 0) {
                const firstOpt = container.querySelector('.variant-option.selected');
                selectedVariantId = firstOpt ? firstOpt.getAttribute('data-variant-id') || null : null;
                if (selectedVariantId) selectedVariantId = parseInt(selectedVariantId);
                
                options.forEach(opt => {
                    opt.addEventListener('click', (e) => {
                        options.forEach(o => o.classList.remove('selected'));
                        e.currentTarget.classList.add('selected');
                        
                        const vId = e.currentTarget.getAttribute('data-variant-id');
                        selectedVariantId = vId ? parseInt(vId) : null;
                        
                        // Recalculate displays
                        const mod = parseFloat(e.currentTarget.getAttribute('data-price-modifier') || 0);
                        const basePrice = parseFloat(product.price);
                        document.getElementById('detail-display-price').textContent = `${this.state.currencySymbol}${(basePrice + mod).toFixed(2)}`;
                    });
                });
            }

            // Click Handlers
            document.getElementById('add-to-cart-detail').addEventListener('click', async () => {
                await this.handleAddToCart(product.id, selectedVariantId);
            });

            document.getElementById('add-to-wishlist-detail').addEventListener('click', async () => {
                await this.handleAddToWishlist(product.id);
            });

            // Review Form handler
            const reviewForm = document.getElementById('submit-review-form');
            if (reviewForm) {
                reviewForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(reviewForm);
                    try {
                        const response = await this.api(`/products/${slug}/review`, {
                            method: 'POST',
                            body: JSON.stringify({
                                rating: parseInt(formData.get('rating')),
                                comment: formData.get('comment')
                            })
                        });
                        if (response.success) {
                            this.showToast("Review submitted successfully!", "success");
                            this.router(); // Reload view
                        }
                    } catch (err) {
                        this.showToast(err.message, "error");
                    }
                });
            }

        } catch (err) {
            container.innerHTML = `<div class="glass-panel text-center"><h3>Error loading product</h3><p>${err.message}</p></div>`;
        }
    },

    // Global Add to Cart action handler
    async handleAddToCart(productId, variantId = null) {
        try {
            const payload = { 
                product_id: parseInt(productId), 
                quantity: 1 
            };
            if (variantId) {
                payload.variant_id = parseInt(variantId);
            }
            const response = await this.api('/cart/add', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            if (response.success) {
                this.state.cart = response.data;
                this.updateCartBadge();
                this.showToast("Product added to cart!", "success");
            }
        } catch (err) {
            this.showToast(err.message, "error");
        }
    },

    // Global Add to Wishlist action
    async handleAddToWishlist(productId) {
        if (!this.state.user) {
            this.showToast("Please sign in to manage your wishlist.", "error");
            window.location.hash = '#/login';
            return;
        }
        try {
            const response = await this.api(`/wishlist/add/${productId}`, {
                method: 'POST'
            });
            if (response.success) {
                this.showToast("Saved to your wishlist!", "success");
            }
        } catch (err) {
            this.showToast(err.message, "error");
        }
    },

    /* =========================================================================
       VIEW 3: Shopping Cart Detail
       ========================================================================= */
    renderCartView(container) {
        const cart = this.state.cart;
        
        if (!cart || !cart.items || cart.items.length === 0) {
            container.innerHTML = `
                <div class="glass-panel" style="text-align:center;padding:64px 24px">
                    <h1 class="view-title" style="margin-bottom:16px">Your Shopping Cart is Empty</h1>
                    <p style="color:var(--text-secondary);margin-bottom:24px">You haven't added any premium goodies to your cart yet.</p>
                    <a href="#/" class="btn btn-primary">Start Shopping</a>
                </div>
            `;
            return;
        }

        let itemsHtml = '';
        cart.items.forEach(item => {
            const imgUrl = item.image_thumb || item.image || '/images/placeholder.svg';
            const itemPrice = parseFloat(item.unit_price || 0);
            const itemSubtotal = parseFloat(item.subtotal || (itemPrice * item.quantity));
            itemsHtml += `
                <div class="glass-panel cart-item" data-key="${item.key}">
                    <img src="${imgUrl}" class="cart-item-image" alt="${item.name || 'Product'}">
                    <div class="cart-item-info">
                        <h4 class="cart-item-title">${item.name || 'Unknown Item'}</h4>
                        ${item.variant_name ? `<span style="font-size:11px;color:var(--text-muted)">Option: ${item.variant_name}</span>` : ''}
                    </div>
                    <div class="cart-item-qty">
                        <button class="qty-btn val-decrease">-</button>
                        <span class="qty-val">${item.quantity}</span>
                        <button class="qty-btn val-increase">+</button>
                    </div>
                    <div class="cart-item-actions" style="display:flex;align-items:center;gap:16px">
                        <span class="cart-item-price">${this.state.currencySymbol}${itemSubtotal.toFixed(2)}</span>
                        <button class="btn btn-danger btn-sm cart-remove-btn">✕</button>
                    </div>
                </div>
            `;
        });

        const sum = cart.summary;
        const subtotal = parseFloat(sum.subtotal || 0);
        const vat = parseFloat(sum.total_vat || 0);
        const discount = parseFloat(sum.discount || 0);
        const grandTotal = parseFloat(sum.grand_total || 0);

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Shopping Cart</h1>
            </div>

            <div class="cart-layout">
                <div class="cart-items">
                    ${itemsHtml}
                </div>

                <div class="cart-summary glass-panel">
                    <h3 class="filter-group-title">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>${this.state.currencySymbol}${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (VAT)</span>
                        <span>${this.state.currencySymbol}${vat.toFixed(2)}</span>
                    </div>
                    <div class="summary-row" style="color:var(--status-success)">
                        <span>Discount</span>
                        <span>-${this.state.currencySymbol}${discount.toFixed(2)}</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>${this.state.currencySymbol}${grandTotal.toFixed(2)}</span>
                    </div>

                    <!-- Promo code input -->
                    <div style="margin-top:12px;border-top:1px solid var(--border-color);padding-top:16px">
                        <form id="promo-form" style="display:flex;gap:8px">
                            <input type="text" class="form-control" placeholder="Promo Code" id="promo-code" style="padding:8px 12px">
                            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                        </form>
                    </div>

                    <a href="#/checkout" class="btn btn-primary" style="margin-top:12px;width:100%">Proceed to Checkout</a>
                </div>
            </div>
        `;

        // Register handlers for quantity updates
        container.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const parent = e.currentTarget.closest('.cart-item');
                const key = parent.getAttribute('data-key');
                const change = e.currentTarget.classList.contains('val-increase') ? 1 : -1;
                
                const existingItem = this.state.cart.items.find(i => i.key === key);
                if (existingItem) {
                    const newQty = existingItem.quantity + change;
                    if (newQty <= 0) {
                        await this.handleRemoveCartItem(key);
                    } else {
                        await this.handleUpdateCartQty(key, newQty);
                    }
                }
            });
        });

        container.querySelectorAll('.cart-remove-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const parent = e.currentTarget.closest('.cart-item');
                const key = parent.getAttribute('data-key');
                await this.handleRemoveCartItem(key);
            });
        });

        // Promo form submission
        const promoForm = document.getElementById('promo-form');
        promoForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = document.getElementById('promo-code').value;
            try {
                const response = await this.api('/cart/promo', {
                    method: 'POST',
                    body: JSON.stringify({ promo_code: code })
                });
                if (response.success) {
                    this.state.cart = response.data;
                    this.showToast("Promo code applied successfully!", "success");
                    this.router();
                }
            } catch (err) {
                this.showToast(err.message, "error");
            }
        });
    },

    async handleUpdateCartQty(key, qty) {
        try {
            const response = await this.api('/cart/update', {
                method: 'POST',
                body: JSON.stringify({ key, quantity: qty })
            });
            if (response.success) {
                this.state.cart = response.data;
                this.updateCartBadge();
                this.router();
            }
        } catch (err) {
            this.showToast(err.message, "error");
        }
    },

    async handleRemoveCartItem(key) {
        await this.handleUpdateCartQty(key, 0);
        this.showToast("Item removed from cart.", "info");
    },

    /* =========================================================================
       VIEW 4: Checkout Page
       ========================================================================= */
    async renderCheckoutView(container) {
        if (!this.state.cart || !this.state.cart.items || this.state.cart.items.length === 0) {
            window.location.hash = '#/cart';
            return;
        }

        this.showSpinner(container);
        
        let addresses = [];
        if (this.state.user) {
            try {
                const response = await this.api('/account/addresses');
                addresses = response.data || [];
            } catch (err) {
                console.warn("Failed to load saved addresses.");
            }
        }

        // Check if cart has physical items
        const hasPhysical = this.state.cart.items.some(item => !item.is_virtual);

        // Fetch delivery options dynamically if physical items exist
        let deliveryOptionsHtml = '';
        if (hasPhysical) {
            try {
                const deliveryRes = await this.api('/delivery-options');
                if (deliveryRes.success && deliveryRes.data.length > 0) {
                    deliveryOptionsHtml = `
                        <div class="form-group" style="margin-top:12px">
                            <label class="form-label">Delivery Method</label>
                            <select class="form-control" name="delivery_option_id" id="delivery-option-select" required>
                                ${deliveryRes.data.map(opt => `
                                    <option value="${opt.id}" data-price="${opt.price}">${opt.name} (${this.state.currencySymbol}${parseFloat(opt.price).toFixed(2)})</option>
                                `).join('')}
                            </select>
                        </div>
                    `;
                }
            } catch (err) {
                console.error("Failed to load delivery options", err);
            }
        }

        // Generate addresses selection list
        let addressesHtml = '';
        if (addresses.length > 0 && hasPhysical) {
            addressesHtml += `
                <div class="checkout-section-title">Saved Shipping Addresses</div>
                <div class="address-grid">
            `;
            addresses.forEach((addr, index) => {
                addressesHtml += `
                    <div class="address-card ${index === 0 ? 'selected' : ''}" data-address-id="${addr.id}"
                         data-address="${addr.address}" data-city="${addr.city}" data-postcode="${addr.postcode}" data-country="${addr.country || 'United Kingdom'}">
                        <div class="address-name">${addr.name || this.state.user.name}</div>
                        <div class="address-details">
                            ${addr.address}<br>
                            ${addr.city}, ${addr.postcode}
                        </div>
                    </div>
                `;
            });
            addressesHtml += `</div>`;
        }

        const sum = this.state.cart.summary;
        const grandTotal = parseFloat(sum.grand_total || 0);
        const subtotal = parseFloat(sum.subtotal || 0);
        const vat = parseFloat(sum.total_vat || 0);

        container.innerHTML = `
            <div class="view-header">
                <h1 class="view-title">Order Checkout</h1>
            </div>

            <div class="checkout-layout">
                <div class="glass-panel">
                    <form id="checkout-form" style="display:flex;flex-direction:column;gap:20px">
                        
                        ${addressesHtml}

                        <div class="checkout-section-title">Contact Information</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                            <div class="form-group">
                                <label class="form-label">Recipient Name</label>
                                <input type="text" class="form-control" name="name" id="shipping-name" value="${this.state.user?.name || ''}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" id="shipping-email" value="${this.state.user?.email || ''}" required>
                            </div>
                        </div>

                        ${hasPhysical ? `
                            <div class="checkout-section-title">Shipping Details</div>
                            
                            <div class="form-group">
                                <label class="form-label">Address Line 1</label>
                                <input type="text" class="form-control" name="address" id="shipping-line1" required>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" id="shipping-city" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" name="postcode" id="shipping-postal" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" id="shipping-country" value="United Kingdom" required>
                            </div>

                            ${deliveryOptionsHtml}
                        ` : ''}

                        <div class="checkout-section-title" style="margin-top:12px">Payment Mock Details</div>
                        <p style="font-size:12px;color:var(--text-secondary)">No real payment is processed. You can insert dummy details below.</p>
                        
                        <div style="display:grid;grid-template-columns:1.5fr 1fr 1fr;gap:16px">
                            <div class="form-group">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" name="card_number" id="card-number-input" placeholder="4242 4242 4242 4242" maxlength="19" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Expiry</label>
                                <input type="text" class="form-control" name="card_expiry" id="card-expiry-input" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">CVC</label>
                                <input type="text" class="form-control" name="card_cvc" id="card-cvc-input" placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <button type="submit" id="place-order-btn" class="btn btn-primary" style="margin-top:16px;align-self:flex-start">Place Order (${this.state.currencySymbol}${grandTotal.toFixed(2)})</button>
                    </form>
                </div>

                <div class="cart-summary glass-panel">
                    <h3 class="filter-group-title">Summary Review</h3>
                    <div style="max-height: 200px; overflow-y: auto; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px">
                        ${this.state.cart.items.map(item => `
                            <div style="display:flex;justify-content:between;gap:8px;font-size:12px;margin-bottom:8px">
                                <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${item.name}</span>
                                <span>x${item.quantity}</span>
                                <span>${this.state.currencySymbol}${parseFloat(item.subtotal || (item.unit_price * item.quantity)).toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>${this.state.currencySymbol}${subtotal.toFixed(2)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>${this.state.currencySymbol}${vat.toFixed(2)}</span>
                    </div>
                    <div class="summary-row" id="delivery-summary-row" style="display: ${hasPhysical ? 'flex' : 'none'}">
                        <span>Delivery</span>
                        <span id="delivery-summary-value">${this.state.currencySymbol}0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Total</span>
                        <span id="summary-grand-total" style="font-weight:700;color:var(--accent-secondary)">${this.state.currencySymbol}${grandTotal.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;

        // Handle addresses pre-filling
        const addressCards = container.querySelectorAll('.address-card');
        const fillAddress = (card) => {
            if (!card) return;
            document.getElementById('shipping-line1').value = card.getAttribute('data-address') || '';
            document.getElementById('shipping-city').value = card.getAttribute('data-city') || '';
            document.getElementById('shipping-postal').value = card.getAttribute('data-postcode') || '';
            if (document.getElementById('shipping-country')) {
                document.getElementById('shipping-country').value = card.getAttribute('data-country') || 'United Kingdom';
            }
        };

        if (addressCards.length > 0 && hasPhysical) {
            fillAddress(addressCards[0]); // Fill first default
            addressCards.forEach(c => {
                c.addEventListener('click', (e) => {
                    addressCards.forEach(card => card.classList.remove('selected'));
                    e.currentTarget.classList.add('selected');
                    fillAddress(e.currentTarget);
                });
            });
        }

        // Handle delivery option changes and update total
        const deliverySelect = document.getElementById('delivery-option-select');
        const updateTotals = () => {
            if (!deliverySelect) return;
            const selectedOpt = deliverySelect.options[deliverySelect.selectedIndex];
            if (!selectedOpt) return;
            const deliveryPrice = parseFloat(selectedOpt.getAttribute('data-price') || 0);
            
            // Update summary delivery row
            const deliveryValueSpan = document.getElementById('delivery-summary-value');
            if (deliveryValueSpan) {
                deliveryValueSpan.textContent = `${this.state.currencySymbol}${deliveryPrice.toFixed(2)}`;
            }
            
            // Update grand total
            const updatedGrandTotal = grandTotal + deliveryPrice;
            const grandTotalSpan = document.getElementById('summary-grand-total');
            if (grandTotalSpan) {
                grandTotalSpan.textContent = `${this.state.currencySymbol}${updatedGrandTotal.toFixed(2)}`;
            }
            
            // Update button
            const placeOrderBtn = document.getElementById('place-order-btn');
            if (placeOrderBtn) {
                placeOrderBtn.textContent = `Place Order (${this.state.currencySymbol}${updatedGrandTotal.toFixed(2)})`;
            }
        };

        if (deliverySelect) {
            deliverySelect.addEventListener('change', updateTotals);
            updateTotals(); // Initialize
        }

        // Card inputs formatting
        const cardNumInput = document.getElementById('card-number-input');
        const cardExpInput = document.getElementById('card-expiry-input');
        const cardCvcInput = document.getElementById('card-cvc-input');

        if (cardNumInput) {
            cardNumInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '');
                let formatted = val.match(/.{1,4}/g);
                e.target.value = formatted ? formatted.join(' ') : '';
            });
        }

        if (cardExpInput) {
            cardExpInput.addEventListener('input', (e) => {
                let val = e.target.value.replace(/\D/g, '');
                if (val.length >= 2) {
                    e.target.value = val.substring(0, 2) + '/' + val.substring(2, 4);
                } else {
                    e.target.value = val;
                }
            });
        }

        if (cardCvcInput) {
            cardCvcInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }

        // Checkout submission Form
        const checkoutForm = document.getElementById('checkout-form');
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(checkoutForm);
            
            const postBody = {
                name: formData.get('name'),
                email: formData.get('email'),
                card_number: formData.get('card_number') ? formData.get('card_number').replace(/\s/g, '') : '',
                card_expiry: formData.get('card_expiry') || '',
                card_cvc: formData.get('card_cvc') || '',
            };

            if (hasPhysical) {
                postBody.address = formData.get('address');
                postBody.city = formData.get('city');
                postBody.postcode = formData.get('postcode');
                postBody.country = formData.get('country');
                postBody.delivery_option_id = parseInt(formData.get('delivery_option_id'));
            }

            try {
                const response = await this.api('/checkout', {
                    method: 'POST',
                    body: JSON.stringify(postBody)
                });

                if (response.success) {
                    this.showToast("Order placed successfully!", "success");
                    
                    // Clear cart details locally
                    this.state.cart = null;
                    this.updateCartBadge();
                    
                    const orderId = response.data?.order_reference || response.data?.order_id || response.data?.id;
                    const emailParam = postBody.email ? `?email=${encodeURIComponent(postBody.email)}` : '';
                    window.location.hash = orderId ? `#/orders/${encodeURIComponent(orderId)}${emailParam}` : '#/orders';
                }
            } catch (err) {
                this.showToast(err.message, "error");
            }
        });
    },

    /* =========================================================================
       VIEW 5: Wishlist View (requires authenticated session)
       ========================================================================= */
    async renderWishlistView(container) {
        if (!this.state.user) {
            container.innerHTML = `
                <div class="glass-panel" style="text-align:center;padding:48px 24px">
                    <h1 class="view-title" style="margin-bottom:16px">Wishlist Account Required</h1>
                    <p style="color:var(--text-secondary);margin-bottom:24px">Please sign in to keep track and manage your wishlist.</p>
                    <a href="#/login" class="btn btn-primary">Sign In</a>
                </div>
            `;
            return;
        }

        this.showSpinner(container);

        try {
            const response = await this.api('/wishlist');
            const items = response.data?.wishlist || response.data?.items || [];
            
            let itemsHtml = '';
            if (items.length === 0) {
                itemsHtml = `
                    <div class="glass-panel" style="grid-column:1/-1;text-align:center;padding:48px">
                        <p style="color:var(--text-secondary)">Your wishlist is currently empty.</p>
                    </div>
                `;
            } else {
                items.forEach(item => {
                    itemsHtml += `
                        <div class="glass-panel wishlist-card" data-product-id="${item.product_id || item.id}">
                            <img src="${item.image_thumb || item.image || '/images/placeholder.svg'}" class="wishlist-card-image" alt="${item.name}">
                            <div class="wishlist-card-info">
                                <h4 style="font-size:15px">${item.name}</h4>
                                <span style="font-weight:700;color:var(--accent-secondary)">${this.state.currencySymbol}${parseFloat(item.price).toFixed(2)}</span>
                            </div>
                            <div style="display:flex;gap:8px">
                                <a href="#/product/${item.slug}" class="btn btn-outline btn-sm">View</a>
                                <button class="btn btn-danger btn-sm wishlist-remove-btn" data-product-id="${item.product_id || item.id}">✕</button>
                            </div>
                        </div>
                    `;
                });
            }

            container.innerHTML = `
                <div class="view-header">
                    <h1 class="view-title">Saved Wishlist</h1>
                </div>
                
                <div class="wishlist-grid">
                    ${itemsHtml}
                </div>
            `;

            container.querySelectorAll('.wishlist-remove-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const prodId = e.currentTarget.getAttribute('data-product-id');
                    try {
                        const response = await this.api(`/wishlist/remove/${prodId}`, { method: 'POST' });
                        if (response.success) {
                            this.showToast("Removed from wishlist", "info");
                            this.router();
                        }
                    } catch (err) {
                        this.showToast(err.message, "error");
                    }
                });
            });

        } catch (err) {
            container.innerHTML = `<div class="glass-panel text-center"><h3>Error</h3><p>${err.message}</p></div>`;
        }
    },

    /* =========================================================================
       VIEW 6: Track Orders & Lookup
       ========================================================================= */
    async renderOrdersView(container, orderId = null, lookupEmail = null) {
        // Formatting helper to ensure order references display as #000098
        const formatRef = (refOrId) => {
            if (!refOrId) return '';
            const clean = String(refOrId).replace('#', '');
            if (/^\d+$/.test(clean)) {
                return '#' + clean.padStart(6, '0');
            }
            return refOrId;
        };

        // If an orderId parameter is passed, show detailed single order view
        if (orderId) {
            this.showSpinner(container);
            try {
                // If it is authenticated, fetch details, otherwise check payload lookup
                let order = null;
                const decodedOrderId = decodeURIComponent(orderId);
                if (this.state.user) {
                    const cleanId = String(decodedOrderId).replace('#', '');
                    const response = await this.api(`/orders/${cleanId}`);
                    order = response.data;
                } else {
                    // Try tracking endpoint /api/v1/orders/lookup
                    const response = await this.api('/orders/lookup', {
                        method: 'POST',
                        body: JSON.stringify({ 
                            order_reference: decodedOrderId,
                            email: lookupEmail
                        })
                    });
                    order = response.data;
                }

                if (!order) {
                    throw new Error("Order not found or access denied.");
                }

                const items = order.items || [];
                const trackingStatus = order.status || 'pending';

                container.innerHTML = `
                    <div style="margin-bottom:24px">
                        <a href="#/orders" class="btn btn-outline btn-sm">← Back to Tracking</a>
                    </div>
                    
                    <div class="glass-panel" style="max-width:700px;margin:0 auto">
                        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-color);padding-bottom:16px;margin-bottom:20px">
                            <div>
                                <h2 class="filter-group-title" style="margin:0">Order Reference: ${formatRef(order.order_reference || order.id)}</h2>
                                <p style="font-size:12px;color:var(--text-secondary)">Placed on ${order.created_at || 'Unknown'}</p>
                            </div>
                            <span class="order-badge ${trackingStatus}">${trackingStatus}</span>
                        </div>

                        <h3 class="checkout-section-title">Shipping Destination</h3>
                        <p style="font-size:14px;color:var(--text-secondary);margin-bottom:20px">
                            <strong>${order.name || order.customer_name || 'Recipient'}</strong><br>
                            ${(order.shipping_address || '').replace(/\n/g, '<br>')}
                        </p>

                        <h3 class="checkout-section-title">Order Items</h3>
                        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
                            ${items.map(item => `
                                <div style="display:flex;flex-direction:column;gap:6px;padding:12px;background:rgba(255,255,255,0.02);border-radius:var(--radius-sm)">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <span style="font-size:14px;font-weight:600">${item.name} x${item.qty}</span>
                                        <span style="font-family:var(--font-heading);font-weight:700;color:var(--accent-secondary)">${this.state.currencySymbol}${parseFloat(item.total).toFixed(2)}</span>
                                    </div>
                                    ${item.variant_name ? `
                                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">Option: ${item.variant_name}</div>
                                    ` : ''}
                                    ${item.is_bundle && item.bundle_components && item.bundle_components.length > 0 ? `
                                        <div style="font-size:12px;margin-top:6px;padding:8px;background:rgba(255,255,255,0.01);border-radius:var(--radius-sm)">
                                            <div style="font-weight:600;color:var(--text-secondary);text-transform:uppercase;font-size:10px;margin-bottom:4px;letter-spacing:0.03em">Includes:</div>
                                            <ul style="margin:0;padding-left:16px;list-style:disc;color:var(--text-muted)">
                                                ${item.bundle_components.map(bc => `<li>${bc.qty} × ${bc.name}</li>`).join('')}
                                            </ul>
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>

                        <div style="border-top:1px solid var(--border-color);padding-top:16px">
                            <div class="summary-row">
                                <span>Shipping Mode</span>
                                <span>${order.delivery_method || 'Standard'}</span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping Cost</span>
                                <span>${this.state.currencySymbol}${parseFloat(order.delivery_cost || 0).toFixed(2)}</span>
                            </div>
                            ${parseFloat(order.discount || 0) > 0 ? `
                                <div class="summary-row">
                                    <span>Discount Applied</span>
                                    <span style="color:var(--status-success)">-${this.state.currencySymbol}${parseFloat(order.discount).toFixed(2)}</span>
                                </div>
                            ` : ''}
                            <div class="summary-row">
                                <span>Tax (VAT)</span>
                                <span>${this.state.currencySymbol}${parseFloat(order.total_vat || 0).toFixed(2)}</span>
                            </div>
                            <div class="summary-row total" style="margin-top:10px">
                                <span>Total Price</span>
                                <span>${this.state.currencySymbol}${parseFloat(order.total).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            } catch (err) {
                container.innerHTML = `
                    <div style="margin-bottom:24px">
                        <a href="#/orders" class="btn btn-outline btn-sm">← Back to Tracking</a>
                    </div>
                    <div class="glass-panel text-center">
                        <h3>Order Lookup Failed</h3>
                        <p style="color:var(--text-secondary);margin-top:8px">${err.message}</p>
                    </div>
                `;
            }
            return;
        }

        // Show generic Track Orders / Search Dashboard
        this.showSpinner(container);

        try {
            let authenticatedOrdersHtml = '';
            if (this.state.user) {
                try {
                    const response = await this.api('/orders');
                    const ordersList = response.data?.orders || response.data || [];
                    
                    if (ordersList.length === 0) {
                        authenticatedOrdersHtml = `<p style="color:var(--text-muted);font-size:14px">You have no order history yet.</p>`;
                    } else {
                        authenticatedOrdersHtml = `<div class="order-list">`;
                        ordersList.forEach(o => {
                            authenticatedOrdersHtml += `
                                <a href="#/orders/${o.id}">
                                    <div class="glass-panel order-item-card">
                                        <div class="order-id-date">
                                            <span class="order-id">${formatRef(o.order_reference)}</span>
                                            <span class="order-date">${o.created_at || ''} &bull; ${this.state.currencySymbol}${parseFloat(o.total).toFixed(2)}</span>
                                        </div>
                                        <span class="order-badge ${o.status || 'pending'}">${o.status || 'pending'}</span>
                                    </div>
                                </a>
                            `;
                        });
                        authenticatedOrdersHtml += `</div>`;
                    }
                } catch (err) {
                    authenticatedOrdersHtml = `<p style="color:var(--status-error)">Could not retrieve order logs.</p>`;
                }
            }

            container.innerHTML = `
                <div class="view-header">
                    <h1 class="view-title">Track Orders</h1>
                </div>

                <div class="order-tracking-card glass-panel">
                    <h3 class="filter-group-title" style="text-align:center;margin-bottom:16px">Track Guest Order</h3>
                    <form id="order-lookup-form" style="display:flex;flex-direction:column;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Order Number / Reference</label>
                            <input type="text" class="form-control" id="lookup-number" placeholder="e.g. #000098 or 98" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Billing Email Address</label>
                            <input type="email" class="form-control" id="lookup-email" placeholder="e.g. customer@example.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Lookup Order</button>
                    </form>
                </div>

                ${this.state.user ? `
                    <div class="glass-panel" style="max-width:600px;margin:32px auto 0 auto">
                        <h3 class="filter-group-title" style="margin-bottom:16px">Your Order History</h3>
                        ${authenticatedOrdersHtml}
                    </div>
                ` : `
                    <div style="max-width:500px;margin:32px auto 0 auto;text-align:center;padding:16px;background:rgba(255,255,255,0.01);border-radius:var(--radius-md)">
                        <p style="color:var(--text-secondary);font-size:13px">Want to see all your orders in one place? <a href="#/login" style="color:var(--accent-primary)">Sign In</a> to save history.</p>
                    </div>
                `}
            `;

            // Lookup Submission handler
            const lookupForm = document.getElementById('order-lookup-form');
            lookupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const num = document.getElementById('lookup-number').value.trim();
                const email = document.getElementById('lookup-email').value.trim();
                if (num && email) {
                    window.location.hash = `#/orders/${encodeURIComponent(num)}?email=${encodeURIComponent(email)}`;
                }
            });

        } catch (err) {
            container.innerHTML = `<div class="glass-panel text-center"><h3>Error loading tracker</h3><p>${err.message}</p></div>`;
        }
    },

    /* =========================================================================
       VIEW 7: Login & Registration Dashboard
       ========================================================================= */
    renderAuthView(container, isRegister = false) {
        container.innerHTML = `
            <div class="glass-panel auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">${isRegister ? 'Create Account' : 'Welcome Back'}</h2>
                    <p class="auth-subtitle">${isRegister ? 'Sign up to manage wishlists and order history' : 'Sign in to access your saved shop profile'}</p>
                </div>

                <form id="auth-form" style="display:flex;flex-direction:column;gap:16px">
                    ${isRegister ? `
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required placeholder="John Doe">
                        </div>
                    ` : ''}
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" required placeholder="customer@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required placeholder="••••••••">
                    </div>

                    ${isRegister ? `
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation" required placeholder="••••••••">
                        </div>
                    ` : ''}

                    <button type="submit" class="btn btn-primary" style="margin-top:8px">${isRegister ? 'Register Account' : 'Sign In'}</button>
                </form>

                <div class="auth-footer">
                    ${isRegister ? `
                        Already have an account? <a href="#/login">Sign In instead</a>
                    ` : `
                        Don't have an account? <a href="#/register">Create one now</a>
                    `}
                </div>
            </div>
        `;

        // Handle Submission
        const authForm = document.getElementById('auth-form');
        authForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(authForm);
            const payload = {};
            formData.forEach((value, key) => payload[key] = value);
            
            const endpoint = isRegister ? '/auth/register' : '/auth/login';
            try {
                const response = await this.api(endpoint, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });

                if (response.success && response.data.token) {
                    this.state.token = response.data.token;
                    localStorage.setItem('auth_token', response.data.token);
                    this.state.user = response.data.user;
                    this.updateAuthWidget();
                    
                    this.showToast(`Welcome back, ${response.data.user.name}!`, "success");
                    
                    // Sync up cart immediately
                    await this.syncCart();
                    
                    window.location.hash = '#/';
                }
            } catch (err) {
                this.showToast(err.message, "error");
            }
        });
    }
};

// Initialize Application once DOM loaded
document.addEventListener('DOMContentLoaded', () => App.init());
