<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\MesaPedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MesaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.mesas.ver');
    }

    public function index(): View
    {
        $mesas = Mesa::query()->with([
            'pedidoAbierto.items',
            'pedidoAbierto.mesero',
        ])->orderBy('nombre')->get();

        $mesas->each(function (Mesa $mesa): void {
            $mesa->setRelation(
                'pedidoAbierto',
                $mesa->pedidoAbierto
                    ->sortBy(fn (MesaPedido $pedido): array => [
                        $pedido->estado === MesaPedido::ESTADO_PENDIENTE_MESERO ? 0 : 1,
                        $pedido->id,
                    ])
                    ->values()
            );
        });

        return view('admin.mesas.index', [
            'mesas' => $mesas,
            'puedeGestionar' => auth()->user()->can('admin.mesas.gestionar'),
            'puedeAtender' => auth()->user()->can('admin.mesas.atender'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('admin.mesas.gestionar'), 403);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'unique:mesas,nombre'],
        ]);

        Mesa::create([
            'nombre' => trim($data['nombre']),
            'qr_token' => (string) Str::uuid(),
            'activa' => true,
        ]);

        return back()->with('success', 'Mesa creada. Su código QR ya está disponible para imprimir.');
    }

    public function toggle(Mesa $mesa): RedirectResponse
    {
        abort_unless(request()->user()->can('admin.mesas.gestionar'), 403);

        if ($mesa->activa && $mesa->pedidoAbierto()->exists()) {
            throw ValidationException::withMessages([
                'mesa' => 'No se puede desactivar una mesa con una solicitud abierta.',
            ]);
        }

        $mesa->update(['activa' => ! $mesa->activa]);

        return back()->with('success', $mesa->activa ? 'Mesa habilitada.' : 'Mesa deshabilitada.');
    }

    public function renovarQr(Mesa $mesa): RedirectResponse
    {
        abort_unless(request()->user()->can('admin.mesas.gestionar'), 403);
        $mesa->update(['qr_token' => (string) Str::uuid()]);

        return back()->with('success', 'Se generó un QR nuevo para '.$mesa->nombre.'. El código anterior dejó de funcionar.');
    }

    public function enviarACaja(Request $request, MesaPedido $pedido): RedirectResponse
    {
        abort_unless($request->user()->can('admin.mesas.atender'), 403);
        $data = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $pedido, $data): void {
            $locked = MesaPedido::query()->lockForUpdate()->findOrFail($pedido->id);
            abort_unless($locked->estado === MesaPedido::ESTADO_PENDIENTE_MESERO, 409);

            $observacionMesero = filled($data['observaciones'] ?? null)
                ? trim($data['observaciones'])
                : null;
            $locked->update([
                'estado' => MesaPedido::ESTADO_EN_CAJA,
                'mesero_id' => $request->user()->id,
                'enviado_a_caja_en' => now(),
                'observaciones' => $observacionMesero
                    ? trim(($locked->observaciones ?? '')."\n[Mesero] ".$observacionMesero)
                    : $locked->observaciones,
            ]);
        });

        return back()->with('success', 'Pedido confirmado y enviado a Caja. El mesero ya no puede editarlo.');
    }
}
