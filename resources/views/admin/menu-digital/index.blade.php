@extends('adminlte::page')

@section('title', 'Menú Digital')

@section('content_header')
    <div class="menu-admin-heading">
        <div>
            <span class="menu-eyebrow">EXPERIENCIA EN TIENDA</span>
            <h1>Menú Digital</h1>
            <p>Elige qué productos pueden ver y pedir los clientes desde el QR de su mesa. Llamar al mesero no registra la venta ni descuenta existencias.</p>
        </div>
        <div class="menu-heading-actions">
            <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#menuSelectionModal">
                <i class="fas fa-list-alt mr-1"></i> Añadir menú
            </button>
            @can('admin.mesas.ver')
                <a href="{{ route('admin.mesas.index') }}" class="btn btn-menu-preview">
                    <i class="fas fa-chair mr-1"></i> Mesas y códigos QR
                </a>
            @endcan
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="alert alert-info d-flex align-items-center mb-3" role="status">
            <i class="fas fa-mobile-alt fa-lg mr-3" aria-hidden="true"></i>
            <div>Los clientes escanean el QR de su mesa para elegir productos y llamar al mesero. Esta lista muestra lo que está publicado actualmente.</div>
        </div>

        @include('admin.productos._selection-modal', [
            'modalId' => 'menuSelectionModal',
            'field' => 'en_menu_digital',
            'title' => 'Productos visibles en el Menú Digital',
            'description' => 'Busca productos y marca los que quieres mostrar. Los que ya están visibles aparecen seleccionados.',
            'selectionRoute' => route('admin.menu-digital.selection'),
            'todosProductos' => $todosProductos,
        ])

        @forelse ($productos as $categoryId => $productsInCategory)
            @php($category = $productsInCategory->first()->category)
            <details class="menu-category-block">
                <summary class="menu-category-heading">
                    <div>
                        <span class="menu-eyebrow">CATEGORÍA</span>
                        <h2>{{ $category?->nombre ?? 'Sin categoría' }}</h2>
                    </div>
                    <div class="menu-category-meta">
                        <span class="badge badge-light">{{ $productsInCategory->count() }} productos</span>
                        <i class="fas fa-chevron-down menu-category-chevron" aria-hidden="true"></i>
                    </div>
                </summary>
                <div class="table-responsive">
                    <table class="table menu-product-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productsInCategory as $producto)
                                <tr>
                                    <td>
                                        <div class="menu-product-cell">
                                            <div class="menu-product-thumb">
                                                @if ($producto->imagen)
                                                    <img src="{{ asset($producto->imagen) }}" alt="" loading="lazy">
                                                @else
                                                    <i class="fas fa-cookie-bite"></i>
                                                @endif
                                            </div>
                                            <div class="min-width-0">
                                                <strong class="d-block text-truncate">{{ $producto->nombre }}</strong>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($producto->descripcion ?: 'Producto artesanal', 68) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-weight-bold">Bs. {{ number_format((float) $producto->precio, 2, ',', '.') }}</td>
                                    <td class="text-right text-nowrap">
                                        <form action="{{ route('admin.menu-digital.reorder', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direccion" value="arriba">
                                            <button class="btn btn-sm btn-outline-secondary" title="Mover arriba" aria-label="Mover {{ $producto->nombre }} arriba"><i class="fas fa-arrow-up"></i></button>
                                        </form>
                                        <form action="{{ route('admin.menu-digital.reorder', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direccion" value="abajo">
                                            <button class="btn btn-sm btn-outline-secondary" title="Mover abajo" aria-label="Mover {{ $producto->nombre }} abajo"><i class="fas fa-arrow-down"></i></button>
                                        </form>
                                        @can('admin.productos.ver')
                                            <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-sm btn-outline-primary" title="Editar precio, foto y producto"><i class="fas fa-pen"></i><span class="d-none d-lg-inline ml-1">Editar</span></a>
                                        @endcan
                                        <form action="{{ route('admin.menu-digital.toggle', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm {{ $producto->en_menu_digital ? 'btn-outline-danger' : 'btn-menu-toggle' }}">
                                                <i class="fas {{ $producto->en_menu_digital ? 'fa-eye-slash' : 'fa-eye' }} mr-1"></i>
                                                {{ $producto->en_menu_digital ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div class="menu-empty-state">
                <span><i class="fas fa-cookie-bite"></i></span>
                <h2>Aún no hay productos en el menú</h2>
                <p>Usa “Añadir menú” para seleccionar productos ya registrados en el sistema.</p>
            </div>
        @endforelse
    </div>
@stop

@push('css')
    <style>
        .menu-admin-heading { display:flex; align-items:center; justify-content:space-between; gap:1.25rem; margin-bottom:1.2rem; }
        .menu-admin-heading h1 { margin:0; font-weight:700; color:#302332; }
        .menu-admin-heading p { margin:.35rem 0 0; color:#77717a; }
        .menu-eyebrow { display:block; color:#9b6e91; font-size:.68rem; font-weight:800; letter-spacing:.14em; margin-bottom:.3rem; }
        .menu-heading-actions { display:flex; justify-content:flex-end; gap:.55rem; flex-wrap:wrap; margin-left:auto; }
        .btn-menu-preview,.btn-menu-toggle { background:#713e67; border-color:#713e67; color:#fff; }
        .btn-menu-preview:hover,.btn-menu-toggle:hover { background:#5d3155; border-color:#5d3155; color:#fff; }
        .menu-category-block { overflow:hidden; margin-bottom:1.25rem; background:#fff; border:1px solid #f0edf0; border-radius:14px; box-shadow:0 5px 18px rgba(52,34,50,.04); }
        .menu-category-heading { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.2rem; border-bottom:1px solid #f0edf0; cursor:pointer; list-style:none; }
        .menu-category-heading::-webkit-details-marker { display:none; }
        .menu-category-meta { display:flex; align-items:center; gap:.75rem; }
        .menu-category-chevron { color:#817985; transition:transform .2s ease; }
        .menu-category-block:not([open]) .menu-category-heading { border-bottom:0; }
        .menu-category-block:not([open]) .menu-category-chevron { transform:rotate(-90deg); }
        .menu-category-heading h2 { margin:0; color:#332b35; font-size:1.1rem; font-weight:700; }
        .menu-product-table { margin:0; min-width:850px; }
        .menu-product-table thead th { padding:.8rem 1rem; color:#817985; background:#fcfafc; border-top:0; border-bottom:1px solid #f0edf0; font-size:.74rem; text-transform:uppercase; letter-spacing:.06em; }
        .menu-product-table td { padding:.8rem 1rem; vertical-align:middle; border-color:#f3f0f3; }
        .menu-product-cell { display:flex; align-items:center; gap:.8rem; min-width:260px; }
        .menu-product-thumb { display:grid; place-items:center; flex:0 0 48px; width:48px; height:48px; overflow:hidden; border-radius:12px; color:#8b5c80; background:linear-gradient(135deg,#f8e8ec,#f3e8f6); }
        .menu-product-thumb img { width:100%; height:100%; object-fit:cover; }
        .menu-empty-state { padding:4rem 1rem; text-align:center; background:#fff; border-radius:14px; }
        .menu-empty-state>span { display:grid; place-items:center; width:72px; height:72px; margin:0 auto 1rem; color:#713e67; background:#f1e8f0; border-radius:22px; font-size:1.8rem; }
        .menu-empty-state h2 { color:#332b35; font-size:1.3rem; font-weight:700; } .menu-empty-state p { color:#817985; }
        .min-width-0 { min-width:0; }
        @media(max-width:767.98px) { .menu-admin-heading { align-items:flex-start; flex-direction:column; } .menu-heading-actions { justify-content:flex-start; margin-left:0; } }
    </style>
@endpush
