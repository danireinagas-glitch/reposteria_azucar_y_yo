@extends('adminlte::page')

@section('title', 'Pedidos pendientes · Caja')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1"><i class="fas fa-receipt mr-2 text-purple"></i>Pedidos pendientes</h1>
            <p class="text-muted mb-0">Cuentas enviadas por los meseros, listas para confirmar el pago.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('admin.caja.pedidos-mesa') }}" class="btn btn-outline-secondary mr-1">
                <i class="fas fa-sync-alt mr-1"></i> Actualizar
            </a>
            <a href="{{ route('admin.caja.index') }}" class="btn btn-primary">
                <i class="fas fa-cash-register mr-1"></i> Registrar venta
            </a>
        </div>
    </div>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>No se pudo confirmar la cuenta.</strong>
            <ul class="mb-0 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($pedidosMesa as $pedidoMesa)
        <article class="card card-outline card-warning mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h2 class="card-title font-weight-bold">
                        <i class="fas fa-chair mr-1"></i>{{ $pedidoMesa->mesa->nombre }}
                    </h2>
                    <small class="text-muted ml-2">Atendió: {{ $pedidoMesa->mesero?->name ?? 'Mesero no disponible' }}</small>
                </div>
                <span class="badge badge-warning mt-1 mt-md-0">Pendiente de pago</span>
            </div>
            <div class="card-body">
                <div class="row align-items-start">
                    <div class="col-lg-7">
                        <ul class="list-unstyled mb-2">
                            @foreach ($pedidoMesa->items as $item)
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>
                                        <strong>{{ $item->cantidad }} × {{ $item->nombre_producto }}</strong>
                                        @if ($item->observacion)
                                            <small class="d-block text-muted">{{ $item->observacion }}</small>
                                        @endif
                                    </span>
                                    <span class="text-nowrap ml-3">Bs. {{ number_format((float) $item->subtotal, 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if ($pedidoMesa->observaciones)
                            <p class="small text-muted mb-0"><strong>Nota:</strong> {{ $pedidoMesa->observaciones }}</p>
                        @endif
                    </div>
                    <div class="col-lg-5 mt-3 mt-lg-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Total a cobrar</span>
                            <strong class="h4 mb-0 text-nowrap">Bs. {{ number_format((float) $pedidoMesa->total, 2) }}</strong>
                        </div>
                        <form method="POST" action="{{ route('admin.caja.mesas.cobrar', $pedidoMesa) }}"
                            class="mesa-payment-form border rounded bg-light p-3"
                            data-confirm-title="Confirmar pago"
                            data-confirm-message="¿Confirmas el pago de Bs. {{ number_format((float) $pedidoMesa->total, 2) }} de {{ $pedidoMesa->mesa->nombre }}? Se registrará la venta y se descontará el stock."
                            data-confirm-accept="Confirmar pago">
                            @csrf
                            <label for="pago-tipo-{{ $pedidoMesa->id }}">Forma de pago</label>
                            <select id="pago-tipo-{{ $pedidoMesa->id }}" name="pago_tipo"
                                class="form-control mb-2 mesa-payment-type" required>
                                <option value="efectivo">Efectivo</option>
                                <option value="qr">QR</option>
                            </select>
                            <input name="pago_recibido" type="number" step="0.01" min="{{ $pedidoMesa->total }}"
                                class="form-control mb-2 mesa-cash-received" placeholder="Efectivo recibido (Bs.)"
                                aria-label="Efectivo recibido para {{ $pedidoMesa->mesa->nombre }}" required>
                            <button class="btn btn-success btn-block font-weight-bold">
                                <i class="fas fa-check mr-1"></i> Confirmar pago
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3" aria-hidden="true"></i>
                <h2 class="h4">No hay pedidos pendientes</h2>
                <p class="text-muted mb-3">Cuando un mesero envíe una cuenta, aparecerá aquí para confirmar el pago.</p>
                <a href="{{ route('admin.caja.index') }}" class="btn btn-primary">
                    <i class="fas fa-cash-register mr-1"></i> Ir a registrar una venta
                </a>
            </div>
        </div>
    @endforelse
@stop

@push('js')
    <script>
        document.querySelectorAll('.mesa-payment-form').forEach((form) => {
            const type = form.querySelector('.mesa-payment-type');
            const received = form.querySelector('.mesa-cash-received');
            const updatePaymentFields = () => {
                const cash = type.value === 'efectivo';
                received.hidden = !cash;
                received.required = cash;
                if (!cash) received.value = '';
            };
            type.addEventListener('change', updatePaymentFields);
            updatePaymentFields();
        });
    </script>
@endpush
