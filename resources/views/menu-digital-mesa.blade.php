<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Menú · {{ $mesa->nombre }} · Azúcar y Yo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: dark;
            --plum: #351026;
            --plum-light: #501a3d;
            --gold: #e6bd70;
            --cream: #fff8f1;
            --mint: #57c9a7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding-bottom: max(13rem, 38vh);
            background: var(--plum);
            color: var(--cream);
            font: 16px/1.45 system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        header {
            padding: 1.2rem max(1rem, calc((100% - 1040px) / 2));
            border-bottom: 1px solid #76503d;
            background: linear-gradient(135deg, #45132f, #61244b);
            color: var(--cream);
        }

        .menu-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .5rem;
        }

        .menu-brand-logo {
            display: block;
            width: 76px;
            height: auto;
            flex: 0 0 auto;
        }

        header h1 { margin: .2rem 0; font-size: 1.6rem; }
        header p { margin: 0; color: #ead9df; }
        main { width: min(1040px, 100%); margin: auto; padding: 1rem; }

        .filters {
            display: flex;
            gap: .5rem;
            overflow-x: auto;
            padding: .5rem 0 1rem;
        }

        .filters .btn {
            flex: none;
            --bs-btn-color: var(--gold);
            --bs-btn-border-color: #9a704e;
            --bs-btn-hover-bg: var(--gold);
            --bs-btn-hover-border-color: var(--gold);
            --bs-btn-hover-color: var(--plum);
        }

        .filters .btn.active {
            border-color: var(--gold);
            background: var(--gold);
            color: var(--plum);
        }

        .grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }

        .product.card {
            display: flex;
            min-width: 0;
            flex-direction: row;
            overflow: hidden;
            border: 1px solid #78503f;
            border-radius: 1.25rem;
            background: linear-gradient(115deg, #40132f, #2e0d24);
            box-shadow: 0 .4rem 1.1rem #17071266;
            color: var(--cream);
        }

        .product img {
            flex: 0 0 35%;
            width: 35%;
            max-width: 280px;
            min-height: 180px;
            object-fit: cover;
            background: #28101f;
        }

        .product-body.card-body {
            display: flex;
            min-width: 0;
            flex: 1;
            flex-direction: column;
            padding: 1rem;
        }

        .product h2 {
            margin: 0 0 .35rem;
            color: var(--cream);
            font-family: Georgia, serif;
            font-size: clamp(1.1rem, 3vw, 1.5rem);
        }

        .product p {
            min-height: 0;
            margin: .2rem 0 .8rem;
            color: #d3c3cf;
            font-size: .9rem;
            overflow-wrap: anywhere;
        }

        .price-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            margin-top: auto;
        }

        .price {
            color: var(--gold);
            font-size: 1.05rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .add.btn {
            flex: 0 0 auto;
            border-color: var(--gold);
            background: var(--gold);
            color: var(--plum);
            font-weight: 700;
            white-space: nowrap;
        }

        .add.btn:hover,
        .add.btn:focus-visible {
            border-color: #f3d795;
            background: #f3d795;
            color: var(--plum);
        }

        .empty {
            padding: 3rem 1rem;
            border: 1px solid #78503f;
            border-radius: 1rem;
            background: var(--plum-light);
            color: var(--cream);
            text-align: center;
        }

        .status.alert {
            margin: 1rem 0;
            border-color: #447d68;
            background: #173c32;
            color: #d8fff1;
        }

        .fixed-cart {
            position: fixed;
            z-index: 5;
            right: 0;
            bottom: 0;
            left: 0;
            max-height: 36vh;
            overflow-y: auto;
            border-top: 1px solid #78503f;
            background: #230b1d;
            box-shadow: 0 -.5rem 1.5rem #17071288;
            color: var(--cream);
            padding: .7rem max(1rem, calc((100% - 1040px) / 2));
        }

        .cart-top { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .cart-top > div { min-width: 0; flex: 1; }
        .cart-top small { color: #d3c3cf; }
        .cart-summary { color: var(--gold); font-weight: 750; }
        .call.btn { min-width: 145px; font-weight: 800; white-space: nowrap; }
        .call:disabled { opacity: .55; }

        .cancel-call.btn {
            --bs-btn-color: #ffc2c9;
            --bs-btn-border-color: #a64b59;
            --bs-btn-hover-bg: #a64b59;
        }

        .cart-items {
            display: grid;
            gap: .6rem;
            max-height: 18vh;
            overflow: auto;
            margin-top: .65rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .35rem .75rem;
            padding: .6rem;
            border: 1px solid #654052;
            border-radius: .75rem;
            background: #351026;
        }

        .cart-item-name { font-weight: 700; }
        .cart-item-price { color: #d3c3cf; font-size: .85rem; }
        .cart-item-actions { display: flex; align-items: center; gap: .4rem; }

        .cart-item-actions button {
            min-width: 38px;
            min-height: 38px;
            border: 1px solid #9a704e;
            border-radius: .5rem;
            background: var(--plum-light);
            color: var(--gold);
            font-weight: 800;
        }

        .cart-item-actions .remove { color: #ffc2c9; }

        .cart-item-note,
        .cart-details input.form-control {
            grid-column: 1 / -1;
            width: 100%;
            min-width: 0;
            border-color: #76566a;
            border-radius: .6rem;
            background: #351026;
            color: var(--cream);
        }

        .cart-item-note { padding: .55rem; }
        .cart-details { margin-top: .5rem; }
        .cart-details input.form-control::placeholder,
        .cart-item-note::placeholder { color: #c3adbd; }
        #feedback { margin-top: .5rem; color: var(--mint); font-size: .9rem; }

        @media (max-width: 520px) {
            body { padding-bottom: max(13rem, 38vh); }
            header { padding: 1rem; }
            main { padding: .75rem; }

            .menu-brand-logo { width: 60px; }

            .product img {
                flex-basis: 34%;
                width: 34%;
                min-height: 160px;
            }

            .product-body.card-body { padding: .75rem; }
            .product h2 { font-size: 1.1rem; }
            .product p { margin-bottom: .6rem; font-size: .85rem; }
            .price { font-size: .9rem; }
            .add.btn { padding: .45rem .55rem; font-size: .85rem; }

            .fixed-cart {
                max-height: 34vh;
                max-height: 34dvh;
                padding: .6rem .75rem calc(.6rem + env(safe-area-inset-bottom));
            }

            .cart-top { gap: .5rem; }
            .cart-top small { font-size: .75rem; line-height: 1.25; }
            .call.btn { min-width: 0; padding: .55rem .65rem; font-size: .85rem; }
            .cart-items { max-height: 12vh; }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <div class="menu-brand">
                <img class="menu-brand-logo" src="{{ asset('img/logo_reposteria_transparente.png') }}"
                    alt="Azúcar y Yo">
                <small>AZÚCAR Y YO · MENÚ DIGITAL</small>
            </div>
            <h1>{{ $mesa->nombre }}</h1>
            <p>Explora el menú, elige tus productos y llama al mesero cuando estés listo.</p>
        </div>
    </header>
    <main>
        @if ($pedidoAbierto)
            <div class="status alert">
                <strong>Estado de tu mesa: {{ $pedidoAbierto->estado_label }}</strong>
                @if ($pedidoAbierto->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO)
                    <div>El llamado aún no fue confirmado. Puedes agregar productos, ajustar la selección y volver a llamar al mesero, o cancelar el llamado.</div>
                @else
                    <div>Tu solicitud fue enviada a Caja. Puedes agregar otros productos y llamar nuevamente al mesero.</div>
                @endif
            </div>
        @endif
        <div class="filters" aria-label="Filtrar por categoría">
            <button type="button" class="btn btn-outline-warning rounded-pill active" data-filter="all">Todos</button>
            @foreach ($categorias as $categoria)
                <button type="button" class="btn btn-outline-warning rounded-pill" data-filter="{{ $categoria->id }}">{{ $categoria->nombre }}</button>
            @endforeach
        </div>
        @if ($productos->isNotEmpty())
            <div class="grid" id="products">
                @foreach ($productos as $producto)
                    <article class="product card" data-category="{{ $producto->category_id }}" data-product-card="{{ $producto->id }}">
                        @if ($producto->imagen)
                            <img src="{{ asset($producto->imagen) }}" alt="{{ $producto->nombre }}" loading="lazy">
                        @else
                            <img src="{{ asset(config('images.default_image')) }}" alt="" loading="lazy">
                        @endif
                        <div class="product-body card-body">
                            <h2 class="card-title">{{ $producto->nombre }}</h2>
                            <p class="card-text">{{ $producto->descripcion ?: 'Preparado artesanalmente en Azúcar y Yo.' }}</p>
                            <div class="price-row">
                                <span class="price">Bs. {{ number_format((float) $producto->precio, 2, ',', '.') }}</span>
                                <button type="button" class="add btn btn-warning" data-add="{{ $producto->id }}">Añadir +</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty">Por el momento no hay productos publicados en el menú.</div>
        @endif
    </main>
    <section class="fixed-cart" aria-label="Tu selección">
        <div class="cart-top">
            <div>
                <div class="cart-summary" id="cart-summary">Tu selección está vacía</div>
                <small>El pago se realiza con el mesero o en Caja; este envío no registra una venta.</small>
            </div>
            <button type="button" id="call-waiter" class="call btn btn-success" disabled>Llamar mesero</button>
        </div>
        @if ($pedidoAbierto?->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO)
            <button type="button" id="cancel-call" class="cancel-call btn btn-outline-danger mt-2">Cancelar llamado</button>
        @endif
        <div class="cart-items" id="cart-items" aria-live="polite"></div>
        <div class="cart-details">
            <input id="order-notes" class="form-control" maxlength="1000" value="{{ $pedidoObservaciones }}"
                placeholder="Nota para todo el pedido (opcional)">
        </div>
        <div id="feedback" role="status" aria-live="polite"></div>
    </section>
    <script>
        (() => {
            const products = @json($menuProducts);
            const initialItems = @json($pedidoItems);
            const byId = new Map(products.map(product => [String(product.id), product]));
            const cart = new Map();
            initialItems.forEach(item => cart.set(String(item.id), { ...item }));
            const summary = document.getElementById('cart-summary');
            const cartItems = document.getElementById('cart-items');
            const callButton = document.getElementById('call-waiter');
            const cancelButton = document.getElementById('cancel-call');
            const feedback = document.getElementById('feedback');
            const formatter = new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const render = () => {
                const quantity = [...cart.values()].reduce((sum, item) => sum + item.cantidad, 0);
                const total = [...cart.values()].reduce((sum, item) => sum + byId.get(item.id).price * item.cantidad, 0);
                const hasUnavailableProduct = [...cart.values()].some(item => !byId.get(item.id).available);
                summary.textContent = quantity
                    ? `${quantity} producto(s) · Total referencial Bs. ${formatter.format(total)}`
                    : 'Tu selección está vacía';
                cartItems.replaceChildren();
                cart.forEach(item => {
                    const product = byId.get(item.id);
                    const row = document.createElement('div');
                    row.className = 'cart-item';

                    const details = document.createElement('div');
                    const name = document.createElement('div');
                    name.className = 'cart-item-name';
                    name.textContent = product.name;
                    const price = document.createElement('div');
                    price.className = 'cart-item-price';
                    price.textContent = `Bs. ${formatter.format(product.price)} c/u`;
                    if (!product.available) {
                        price.textContent += ' · Ya no disponible';
                    }
                    details.append(name, price);

                    const actions = document.createElement('div');
                    actions.className = 'cart-item-actions';
                    const decrease = document.createElement('button');
                    if (product.available) {
                        decrease.type = 'button';
                        decrease.textContent = '−';
                        decrease.setAttribute('aria-label', `Quitar una unidad de ${product.name}`);
                        decrease.addEventListener('click', () => changeQuantity(item.id, -1));
                        const amount = document.createElement('span');
                        amount.textContent = String(item.cantidad);
                        const increase = document.createElement('button');
                        increase.type = 'button';
                        increase.textContent = '+';
                        increase.setAttribute('aria-label', `Añadir una unidad de ${product.name}`);
                        increase.addEventListener('click', () => changeQuantity(item.id, 1));
                        actions.append(decrease, amount, increase);
                    } else {
                        const unavailable = document.createElement('small');
                        unavailable.textContent = 'Quita este producto para actualizar el pedido';
                        actions.append(unavailable);
                    }
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'remove';
                    remove.textContent = '×';
                    remove.setAttribute('aria-label', `Quitar ${product.name} de la selección`);
                    remove.addEventListener('click', () => {
                        cart.delete(item.id);
                        render();
                    });
                    actions.append(remove);

                    const note = document.createElement('input');
                    note.type = 'text';
                    note.maxLength = 500;
                    note.className = 'cart-item-note';
                    note.placeholder = `Detalle para ${product.name} (opcional)`;
                    note.setAttribute('aria-label', `Detalle para ${product.name}`);
                    note.value = item.observacion;
                    note.addEventListener('input', () => { item.observacion = note.value; });
                    note.disabled = !product.available;

                    row.append(details, actions, note);
                    cartItems.append(row);
                });
                document.querySelectorAll('[data-add]').forEach(button => {
                    const item = cart.get(String(button.dataset.add));
                    button.textContent = item ? `Añadido (${item.cantidad})` : 'Añadir +';
                });
                callButton.disabled = quantity === 0 || hasUnavailableProduct;
                if (hasUnavailableProduct) {
                    feedback.textContent = 'Quita de la selección los productos que ya no están disponibles para poder actualizar el pedido.';
                } else if (feedback.textContent.startsWith('Quita de la selección')) {
                    feedback.textContent = '';
                }
                callButton.textContent = initialItems.length ? 'Actualizar pedido y llamar mesero' : 'Llamar mesero';
            };

            const changeQuantity = (id, difference) => {
                const item = cart.get(id);
                if (!item) return;
                item.cantidad += difference;
                if (item.cantidad <= 0) {
                    cart.delete(id);
                }
                if (item.cantidad > 50) {
                    item.cantidad = 50;
                }
                render();
            };

            document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
                document.querySelectorAll('[data-filter]').forEach(item => item.classList.toggle('active', item === button));
                document.querySelectorAll('[data-product-card]').forEach(card => {
                    card.hidden = button.dataset.filter !== 'all' && card.dataset.category !== button.dataset.filter;
                });
            }));

            document.querySelectorAll('[data-add]').forEach(button => button.addEventListener('click', () => {
                const id = String(button.dataset.add);
                const current = cart.get(id) || { id, cantidad: 0, observacion: '' };
                current.cantidad = Math.min(50, current.cantidad + 1);
                cart.set(id, current);
                button.textContent = `Añadido (${current.cantidad})`;
                render();
            }));

            callButton.addEventListener('click', async () => {
                if (!cart.size || [...cart.values()].some(item => !byId.get(item.id).available)) return;
                callButton.disabled = true;
                callButton.textContent = 'Enviando...';
                feedback.textContent = '';
                try {
                    const response = await fetch(@json($solicitudUrl), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            items: [...cart.values()],
                            observaciones: document.getElementById('order-notes').value,
                        }),
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'No se pudo enviar la solicitud.');
                    }
                    cart.clear();
                    document.getElementById('order-notes').value = '';
                    feedback.textContent = data.message;
                    window.setTimeout(() => window.location.reload(), 1600);
                } catch (error) {
                    feedback.textContent = error.message || 'No se pudo enviar la solicitud.';
                } finally {
                    render();
                }
            });

            if (cancelButton) {
                cancelButton.addEventListener('click', async () => {
                    if (!window.confirm('¿Cancelar el llamado al mesero? Podrás volver a seleccionar productos después.')) return;
                    cancelButton.disabled = true;
                    cancelButton.textContent = 'Cancelando...';
                    feedback.textContent = '';
                    try {
                        const response = await fetch(@json($cancelacionUrl), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });
                        if (!response.ok) {
                            throw new Error('No se pudo cancelar. Es posible que el mesero ya haya confirmado el llamado.');
                        }
                        const data = await response.json();
                        feedback.textContent = data.message;
                        window.setTimeout(() => window.location.reload(), 900);
                    } catch (error) {
                        feedback.textContent = error.message || 'No se pudo cancelar el llamado.';
                        cancelButton.disabled = false;
                        cancelButton.textContent = 'Cancelar llamado';
                    }
                });
            }

            render();
        })();
    </script>
</body>
</html>
