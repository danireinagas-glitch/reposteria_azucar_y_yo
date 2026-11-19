@extends('adminlte::page')

@section('title', 'Mesas y pedidos')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Mesas</h1>
            <p class="text-muted mb-0">Atiende las llamadas del menú digital y envía los pedidos confirmados a Caja.</p>
        </div>
    </div>
@stop

@section('content')
    @push('css')
        <style>
            .mesa-llamada-activa {
                border: 2px solid #28a745 !important;
                box-shadow: 0 0 0 .25rem rgba(40, 167, 69, .16), 0 0 22px rgba(40, 167, 69, .28);
                animation: mesa-llamada-pulso 1.8s ease-in-out infinite;
            }
            @keyframes mesa-llamada-pulso {
                50% { box-shadow: 0 0 0 .4rem rgba(40, 167, 69, .08), 0 0 30px rgba(40, 167, 69, .38); }
            }
            @media (prefers-reduced-motion: reduce) {
                .mesa-llamada-activa { animation: none; }
            }
        </style>
    @endpush

    <ul class="nav nav-tabs mb-3">
        @can('admin.pedidos.ver')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.pedidos.personalizados.index') }}">Encargos personalizados</a>
            </li>
        @endcan
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.mesas.index') }}">Pedidos de mesa</a>
        </li>
    </ul>
    @if ($puedeAtender)
        <div class="alert alert-light border mb-3">
            <i class="fas fa-sync-alt text-success mr-1" aria-hidden="true"></i>
            Las llamadas nuevas se resaltan en verde. Esta lista se actualiza automáticamente cada 15 segundos.
        </div>
    @endif

    @if ($puedeGestionar)
        <div class="card card-outline card-primary mb-3">
            <div class="card-header"><h3 class="card-title">Añadir una mesa</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.mesas.store') }}" class="form-inline">
                    @csrf
                    <label class="sr-only" for="mesa-nombre">Nombre</label>
                    <input id="mesa-nombre" name="nombre" maxlength="80" required
                        class="form-control mr-2 mb-2" placeholder="Ej. Mesa 1">
                    <button class="btn btn-primary mb-2"><i class="fas fa-plus mr-1"></i> Crear mesa y QR</button>
                </form>
            </div>
        </div>
    @endif

    @if ($mesas->isEmpty())
        <div class="alert alert-info">Todavía no hay mesas registradas.</div>
    @else
        <div class="row">
            @foreach ($mesas as $mesa)
                @php($cuentas = $mesa->pedidoAbierto)
                @php($cuenta = $cuentas->first())
                @php($llamadasPendientes = $cuentas->where('estado', \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO)->count())
                @php($menuUrl = rtrim(config('app.url'), '/') . route('menu', ['mesa' => $mesa->qr_token], false))
                <div class="col-xl-6 mb-3">
                    <article class="card h-100 {{ $cuenta?->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO ? 'border-success shadow mesa-llamada-activa' : '' }}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold">{{ $mesa->nombre }}</h3>
                            <span class="badge badge-{{ $mesa->activa ? ($cuenta?->estado_color ?? 'success') : 'secondary' }}">
                                {{ $mesa->activa ? ($cuenta?->estado_label ?? 'Disponible') : 'Deshabilitada' }}
                            </span>
                        </div>
                        <div class="card-body">
                            @if ($mesa->activa)
                                <div class="d-flex flex-wrap align-items-center mb-3" style="gap:1rem">
                                    <div class="mesa-qr border rounded p-2 bg-white"
                                        data-qr-url="{{ $menuUrl }}"
                                        aria-label="Código QR de {{ $mesa->nombre }}"></div>
                                    <div>
                                        <p class="mb-2"><strong>QR para pedir desde {{ $mesa->nombre }}</strong></p>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $menuUrl }}"
                                            target="_blank" rel="noopener">
                                            Probar menú y llamar mesero
                                        </a>
                                        @if ($puedeGestionar)
                                            <form method="POST" action="{{ route('admin.mesas.qr', $mesa) }}" class="mt-2"
                                                onsubmit="return confirm('El QR anterior dejará de funcionar. ¿Continuar?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-secondary">Renovar QR</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($cuentas->isNotEmpty())
                                @if ($llamadasPendientes > 1)
                                    <div class="alert alert-success font-weight-bold">
                                        <i class="fas fa-bell mr-1" aria-hidden="true"></i>
                                        Hay {{ $llamadasPendientes }} pedidos esperando al mesero.
                                    </div>
                                @endif
                                @foreach ($cuentas as $cuenta)
                                    <div class="border rounded p-3 mb-3 {{ $cuenta->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO ? 'bg-light border-success' : '' }}">
                                        <div class="d-flex justify-content-between">
                                            <strong>Pedido #{{ $cuenta->id }}</strong>
                                            <span>Bs. {{ number_format((float) $cuenta->total, 2) }}</span>
                                        </div>
                                        <div class="small text-muted">{{ $cuenta->estado_label }}</div>
                                        @if ($cuenta->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO)
                                            <div class="alert alert-success mt-2 mb-2 font-weight-bold">
                                                <i class="fas fa-bell mr-1" aria-hidden="true"></i> ¡Llamado al mesero! Revisa este pedido.
                                            </div>
                                        @endif
                                        <ul class="mb-2 mt-2 pl-3">
                                            @foreach ($cuenta->items as $item)
                                                <li>
                                                    {{ $item->cantidad }} × {{ $item->nombre_producto }}
                                                    @if ($item->observacion)
                                                        <small class="d-block text-muted">{{ $item->observacion }}</small>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if ($cuenta->observaciones)
                                            <p class="small text-muted mb-2">{{ $cuenta->observaciones }}</p>
                                        @endif

                                        @if ($cuenta->estado === \App\Models\MesaPedido::ESTADO_PENDIENTE_MESERO && $puedeAtender)
                                            <form method="POST" action="{{ route('admin.mesas.pedidos.enviar-caja', $cuenta) }}">
                                                @csrf
                                                @method('PATCH')
                                                <label for="mesero-nota-{{ $cuenta->id }}">Detalle acordado con el cliente (opcional)</label>
                                                <textarea id="mesero-nota-{{ $cuenta->id }}" name="observaciones" rows="2"
                                                    maxlength="1000" class="form-control form-control-sm mb-2"
                                                    placeholder="Aclaraciones para Caja o preparación"></textarea>
                                                <button class="btn btn-success btn-sm">
                                                    <i class="fas fa-paper-plane mr-1"></i> Confirmar y enviar a Caja
                                                </button>
                                            </form>
                                        @elseif ($cuenta->estado === \App\Models\MesaPedido::ESTADO_EN_CAJA)
                                            <p class="mb-0 text-warning font-weight-bold">En Caja; esperando confirmación de pago.</p>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted mb-0">Sin llamadas ni cuentas abiertas.</p>
                            @endif
                        </div>
                        @if ($puedeGestionar)
                            <div class="card-footer text-right">
                                <form method="POST" action="{{ route('admin.mesas.toggle', $mesa) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm {{ $mesa->activa ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        @disabled($cuenta)>
                                        {{ $mesa->activa ? 'Deshabilitar mesa' : 'Habilitar mesa' }}
                                    </button>
                                </form>
                                @if ($cuentas->isNotEmpty())
                                    <small class="text-muted ml-2">Cierra los pedidos abiertos en Caja antes de deshabilitar.</small>
                                @endif
                            </div>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>
    @endif
@stop

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.querySelectorAll('.mesa-qr[data-qr-url]').forEach((element) => {
            if (typeof QRCode === 'undefined') {
                console.error('No se pudo cargar el generador de códigos QR de las mesas.');
                return;
            }
            new QRCode(element, { text: element.dataset.qrUrl, width: 144, height: 144, correctLevel: QRCode.CorrectLevel.H });
        });
        /* Tiempo de actualización automática de 12 segundos */
        @if ($puedeAtender)
            window.setInterval(() => {
                if (document.visibilityState === 'visible') window.location.reload();
            }, 12000);
        @endif
    </script>
@endpush
