'use strict';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Utilities ────────────────────────────────────────────────────────────────

async function postAjax(url, params) {
    params.csrf_token = csrfToken;
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams(params),
    });
    const data = await res.json();
    return data;
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `alert alert-${type} toast-enter`;
    toast.style.pointerEvents = 'auto';
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('toast-enter');
        toast.classList.add('toast-exit');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, 3000);
}

function formatMoney(amount, symbol = '£') {
    return symbol + parseFloat(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateCartBadge(count) {
    const link = document.getElementById('cart-link');
    if (!link) return;
    let badge = link.querySelector('.cart-badge');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            link.appendChild(badge);
        }
        badge.textContent = count;
    } else if (badge) {
        badge.remove();
    }
}

// ── Add to Cart ──────────────────────────────────────────────────────────────

const addToCartForm = document.getElementById('add-to-cart-form');
if (addToCartForm) {
    addToCartForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = addToCartForm.querySelector('[type=submit]');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Adding…';

        const msgEl = document.getElementById('cart-message');

        try {
            const slug = addToCartForm.querySelector('[name=slug]').value;
            const data = await postAjax('/product/' + encodeURIComponent(slug), {
                product_id: addToCartForm.querySelector('[name=product_id]').value,
                variant_id: addToCartForm.querySelector('[name=variant_id]')?.value || '',
                slug:       addToCartForm.querySelector('[name=slug]').value,
                qty:        addToCartForm.querySelector('[name=qty]').value,
            });

            if (data.ok) {
                updateCartBadge(data.cart_count);
                if (msgEl) {
                    msgEl.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                } else {
                    showToast(data.message, 'success');
                }
            } else {
                showToast(data.message || 'Something went wrong.', 'error');
            }
        } catch {
            showToast('Request failed. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
}

// ── Cart form (update + remove) ──────────────────────────────────────────────

const cartForm = document.getElementById('cart-form');
if (cartForm) {
    cartForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitter = e.submitter;
        const isRemove = submitter?.name === 'remove';
        const isUpdate = submitter?.name === 'update';

        if (!isRemove && !isUpdate) return;

        submitter.disabled = true;
        const originalText = submitter.textContent;
        if (isUpdate) submitter.textContent = 'Updating…';

        try {
            const params = {};

            if (isRemove) {
                params.remove = submitter.value;
            }

            if (isUpdate) {
                params.update = '1';
                cartForm.querySelectorAll('input[name^="qty["]').forEach(input => {
                    params[input.name] = input.value;
                });
            }

            const data = await postAjax('/cart', params);

            if (!data.ok) {
                showToast(data.message || 'Something went wrong.', 'error');
                return;
            }

            updateCartBadge(data.cart_count);

            const subtotalEl = document.getElementById('cart-subtotal');
            const totalEl = document.getElementById('cart-total');
            const vatEl = document.getElementById('cart-vat');
            const discountRow = document.getElementById('discount-row');
            const discountEl = document.getElementById('cart-discount');
            const discountLabel = document.getElementById('discount-label');

            if (subtotalEl) subtotalEl.textContent = data.subtotal;
            if (totalEl) totalEl.textContent = data.grand_total;
            if (vatEl) vatEl.textContent = data.total_vat;

            if (discountRow) {
                if (data.has_discount) {
                    discountRow.style.display = 'block';
                    const details = document.getElementById('discount-details');
                    const summary = document.getElementById('discount-summary');
                    if (details) details.style.display = 'none';
                    if (summary) summary.style.display = 'flex';

                    if (discountEl) discountEl.textContent = '-' + data.discount;
                    if (discountLabel) {
                        const names = data.promo_names || [];
                        discountLabel.textContent = 'Discount (' + names.join(', ') + ')';
                    }
                } else {
                    discountRow.style.display = 'none';
                }
            }

            if (isRemove) {
                const row = cartForm.querySelector(`tr[data-item-key="${submitter.value}"]`);
                if (row) {
                    row.style.transition = 'opacity .25s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // If cart is now empty, reload so PHP renders the empty state
                        if (data.cart_count === 0) {
                            window.location.reload();
                        }
                    }, 260);
                }
            }

            if (isUpdate) {
                // Update each row subtotal
                data.items.forEach(item => {
                    const cell = cartForm.querySelector(`.item-subtotal[data-item-key="${item.key}"]`);
                    if (cell) cell.innerHTML = `<strong>${item.subtotal}</strong>`;
                });

                showToast(data.message, 'success');
            }
        } catch {
            showToast('Request failed. Please try again.', 'error');
        } finally {
            submitter.disabled = false;
            submitter.textContent = originalText;
        }
    });
}

// ── Product Details ─────────────────────────────────────────────────────────

const variantSelect = document.getElementById('variant-select');
if (variantSelect) {
    const displayPrice = document.getElementById('display-price');
    const stockStatus = document.getElementById('stock-status');
    const variantIdInput = document.getElementById('selected-variant-id');
    const qtyInput = document.getElementById('qty');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const lowStockThreshold = parseInt(variantSelect.dataset.lowStockThreshold || 0);

    variantSelect.addEventListener('change', function() {
        const option = variantSelect.options[variantSelect.selectedIndex];
        const price = option.dataset.price;
        const stock = parseInt(option.dataset.stock);
        const vid = option.value;

        // Update price
        displayPrice.textContent = formatMoney(price);

        // Update hidden variant ID
        variantIdInput.value = vid;

        // Update stock status
        let badgeHtml = '';
        if (stock > lowStockThreshold) {
            badgeHtml = '<span class="badge badge-success">✓ In Stock</span>';
            addToCartBtn.disabled = false;
        } else if (stock > 0) {
            badgeHtml = `<span class="badge badge-warning">⚠ Only ${stock} left</span>`;
            addToCartBtn.disabled = false;
        } else {
            badgeHtml = '<span class="badge badge-danger">✗ Out of Stock</span>';
            addToCartBtn.disabled = true;
        }
        stockStatus.innerHTML = badgeHtml;

        // Update quantity max
        qtyInput.max = stock;
        if (parseInt(qtyInput.value) > stock) {
            qtyInput.value = stock || 1;
        }
    });
}

// ── Checkout ────────────────────────────────────────────────────────────────

const checkoutForm = document.getElementById('checkout-form');
if (checkoutForm) {
    const baseTotal = parseFloat(checkoutForm.dataset.baseTotal || 0);
    const baseVat = parseFloat(checkoutForm.dataset.baseVat || 0);
    const defaultVatRate = parseFloat(checkoutForm.dataset.vatRate || 0);
    const currencySymbol = checkoutForm.dataset.currencySymbol || '£';

    const deliveryRow = document.getElementById('delivery-row');
    const deliveryCostEl = document.getElementById('delivery-cost');
    const finalTotalEl = document.getElementById('final-total');
    const vatAmountEl = document.getElementById('vat-amount');
    const placeOrderBtn = document.getElementById('place-order-btn');

    const updateTotal = (price) => {
        const deliveryVat = price * (defaultVatRate / (100 + defaultVatRate));
        const totalVat = baseVat + deliveryVat;

        if (deliveryRow) deliveryRow.style.display = 'flex';
        if (deliveryCostEl) deliveryCostEl.textContent = formatMoney(price, currencySymbol);
        if (finalTotalEl) finalTotalEl.textContent = formatMoney(baseTotal + price, currencySymbol);
        if (vatAmountEl) vatAmountEl.textContent = formatMoney(totalVat, currencySymbol);
        if (placeOrderBtn) placeOrderBtn.disabled = false;
    };

    checkoutForm.addEventListener('change', (e) => {
        if (e.target.name === 'delivery_option_id') {
            const label = e.target.closest('label');
            const priceText = label.querySelector('strong').innerText.replace(currencySymbol, '');
            updateTotal(parseFloat(priceText));
        }
    });

    // Handle initial selection if page reloaded
    const checked = checkoutForm.querySelector('input[name="delivery_option_id"]:checked');
    if (checked) {
        const priceText = checked.closest('label').querySelector('strong').innerText.replace(currencySymbol, '');
        updateTotal(parseFloat(priceText));
    }

    // Address selector logic
    const addressSelector = document.getElementById('address-selector');
    if (addressSelector) {
        addressSelector.addEventListener('change', function() {
            const opt = addressSelector.options[addressSelector.selectedIndex];
            if (opt.value) {
                document.getElementById('name').value = opt.dataset.name || '';
                document.getElementById('address').value = opt.dataset.address || '';
                document.getElementById('city').value = opt.dataset.city || '';
                document.getElementById('postcode').value = opt.dataset.postcode || '';
                document.getElementById('country').value = opt.dataset.country || '';
            }
        });
    }
}

// ── Search Autocomplete ──────────────────────────────────────────────────────

const searchInputs = document.querySelectorAll('.search-input-wrapper input[name="q"]');
searchInputs.forEach(input => {
    const wrapper = input.closest('.search-input-wrapper');
    const suggestionsBox = wrapper.querySelector('.search-suggestions');
    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length < 3) {
            suggestionsBox.style.display = 'none';
            suggestionsBox.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`/search/suggestions?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const suggestions = await res.json();

                if (suggestions.length > 0) {
                    renderSuggestions(suggestions, suggestionsBox);
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            } catch (err) {
                console.error('Autocomplete fetch failed', err);
            }
        }, 300);
    });

    input.addEventListener('keydown', (e) => {
        const items = suggestionsBox.querySelectorAll('.suggestion-item');
        let activeIndex = Array.from(items).findIndex(item => item.classList.contains('active'));

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (suggestionsBox.style.display === 'none') return;
            if (activeIndex < items.length - 1) {
                if (activeIndex >= 0) items[activeIndex].classList.remove('active');
                items[activeIndex + 1].classList.add('active');
                items[activeIndex + 1].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (suggestionsBox.style.display === 'none') return;
            if (activeIndex > 0) {
                items[activeIndex].classList.remove('active');
                items[activeIndex - 1].classList.add('active');
                items[activeIndex - 1].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0) {
                e.preventDefault();
                items[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            suggestionsBox.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
});

function renderSuggestions(suggestions, container) {
    container.innerHTML = suggestions.map(s => `
        <a href="${s.url}" class="suggestion-item">
            <img src="${s.image}" alt="" class="suggestion-img">
            <div class="suggestion-info">
                <span class="suggestion-name">${s.name}</span>
                <span class="suggestion-price">${s.price}</span>
            </div>
        </a>
    `).join('');
}

// ── Mobile nav toggle ────────────────────────────────────────────────────────

const navToggle = document.getElementById('nav-toggle');
const siteNav   = document.getElementById('site-nav');
if (navToggle && siteNav) {
    navToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = siteNav.classList.toggle('open');
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => {
        if (siteNav.classList.contains('open') &&
            !siteNav.contains(e.target) && !navToggle.contains(e.target)) {
            siteNav.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && siteNav.classList.contains('open')) {
            siteNav.classList.remove('open');
            navToggle.setAttribute('aria-expanded', 'false');
            navToggle.focus();
        }
    });
}

// ── Cookie Consent Banner ─────────────────────────────────────────────────────

const cookieBanner = document.getElementById('cookie-banner');
if (cookieBanner) {
    function dismissBanner() {
        cookieBanner.style.transition = 'opacity .25s, transform .25s';
        cookieBanner.style.opacity = '0';
        cookieBanner.style.transform = 'translateY(100%)';
        setTimeout(() => cookieBanner.remove(), 260);
    }

    document.getElementById('cookie-accept')?.addEventListener('click', () => {
        const expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = `cookie_consent=1; expires=${expires}; path=/; SameSite=Lax`;
        dismissBanner();
    });

    document.getElementById('cookie-decline')?.addEventListener('click', () => {
        dismissBanner();
    });
}

// ── Faceted Filtering (AJAX) ──────────────────────────────────────────────────

const filtersForm = document.getElementById('filters-form');
const productsList = document.getElementById('products-list');

if (filtersForm && productsList) {
    const updateProducts = async () => {
        productsList.classList.add('loading');
        
        const formData = new FormData(filtersForm);
        const params = new URLSearchParams(formData);
        
        const url = new URL(window.location.href);
        // Clear existing params to rebuild
        const preservedParams = ['q']; // Preserve search query
        const oldQ = url.searchParams.get('q');
        
        url.search = ''; 
        if (oldQ) url.searchParams.set('q', oldQ);

        params.forEach((value, key) => {
            if (key === 'attr[]') {
                url.searchParams.append(key, value);
            } else if (value !== '') {
                url.searchParams.set(key, value);
            }
        });

        const fetchUrl = url.pathname + url.search + (url.search ? '&' : '?') + 'ajax=1';
        
        try {
            const res = await fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            productsList.innerHTML = html;
            window.history.pushState({}, '', url.toString());
        } catch (e) {
            console.error('Filtering failed', e);
            if (typeof showToast === 'function') showToast('Failed to update products.', 'error');
        } finally {
            productsList.classList.remove('loading');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    filtersForm.addEventListener('change', (e) => {
        // Debounce text inputs if needed, but for now just update
        if (e.target.type === 'number') {
             // Optional: add a small delay for typing
        }
        updateProducts();
    });

    filtersForm.addEventListener('submit', (e) => {
        e.preventDefault();
        updateProducts();
    });
    
    productsList.addEventListener('click', (e) => {
        const link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            const fetchUrl = url.pathname + url.search + (url.search ? '&' : '?') + 'ajax=1';
            
            productsList.classList.add('loading');
            fetch(fetchUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                productsList.innerHTML = html;
                window.history.pushState({}, '', url.toString());
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(err => {
                console.error('Pagination failed', err);
                if (typeof showToast === 'function') showToast('Failed to load page.', 'error');
            })
            .finally(() => {
                productsList.classList.remove('loading');
            });
        }
    });

    window.addEventListener('popstate', () => {
        window.location.reload();
    });
}

// ── Filter Show More/Less ──────────────────────────────────────────────────

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-toggle-filters');
    if (!btn) return;

    const group = btn.closest('.filter-group');
    const extra = group.querySelector('.filter-extra');
    if (!extra) return;

    const isExpanded = btn.classList.toggle('active');
    btn.textContent = isExpanded ? 'Show less' : 'Show more';

    if (isExpanded) {
        extra.style.display = 'flex';
        const height = extra.scrollHeight;
        extra.style.maxHeight = '0';
        extra.style.opacity = '0';
        extra.style.transition = 'max-height .3s ease, opacity .3s ease';
        
        // Force reflow
        extra.offsetHeight;
        
        extra.style.maxHeight = height + 'px';
        extra.style.opacity = '1';
    } else {
        extra.style.maxHeight = '0';
        extra.style.opacity = '0';
        
        extra.addEventListener('transitionend', () => {
            if (!btn.classList.contains('active')) {
                extra.style.display = 'none';
            }
        }, { once: true });
    }
});
