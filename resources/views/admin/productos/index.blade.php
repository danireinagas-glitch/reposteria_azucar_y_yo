@extends('adminlte::page')

@section('title', 'Gestión de Productos')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Gestión de Productos</h1>
                <p class="text-muted">Administra los productos que se muestran en el catálogo.</p>
            </div>
            <div class="col-sm-6 text-end d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-toggle="modal" data-target="#createCategoryModal">
                    <i class="fas fa-folder-plus mr-1"></i> Nueva categoría
                </button>
                <a href="{{ route('admin.productos.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Nuevo Producto
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.categorias._create-modal', ['returnTo' => 'productos'])

    @php
        // Obtenemos las categorías únicas presentes en la colección de productos
        $categoriasUnicas = $productos->map(function ($p) {
            return $p->category->nombre ?? 'Sin categoría';
        })->unique()->values();
    @endphp

    <div class="container-fluid">
        <div class="card card-primary card-outline card-outline-tabs">
            {{-- Cabecera con pestañas de categorías --}}
<div class="card-header p-0 pt-2 px-2 border-bottom" style="background-color: #fdf6fa;">
    <ul class="nav nav-tabs border-bottom-0" id="categoryFilterTabs" role="tablist">
        <li class="nav-item mr-1">
            <a class="nav-link active js-category-tab font-weight-bold" href="#" data-category="all" role="tab" aria-selected="true"
               style="background-color: #f8e4f0; color: #6b2d5c; border: 1px solid #ecc6de; border-bottom: none;">
                <i class="fas fa-th-list mr-1"></i> Todos los productos
                <span class="badge badge-primary ml-1">{{ $productos->count() }}</span>
            </a>
        </li>
        @foreach ($categoriasUnicas as $catNombre)
            @php
                $cantidadCat = $productos->filter(function ($p) use ($catNombre) {
                    return ($p->category->nombre ?? 'Sin categoría') === $catNombre;
                })->count();
            @endphp
            <li class="nav-item mr-1">
                <a class="nav-link js-category-tab font-weight-bold" href="#" data-category="{{ $catNombre }}" role="tab" aria-selected="false"
                   style="background-color: #f8e4f0; color: #6b2d5c; border: 1px solid #ecc6de; border-bottom: none;">
                    {{ $catNombre }}
                    <span class="badge badge-light border text-dark ml-1">{{ $cantidadCat }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>

            <div class="card-body">
                {{-- Buscador rápido opcional --}}
                <div class="row mb-3">
                    <div class="col-md-4 ml-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" id="productSearchInput" class="form-control" placeholder="Buscar producto por nombre...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th class="text-center">Catálogo</th>
                                <th class="text-center">Destacado</th>
                                <th class="text-center">Menú digital</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $producto)
                                @php
                                    $categoriaActual = $producto->category->nombre ?? 'Sin categoría';
                                    $nombreProducto = $producto->name ?? $producto->nombre;
                                @endphp
                                <tr class="js-product-row"
                                    data-category="{{ $categoriaActual }}"
                                    data-name="{{ mb_strtolower($nombreProducto) }}">
                                    <td>{{ $producto->id }}</td>
                                    <td>{{ $nombreProducto }}</td>
                                    <td>
                                        <span class="badge badge-secondary">{{ $categoriaActual }}</span>
                                    </td>
                                    <td>Bs. {{ number_format($producto->precio, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $producto->stock > 0 ? 'bg-info' : 'bg-danger' }}">
                                            {{ $producto->stock }} un.
                                        </span>
                                    </td>
                                    @foreach ([
                                        'estado' => ['label' => 'Catálogo', 'value' => $producto->estado],
                                        'destacado' => ['label' => 'Destacados', 'value' => $producto->destacado],
                                        'en_menu_digital' => ['label' => 'Menú digital', 'value' => $producto->en_menu_digital],
                                    ] as $campo => $opcion)
                                        <td class="text-center">
                                            <form action="{{ route('admin.productos.visibility', $producto) }}" method="POST"
                                                class="product-visibility-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="campo" value="{{ $campo }}">
                                                <input type="hidden" name="activo" value="{{ $opcion['value'] ? '0' : '1' }}">
                                                <button type="submit"
                                                    class="product-visibility-switch {{ $opcion['value'] ? 'is-on' : '' }}"
                                                    role="switch" aria-checked="{{ $opcion['value'] ? 'true' : 'false' }}"
                                                    aria-label="{{ $opcion['value'] ? 'Ocultar' : 'Mostrar' }} {{ $producto->nombre }} en {{ $opcion['label'] }}"
                                                    title="{{ $opcion['value'] ? 'Visible' : 'Oculto' }}">
                                                    <span></span>
                                                </button>
                                                <span class="product-visibility-state {{ $opcion['value'] ? 'text-success' : 'text-muted' }}">
                                                    {{ $opcion['value'] ? 'Sí' : 'No' }}
                                                </span>
                                            </form>
                                        </td>
                                    @endforeach
                                    <td>
                                        <a href="{{ route('admin.productos.edit', $producto->id) }}"
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-success js-add-stock"
                                            data-stock-url="{{ route('admin.productos.stock', $producto) }}"
                                            data-stock-name="{{ $producto->nombre }}"
                                            data-stock-current="{{ $producto->stock }}"
                                            aria-label="Añadir stock a {{ $producto->nombre }}"
                                            title="Añadir stock">
                                            <i class="fas fa-plus"></i> Stock
                                        </button>
                                        <form action="{{ route('admin.productos.destroy', $producto->id) }}" method="POST"
                                            style="display:inline-block;"
                                            data-confirm-title="Eliminar producto"
                                            data-confirm-message="¿Estás seguro de que deseas eliminar este producto?"
                                            data-confirm-accept="Eliminar"
                                            data-confirm-destructive="true">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No hay productos registrados todavía.</td>
                                </tr>
                            @endforelse
                            {{-- Fila cuando el filtro o búsqueda no arroja resultados --}}
                            <tr id="noFilterResultsRow" style="display: none;">
                                <td colspan="9" class="text-center text-muted py-4">
                                    No se encontraron productos en esta categoría o con ese criterio de búsqueda.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Añadir Stock --}}
    <div class="modal fade" id="addStockModal" tabindex="-1" role="dialog" aria-labelledby="addStockTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="addStockForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStockTitle">Añadir stock</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2"><strong id="addStockProductName"></strong></p>
                        <p class="text-muted">Stock actual: <span id="addStockCurrent"></span> unidades</p>
                        @if ($errors->has('cantidad'))
                            <div class="alert alert-danger" role="alert">{{ $errors->first('cantidad') }}</div>
                        @endif
                        <label for="addStockQuantity">Unidades que deseas añadir</label>
                        <input type="number" id="addStockQuantity" name="cantidad" min="1" max="2147483647"
                            step="1" class="form-control @error('cantidad') is-invalid @enderror"
                            value="{{ old('cantidad', 1) }}" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus mr-1"></i> Añadir unidades
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Lógica del modal de stock
        document.querySelectorAll('.js-add-stock').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = document.getElementById('addStockForm');
                form.action = button.dataset.stockUrl;
                document.getElementById('addStockProductName').textContent = button.dataset.stockName;
                document.getElementById('addStockCurrent').textContent = button.dataset.stockCurrent;
                $('#addStockModal').modal('show');
            });
        });

        @if (session('stock_product_id'))
            const stockButton = document.querySelector(
                '.js-add-stock[data-stock-url="{{ route('admin.productos.stock', session('stock_product_id')) }}"]'
            );
            if (stockButton) stockButton.click();
        @endif

        // Lógica de filtrado por pestañas de categoría y búsqueda por nombre
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.js-category-tab');
            const rows = document.querySelectorAll('.js-product-row');
            const searchInput = document.getElementById('productSearchInput');
            const noResultsRow = document.getElementById('noFilterResultsRow');

            let selectedCategory = 'all';

            function applyFilters() {
                const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
                let visibleCount = 0;

                rows.forEach(function (row) {
                    const rowCat = row.dataset.category;
                    const rowName = row.dataset.name;

                    const matchesCategory = (selectedCategory === 'all' || rowCat === selectedCategory);
                    const matchesSearch = (searchTerm === '' || rowName.includes(searchTerm));

                    if (matchesCategory && matchesSearch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noResultsRow && rows.length > 0) {
                    noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
                }
            }

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Actualizar estado visual de las pestañas
                    tabs.forEach(t => {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                        const badge = t.querySelector('.badge');
                        if (badge) {
                            badge.classList.remove('badge-primary');
                            badge.classList.add('badge-light', 'border');
                        }
                    });

                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    const activeBadge = this.querySelector('.badge');
                    if (activeBadge) {
                        activeBadge.classList.remove('badge-light', 'border');
                        activeBadge.classList.add('badge-primary');
                    }

                    selectedCategory = this.dataset.category;
                    applyFilters();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
            }
        });
    </script>
@endsection

@push('css')
    <style>
        .product-visibility-form {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-width: 76px;
            margin: 0;
        }

        .product-visibility-switch {
            position: relative;
            width: 38px;
            height: 22px;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: #adb5bd;
            cursor: pointer;
            transition: background-color .18s ease, box-shadow .18s ease;
        }

        .product-visibility-switch.is-on {
            background: #28a745;
        }

        .product-visibility-switch span {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
            transition: transform .18s ease;
        }

        .product-visibility-switch.is-on span {
            transform: translateX(16px);
        }

        .product-visibility-switch:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, .25);
        }

        .product-visibility-state {
            min-width: 1.2rem;
            font-size: .82rem;
            font-weight: 600;
        }

        /* Estilo suave para las pestañas con desplazamiento horizontal si hay muchas categorías */
        #categoryFilterTabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
        }

        #categoryFilterTabs .nav-link {
            white-space: nowrap;
            font-weight: 500;
        }
    </style>
@endpush