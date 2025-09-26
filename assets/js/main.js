function selectTable(element, tableId) {
    const buttons = document.querySelectorAll('.table-card');
    buttons.forEach(btn => btn.classList.remove('active'));
    element.classList.add('active');
    const input = document.getElementById('table_id');
    if (input) {
        input.value = tableId;
    }
}

function updatePaymentDisplay() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const promptpaySection = document.getElementById('promptpay-section');
    if (promptpaySection) {
        promptpaySection.style.display = paymentMethod && paymentMethod.value === 'promptpay' ? 'block' : 'none';
    }
}

function initQuantityControls() {
    document.querySelectorAll('[data-qty-control]').forEach(control => {
        if (control.dataset.bound === '1') {
            return;
        }
        const input = control.querySelector('.qty-input');
        const minus = control.querySelector('.qty-minus');
        const plus = control.querySelector('.qty-plus');
        if (!input || !minus || !plus) {
            return;
        }
        const min = input.hasAttribute('min') ? parseInt(input.getAttribute('min'), 10) || 0 : 0;

        function adjust(delta) {
            const current = parseInt(input.value, 10) || 0;
            let next = current + delta;
            if (next < min) {
                next = min;
            }
            input.value = next;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        minus.addEventListener('click', () => adjust(-1));
        plus.addEventListener('click', () => adjust(1));
        input.addEventListener('blur', () => {
            let value = parseInt(input.value, 10);
            if (Number.isNaN(value) || value < min) {
                input.value = min;
            }
        });
        control.dataset.bound = '1';
    });
}

let floatingAlertTimer;

function showFloatingAlert(message, variant = 'success') {
    let alert = document.querySelector('.floating-alert');
    if (!alert) {
        alert = document.createElement('div');
        alert.className = 'floating-alert';
        document.body.appendChild(alert);
    }
    alert.textContent = message;
    alert.classList.remove('hide');
    if (variant === 'error') {
        alert.style.background = 'rgba(239, 68, 68, 0.95)';
        alert.style.color = '#fee2e2';
        alert.style.boxShadow = '0 20px 40px rgba(239, 68, 68, 0.25)';
    } else {
        alert.style.background = 'rgba(16, 185, 129, 0.95)';
        alert.style.color = '#ecfdf5';
        alert.style.boxShadow = '0 20px 40px rgba(16, 185, 129, 0.25)';
    }
    clearTimeout(floatingAlertTimer);
    floatingAlertTimer = setTimeout(() => {
        alert.classList.add('hide');
    }, 2800);
}

function updateCartCount(count) {
    const headerCount = document.getElementById('cart-count');
    if (headerCount) {
        headerCount.textContent = count;
    }
    const floatingCount = document.getElementById('cart-count-floating');
    if (floatingCount) {
        floatingCount.textContent = count;
        floatingCount.classList.add('pop');
        setTimeout(() => floatingCount.classList.remove('pop'), 420);
    }
}

function animateFlyToCart(card) {
    const img = card ? card.querySelector('img') : null;
    const floating = document.querySelector('.floating-cart-button');
    const header = document.querySelector('.cart-button');
    const cartButton = (() => {
        if (floating) {
            const computed = window.getComputedStyle(floating);
            if (computed.opacity !== '0') {
                return floating;
            }
        }
        return header || floating;
    })();
    if (!img || !cartButton) {
        return;
    }
    const imgRect = img.getBoundingClientRect();
    const cartRect = cartButton.getBoundingClientRect();
    const clone = img.cloneNode(true);
    clone.classList.add('flying-image');
    clone.style.borderRadius = window.getComputedStyle(img).borderRadius;
    clone.style.top = `${imgRect.top}px`;
    clone.style.left = `${imgRect.left}px`;
    clone.style.width = `${imgRect.width}px`;
    clone.style.height = `${imgRect.height}px`;
    document.body.appendChild(clone);
    requestAnimationFrame(() => {
        const translateX = cartRect.left + cartRect.width / 2 - (imgRect.left + imgRect.width / 2);
        const translateY = cartRect.top + cartRect.height / 2 - (imgRect.top + imgRect.height / 2);
        clone.style.transform = `translate(${translateX}px, ${translateY}px) scale(0.25)`;
        clone.style.opacity = '0';
    });
    clone.addEventListener('transitionend', () => clone.remove(), { once: true });
}

function initAddToCartForms() {
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        if (form.dataset.bound === '1') {
            return;
        }
        form.addEventListener('submit', event => {
            event.preventDefault();
            if (form.dataset.sending === '1') {
                return;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            form.dataset.sending = '1';
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            const menuId = form.dataset.menuId;
            const quantityInput = form.querySelector('.qty-input');
            const quantity = parseInt(quantityInput ? quantityInput.value : '1', 10) || 1;
            if (!menuId) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                form.dataset.sending = '0';
                form.submit();
                return;
            }
            const payload = new FormData();
            payload.append('menu_id', menuId);
            payload.append('quantity', quantity);
            fetch('cart_add.php', {
                method: 'POST',
                body: payload,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(resp => {
                    if (!resp.ok) {
                        throw new Error('ไม่สามารถติดต่อเซิร์ฟเวอร์ได้');
                    }
                    return resp.text();
                })
                .then(body => {
                    const trimmed = body.trim();
                    if (!trimmed) {
                        throw new Error('Unexpected empty response');
                    }
                    try {
                        return JSON.parse(trimmed);
                    } catch (error) {
                        throw new Error('Unexpected response: ' + trimmed.slice(0, 80));
                    }
                })
                .then(data => {
                    if (!data || !data.success) {
                        const message = data && data.message ? data.message : 'เพิ่มเมนูไม่สำเร็จ กรุณาลองอีกครั้ง';
                        throw new Error(message);
                    }
                    const countEl = document.getElementById('cart-count');
                    const currentCount = countEl ? parseInt(countEl.textContent || '0', 10) : 0;
                    const newCount = typeof data.count === 'number' ? data.count : currentCount + quantity;
                    updateCartCount(newCount);
                    const menuLabel = form.dataset.menuName || 'เมนู';
                    showFloatingAlert(`เพิ่ม ${menuLabel} ลงตะกร้าเรียบร้อยแล้ว`);
                    animateFlyToCart(form.closest('.menu-card'));
                    if (quantityInput) {
                        const min = quantityInput.hasAttribute('min') ? parseInt(quantityInput.getAttribute('min'), 10) || 1 : 1;
                        quantityInput.value = min;
                    }
                })
                .catch((error) => {
                    showFloatingAlert(error.message || 'เพิ่มเมนูไม่สำเร็จ กรุณาลองอีกครั้ง', 'error');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                    form.dataset.sending = '0';
                });
        });
        form.dataset.bound = '1';
        form.dataset.sending = form.dataset.sending || '0';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    updatePaymentDisplay();
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', updatePaymentDisplay);
    });
    initQuantityControls();
    initAddToCartForms();
    const serverAlert = document.querySelector('.floating-alert[data-auto-hide]');
    if (serverAlert) {
        if (serverAlert.dataset.variant === 'error') {
            serverAlert.style.background = 'rgba(239, 68, 68, 0.95)';
            serverAlert.style.color = '#fee2e2';
            serverAlert.style.boxShadow = '0 20px 40px rgba(239, 68, 68, 0.25)';
        } else {
            serverAlert.style.background = 'rgba(16, 185, 129, 0.95)';
            serverAlert.style.color = '#ecfdf5';
            serverAlert.style.boxShadow = '0 20px 40px rgba(16, 185, 129, 0.25)';
        }
        setTimeout(() => serverAlert.classList.add('hide'), 2800);
    }
    const floatingCart = document.querySelector('.floating-cart-button');
    if (floatingCart) {
        const toggleFloatingCart = () => {
            if (window.scrollY > 140) {
                floatingCart.style.opacity = '1';
                floatingCart.style.pointerEvents = 'auto';
                floatingCart.setAttribute('aria-hidden', 'false');
            } else {
                floatingCart.style.opacity = '0';
                floatingCart.style.pointerEvents = 'none';
                floatingCart.setAttribute('aria-hidden', 'true');
            }
        };
        toggleFloatingCart();
        window.addEventListener('scroll', toggleFloatingCart, { passive: true });
    }
});
