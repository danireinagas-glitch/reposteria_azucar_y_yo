<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MesaPedido;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MesaPaymentController extends Controller
{
    public function update(Request $request, MesaPedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('mesa_pedido_items', 'id')->where('mesa_pedido_id', $pedido->id),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.observacion' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($pedido, $data): void {
            $cuenta = MesaPedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if ($cuenta->estado !== MesaPedido::ESTADO_EN_CAJA || $cuenta->venta_id) {
                throw ValidationException::withMessages([
                    'pedido' => 'Solo Caja puede corregir pedidos que están pendientes de cobro.',
                ]);
            }

            $items = $cuenta->items()->lockForUpdate()->get()->keyBy('id');
            $submittedIds = collect($data['items'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            $currentIds = $items->keys()->map(fn ($id) => (int) $id)->sort()->values();
            if ($submittedIds->all() !== $currentIds->all()) {
                throw ValidationException::withMessages([
                    'items' => 'Incluye todos los productos del pedido para guardar la corrección.',
                ]);
            }

            $total = 0;
            foreach ($data['items'] as $itemData) {
                $item = $items->get((int) $itemData['id']);
                if (! $item) {
                    throw ValidationException::withMessages([
                        'items' => 'La lista contiene un producto que no pertenece a esta cuenta.',
                    ]);
                }

                $item->update([
                    'cantidad' => (int) $itemData['cantidad'],
                    'observacion' => filled($itemData['observacion'] ?? null)
                        ? trim($itemData['observacion'])
                        : null,
                ]);
                $total += (float) $item->precio * (int) $itemData['cantidad'];
            }

            $cuenta->update(['total' => round($total, 2)]);
        });

        return back()->with('success', 'Se actualizó el pedido de mesa antes del cobro.');
    }

    public function cancel(Request $request, MesaPedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:300'],
        ]);

        DB::transaction(function () use ($request, $pedido, $data): void {
            $cuenta = MesaPedido::query()->lockForUpdate()->findOrFail($pedido->id);
            if ($cuenta->estado !== MesaPedido::ESTADO_EN_CAJA || $cuenta->venta_id) {
                throw ValidationException::withMessages([
                    'pedido' => 'Solo se puede cancelar desde Caja un pedido todavía pendiente de cobro.',
                ]);
            }

            $motivo = filled($data['motivo'] ?? null)
                ? "\n[Cancelado por {$request->user()->name}] ".trim($data['motivo'])
                : "\n[Cancelado por {$request->user()->name}]";
            $cuenta->update([
                'estado' => MesaPedido::ESTADO_CANCELADO,
                'cajero_id' => $request->user()->id,
                'observaciones' => trim(($cuenta->observaciones ?? '').$motivo),
            ]);
        });

        return back()->with('success', 'Pedido cancelado en Caja; no se creó una venta ni se descontó stock.');
    }

    public function pay(Request $request, MesaPedido $pedido): RedirectResponse
    {
        $data = $request->validate([
            'pago_tipo' => ['required', 'in:efectivo,qr'],
            'pago_recibido' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        if ($data['pago_tipo'] === 'efectivo' && ! $request->filled('pago_recibido')) {
            throw ValidationException::withMessages([
                'pago_recibido' => 'Indica cuánto efectivo recibiste.',
            ]);
        }

        $venta = DB::transaction(function () use ($request, $pedido, $data): Order {
            $cajero = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $cuenta = MesaPedido::query()
                ->with(['mesa', 'items'])
                ->lockForUpdate()
                ->findOrFail($pedido->id);

            if ($cuenta->estado !== MesaPedido::ESTADO_EN_CAJA || $cuenta->venta_id) {
                throw ValidationException::withMessages([
                    'pedido' => 'Este pedido ya fue atendido o todavía no ha sido confirmado por el mesero.',
                ]);
            }

            $items = $cuenta->items;
            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'pedido' => 'No se puede cobrar una cuenta sin productos.',
                ]);
            }

            $productIds = $items->pluck('product_id')->filter()->unique()->values();
            $products = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = $products->get($item->product_id);
                if (! $product || ! $product->estado || ! $product->en_menu_digital) {
                    throw ValidationException::withMessages([
                        'pedido' => 'El producto "'.$item->nombre_producto.'" ya no está disponible en Caja.',
                    ]);
                }

                if ((int) $product->stock < $item->cantidad) {
                    throw ValidationException::withMessages([
                        'pedido' => 'Stock insuficiente de "'.$product->nombre.'". Disponible: '.$product->stock.'.',
                    ]);
                }
            }

            $total = round($items->sum(fn ($item) => (float) $item->precio * $item->cantidad), 2);
            $pagoRecibido = $data['pago_tipo'] === 'qr'
                ? $total
                : (float) $data['pago_recibido'];
            if ($pagoRecibido < $total) {
                throw ValidationException::withMessages([
                    'pago_recibido' => 'El efectivo recibido no alcanza el total de la cuenta.',
                ]);
            }

            $venta = Order::create([
                'user_id' => $cajero->id,
                'cliente_nombre' => $cuenta->mesa->nombre,
                'cliente_apellido' => 'Mesa',
                'total' => $total,
                'total_final' => $total,
                'pago_tipo' => $data['pago_tipo'],
                'pago_recibido' => $pagoRecibido,
                'pago_vuelto' => $data['pago_tipo'] === 'efectivo'
                    ? round($pagoRecibido - $total, 2)
                    : 0,
                'estado' => Order::ESTADO_COMPLETADO,
                'origen' => 'mesa',
                'numero_ficha' => Order::siguienteNumeroFicha($cajero->id),
                'notas' => '[Pedido de mesa '.$cuenta->mesa->nombre.'] '.($cuenta->observaciones ?? ''),
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $venta->id,
                    'product_id' => $item->product_id,
                    'nombre_producto' => $item->nombre_producto,
                    'precio' => $item->precio,
                    'cantidad' => $item->cantidad,
                    'observacion' => $item->observacion,
                ]);

                if ($item->product_id) {
                    $products->get($item->product_id)->decrement('stock', $item->cantidad);
                }
            }

            $cuenta->update([
                'estado' => MesaPedido::ESTADO_PAGADO,
                'cajero_id' => $cajero->id,
                'venta_id' => $venta->id,
                'pagado_en' => now(),
                'total' => $total,
            ]);

            return $venta;
        });

        return redirect()->route('admin.caja.ficha', $venta)
            ->with('success', 'Pago confirmado. El pedido de mesa quedó registrado como venta.');
    }
}
