// ==========================================
// CARRITO DE COMPRAS - AZÚCAR Y YO
// ==========================================
const WHATSAPP_PHONE = String(window.AZUCAR_WHATSAPP_PHONE || '').replace(/\D/g, '');
const WHATSAPP_NEGOCIO = String(window.AZUCAR_WHATSAPP_NEGOCIO || 'Azúcar y Yo');
const WHATSAPP_HORARIO_SEMANA = String(window.AZUCAR_WHATSAPP_HORARIO_SEMANA || '');
const WHATSAPP_HORARIO_DOMINGO = String(window.AZUCAR_WHATSAPP_HORARIO_DOMINGO || '');

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCart);
} else {
    initCart();
}

function getCart() {
    try {
        return JSON.parse(localStorage.getItem('azucar_cart')) || [];
    } catch (e) {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem('azucar_cart', JSON.stringify(cart));
    updateCartUI();
}

function initCart() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-add-to-cart');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            
            const product = {
                id: String(btn.dataset.id || Date.now()),
                nombre: btn.dataset.nombre || 'Producto',
                precio: parseFloat(btn.dataset.precio || 0),
                imagen: btn.dataset.imagen || ''
            };
            
            addToCart(product);
        }
    });

    const btnClear = document.getElementById('btn-clear-cart');
    if (btnClear) {
        btnClear.onclick = () => {
            if (confirm('¿Deseas vaciar el carrito?')) {
                saveCart([]);
            }
        };
    }

    const btnWhatsapp = document.getElementById('btn-send-whatsapp');
    if (btnWhatsapp) {
        btnWhatsapp.onclick = sendOrderToWhatsApp;
    }

    updateCartUI();
}

function addToCart(product) {
    let cart = getCart();
    const existingIndex = cart.findIndex(item => String(item.id) === String(product.id));

    if (existingIndex > -1) {
        cart[existingIndex].cantidad += 1;
    } else {
        cart.push({ ...product, cantidad: 1 });
    }

    saveCart(cart);

    const cartOffcanvasEl = document.getElementById('cartOffcanvas');
    if (cartOffcanvasEl && window.bootstrap) {
        const bsOffcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(cartOffcanvasEl);
        bsOffcanvas.show();
    }
}

window.changeQuantity = function(id, delta) {
    let cart = getCart();
    const item = cart.find(i => String(i.id) === String(id));
    if (item) {
        item.cantidad += delta;
        if (item.cantidad <= 0) {
            cart = cart.filter(i => String(i.id) !== String(id));
        }
        saveCart(cart);
    }
};

window.removeFromCart = function(id) {
    let cart = getCart().filter(i => String(i.id) !== String(id));
    saveCart(cart);
};

function updateCartUI() {
    const cart = getCart();
    const badges = document.querySelectorAll('.cart-badge');
    const totalItems = cart.reduce((sum, item) => sum + item.cantidad, 0);
    badges.forEach(b => b.textContent = totalItems);

    const container = document.getElementById('cart-items-container');
    const totalElement = document.getElementById('cart-total-price');
    
    if (!container) return;

    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 my-auto text-muted">
                <i class="bi bi-bag-x fs-1 opacity-50 mb-2 d-block" style="color: #6a397b;"></i>
                <p class="mb-0 fw-medium">Tu carrito está vacío.</p>
                <small>¡Añade tus postres favoritos para comenzar!</small>
            </div>
        `;
        if (totalElement) totalElement.textContent = 'Bs. 0.00';
        return;
    }

    let total = 0;
    let html = '';

    cart.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        html += `
            <div class="cart-item d-flex align-items-center gap-3 p-2 mb-3 bg-white rounded-3 shadow-sm border border-light">
                <img src="${item.imagen}" alt="${item.nombre}" class="rounded-3 object-fit-cover" style="width: 65px; height: 65px;">
                <div class="flex-grow-1 min-w-0">
                    <h6 class="mb-1 text-truncate fw-semibold" style="color: #4a1d47; font-size: 0.95rem;">${item.nombre}</h6>
                    <div class="text-muted small">Bs. ${item.precio.toFixed(2)} c/u</div>
                    <div class="fw-bold mt-1" style="color: #6a397b; font-size: 0.9rem;">Subtotal: Bs. ${subtotal.toFixed(2)}</div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <button type="button" onclick="removeFromCart('${item.id}')" class="btn btn-link text-danger p-0 border-0" title="Eliminar">
                        <i class="bi bi-trash fs-6"></i>
                    </button>
                    <div class="d-flex align-items-center gap-1 bg-light rounded-pill px-2 py-1 border">
                        <button type="button" onclick="changeQuantity('${item.id}', -1)" class="btn btn-sm p-0 border-0 lh-1 text-secondary" style="width:20px;">-</button>
                        <span class="px-1 fw-bold small">${item.cantidad}</span>
                        <button type="button" onclick="changeQuantity('${item.id}', 1)" class="btn btn-sm p-0 border-0 lh-1 text-secondary" style="width:20px;">+</button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    if (totalElement) totalElement.textContent = `Bs. ${total.toFixed(2)}`;
}

function sendOrderToWhatsApp() {
    const cart = getCart();
    if (cart.length === 0) {
        alert('El carrito está vacío.');
        return;
    }

    if (!WHATSAPP_PHONE) {
        alert('No hay un número de WhatsApp configurado.');
        return;
    }

    let message = [
        '━━━━━━━━━━━━━━━━━━━━━━',
        `🍰✨ *${WHATSAPP_NEGOCIO}* ✨🍰`,
        '_Repostería Artesanal_',
        '━━━━━━━━━━━━━━━━━━━━━━',
        '',
        `¡Hola *${WHATSAPP_NEGOCIO}*! 👋🧁`,
        '*Quisiera realizar el siguiente pedido:*',
        '',
        '──────────────────────',
        '🛒 *Detalle del pedido:*',
    ].join('\n');

    let total = 0;

    cart.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        const nombre = String(item.nombre || 'Producto').replace(/[\r\n]+/g, ' ').trim();
        message += `\n• *${item.cantidad}x ${nombre}* — *Bs. ${subtotal.toFixed(2)}*`;
    });

    message += [
        '',
        '',
        `💰 *Total estimado:* *Bs. ${total.toFixed(2)}*`,
        '──────────────────────',
        '',
        '🕒 *Horarios de atención:*',
    ].join('\n');

    if (WHATSAPP_HORARIO_SEMANA) {
        message += `\n• ${WHATSAPP_HORARIO_SEMANA}`;
    }
    if (WHATSAPP_HORARIO_DOMINGO) {
        message += `\n• ${WHATSAPP_HORARIO_DOMINGO}`;
    }

    message += [
        '',
        '',
        '━━━━━━━━━━━━━━━━━━━━━━',
        '> _Quedo a la espera de la confirmación. ¡Muchas gracias!_ 💛🍰',
        '━━━━━━━━━━━━━━━━━━━━━━',
    ].join('\n');

    const url = `https://wa.me/${WHATSAPP_PHONE}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
}