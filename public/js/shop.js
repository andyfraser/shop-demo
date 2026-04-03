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

            if (isRemove) {
                const row = cartForm.querySelector(`tr[data-item-id="${submitter.value}"]`);
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
                // Update total
                const totalEl = document.getElementById('cart-total');
                if (totalEl) totalEl.textContent = data.total;

                // Update the subtotals sidebar too
                const subtotalEls = document.querySelectorAll('.order-summary strong');
                if (subtotalEls.length >= 1) subtotalEls[0].textContent = data.total;
            }

            if (isUpdate) {
                // Update each row subtotal
                data.items.forEach(item => {
                    const cell = cartForm.querySelector(`.item-subtotal[data-item-id="${item.id}"]`);
                    if (cell) cell.innerHTML = `<strong>${item.subtotal}</strong>`;
                });
                // Update totals
                const totalEl = document.getElementById('cart-total');
                if (totalEl) totalEl.textContent = data.total;

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
