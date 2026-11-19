<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Mesa;
use App\Models\MesaPedido;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuPublicController extends Controller
{
    /** Menú digital público: el que ve el cliente desde el celular. */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer', 'exists:categories,id'],
            'mesa' => ['nullable', 'uuid', 'exists:mesas,qr_token'],
        ]);
        $mesa = isset($filters['mesa'])
            ? Mesa::query()->where('qr_token', $filters['mesa'])->where('activa', true)->firstOrFail()
            : null;
        $productos = Product::with('category')
            ->enMenuDigital()
            ->orderBy('category_id')
            ->orderByRaw('orden_destacado IS NULL, orden_destacado')
            ->orderBy('nombre');

        if (! $mesa) {
            return response()
                ->view('menu-sin-mesa')
                ->header('Cache-Control', 'no-store, private');
        }

        $productos->buscar($filters['q'] ?? null)
            ->when(isset($filters['categoria']), fn ($q) => $q->where('category_id', $filters['categoria']));
        $productos = $productos->get();
        $categorias = Category::query()
            ->whereHas('products', fn ($query) => $query->enMenuDigital())
            ->orderBy('nombre')
            ->get();
        $requesterHash = hash('sha256', $request->session()->getId());
        $pedidoAbierto = $mesa->pedidoAbierto()
            ->where('requester_hash', $requesterHash)
            ->with('items')
            ->latest('id')
            ->first();
        $menuProducts = $productos->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->nombre,
            'price' => (float) $product->precio,
            'available' => true,
        ])->keyBy(fn (array $product) => (string) $product['id']);
        $pedidoItems = $pedidoAbierto?->estado === MesaPedido::ESTADO_PENDIENTE_MESERO
            ? $pedidoAbierto->items->map(fn ($item) => [
                'id' => (string) $item->product_id,
                'name' => $item->nombre_producto,
                'price' => (float) $item->precio,
                'cantidad' => $item->cantidad,
                'observacion' => $item->observacion ?? '',
                'available' => $menuProducts->has((string) $item->product_id),
            ])->values()
            : collect();
        $unavailablePedidoProducts = $pedidoItems
            ->where('available', false)
            ->keyBy('id')
            ->map(fn (array $item) => [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'available' => false,
            ]);
        $menuProducts = $menuProducts->union($unavailablePedidoProducts);

        return view('menu-digital-mesa', [
            'productos' => $productos,
            'menuProducts' => $menuProducts->values(),
            'pedidoItems' => $pedidoItems,
            'pedidoObservaciones' => $pedidoAbierto?->observaciones ?? '',
            'categorias' => $categorias,
            'mesa' => $mesa,
            'pedidoAbierto' => $pedidoAbierto,
            'solicitudUrl' => route('menu.mesa.solicitar', $mesa->qr_token),
            'cancelacionUrl' => route('menu.mesa.cancelar', $mesa->qr_token),
        ]);
    }

    public function cancelarSolicitud(Mesa $mesa)
    {
        abort_unless($mesa->activa, 404);

        DB::transaction(function () use ($mesa): void {
            $mesa = Mesa::query()->lockForUpdate()->findOrFail($mesa->id);
            abort_unless($mesa->activa, 404);

            $pedido = $mesa->pedidos()
                ->where('estado', MesaPedido::ESTADO_PENDIENTE_MESERO)
                ->where('requester_hash', hash('sha256', request()->session()->getId()))
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $pedido) {
                abort(409, 'El llamado ya fue atendido y no se puede cancelar desde el menú.');
            }

            $pedido->update([
                'estado' => MesaPedido::ESTADO_CANCELADO,
                'observaciones' => trim(($pedido->observaciones ?? '')."\n[Cancelado por el cliente antes de la confirmación del mesero]"),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Se canceló el llamado al mesero.',
        ]);
    }

    public function solicitarPorMesa(Request $request, Mesa $mesa)
    {
        abort_unless($mesa->activa, 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.observacion' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $requesterHash = hash('sha256', $request->session()->getId());
        $pedido = DB::transaction(function () use ($mesa, $data, $requesterHash): MesaPedido {
            $mesa = Mesa::query()->lockForUpdate()->findOrFail($mesa->id);
            abort_unless($mesa->activa, 404);

            $pedido = MesaPedido::query()
                ->where('mesa_id', $mesa->id)
                ->where('requester_hash', $requesterHash)
                ->whereIn('estado', [MesaPedido::ESTADO_PENDIENTE_MESERO, MesaPedido::ESTADO_EN_CAJA])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $esNuevoEnvio = $pedido?->estado === MesaPedido::ESTADO_EN_CAJA;
            if (! $pedido) {
                $pedido = MesaPedido::create([
                    'mesa_id' => $mesa->id,
                    'requester_hash' => $requesterHash,
                    'estado' => MesaPedido::ESTADO_PENDIENTE_MESERO,
                ]);
            } elseif (! $esNuevoEnvio) {
                $pedido->items()->delete();
            }

            $ids = collect($data['items'])->pluck('id')->map(fn ($id) => (int) $id)->all();
            $products = Product::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            $total = $esNuevoEnvio ? (float) $pedido->total : 0;
            foreach ($data['items'] as $index => $item) {
                $product = $products->get((int) $item['id']);
                if (! $product || ! $product->estado || ! $product->en_menu_digital) {
                    throw ValidationException::withMessages([
                        "items.$index.id" => 'Uno de los productos ya no está disponible en el menú.',
                    ]);
                }

                $quantity = (int) $item['cantidad'];
                $price = round((float) $product->precio, 2);
                $total += $price * $quantity;
                $pedido->items()->create([
                    'product_id' => $product->id,
                    'nombre_producto' => $product->nombre,
                    'precio' => $price,
                    'cantidad' => $quantity,
                    'observacion' => filled($item['observacion'] ?? null) ? trim($item['observacion']) : null,
                ]);
            }

            if ($total > 9_999_999.99) {
                throw ValidationException::withMessages([
                    'items' => 'El monto de la cuenta supera el límite permitido.',
                ]);
            }

            $observaciones = filled($data['observaciones'] ?? null) ? trim($data['observaciones']) : null;
            $pedido->update([
                'estado' => MesaPedido::ESTADO_PENDIENTE_MESERO,
                'total' => $total,
                'observaciones' => $esNuevoEnvio && $observaciones
                    ? trim(($pedido->observaciones ?? '')."\n".$observaciones)
                    : $observaciones,
                'mesero_id' => null,
                'enviado_a_caja_en' => null,
            ]);

            return $pedido->fresh(['mesa', 'items']);
        });

        return response()->json([
            'success' => true,
            'message' => 'El mesero recibió tu solicitud.',
            'estado' => $pedido->estado_label,
        ], 201);
    }

}
