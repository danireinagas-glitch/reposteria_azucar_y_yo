<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\MesaPedido;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MesaOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mesero;

    private Product $product;

    private Mesa $mesa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        $this->withCookie(config('session.cookie'), str_repeat('a', 40));
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Administradora']);
        $this->admin->assignRole(User::ROL_ADMIN);
        $this->mesero = User::factory()->create(['name' => 'Mesero Uno']);
        $this->mesero->assignRole(Role::findByName('mesero'));

        $this->product = Product::create([
            'nombre' => 'Latte de vainilla',
            'precio' => 10,
            'stock' => 10,
            'estado' => true,
            'en_menu_digital' => true,
        ]);
        $this->mesa = Mesa::create([
            'nombre' => 'Mesa 1',
            'qr_token' => (string) \Illuminate\Support\Str::uuid(),
            'activa' => true,
        ]);
    }

    public function test_table_qr_uses_configured_wifi_url_when_admin_opens_panel_on_localhost(): void
    {
        config(['app.url' => 'http://192.168.137.1:8000']);

        $this->actingAs($this->admin)
            ->get('http://127.0.0.1:8000/admin/mesas')
            ->assertOk()
            ->assertSee('data-qr-url="http://192.168.137.1:8000/menu?mesa='.$this->mesa->qr_token.'"', false)
            ->assertSee('href="http://192.168.137.1:8000/menu?mesa='.$this->mesa->qr_token.'"', false);
    }

    public function test_qr_order_waiter_confirmation_and_cashier_payment_form_one_open_account(): void
    {
        $this->get(route('menu', ['mesa' => $this->mesa->qr_token]))
            ->assertOk()
            ->assertSee('Mesa 1')
            ->assertSee('Llamar mesero')
            ->assertSee('id="cart-items"', false)
            ->assertSee('data-add="'.$this->product->id.'"', false)
            ->assertSee('Latte de vainilla');

        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [[
                'id' => $this->product->id,
                'cantidad' => 2,
                'observacion' => 'Sin azúcar',
            ]],
            'observaciones' => 'Sin azúcar',
        ])->assertCreated()
            ->assertJsonPath('success', true);

        $pedido = MesaPedido::with('items')->sole();
        $this->assertSame(MesaPedido::ESTADO_PENDIENTE_MESERO, $pedido->estado);
        $this->assertSame('20.00', $pedido->total);
        $this->assertSame('Sin azúcar', $pedido->items->sole()->observacion);
        $this->assertSame(10, $this->product->fresh()->stock);

        $this->get(route('menu', ['mesa' => $this->mesa->qr_token]))
            ->assertOk()
            ->assertSee('Actualizar pedido y llamar mesero')
            ->assertSee('Cancelar llamado')
            ->assertSee('"cantidad":2', false)
            ->assertSee('Sin azúcar');

        $this->actingAs($this->mesero)
            ->get(route('admin.mesas.index'))
            ->assertOk()
            ->assertSee('Llama al mesero')
            ->assertSee('¡Llamado al mesero!')
            ->assertSee('mesa-llamada-activa')
            ->assertSee('Mesa 1');

        $this->actingAs($this->mesero)
            ->patch(route('admin.mesas.pedidos.enviar-caja', $pedido), [
                'observaciones' => 'Cliente solicita que se sirva frío',
            ])
            ->assertRedirect();
        $this->assertSame(MesaPedido::ESTADO_EN_CAJA, $pedido->fresh()->estado);

        $this->actingAs($this->mesero)
            ->get(route('admin.caja.index'))
            ->assertForbidden();

        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 1]],
        ])->assertCreated();
        $pedido->refresh();
        $this->assertSame(MesaPedido::ESTADO_PENDIENTE_MESERO, $pedido->estado);
        $this->assertSame('30.00', $pedido->total);
        $this->assertCount(2, $pedido->items);

        $this->actingAs($this->mesero)
            ->patch(route('admin.mesas.pedidos.enviar-caja', $pedido))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('admin.caja.index'))
            ->assertOk()
            ->assertDontSee('Pedidos de mesa pendientes de cobro')
            ->assertDontSee('Confirmar pago');

        $this->actingAs($this->admin)
            ->get(route('admin.caja.pedidos-mesa'))
            ->assertOk()
            ->assertSee('Pedidos pendientes')
            ->assertSee('Mesa 1')
            ->assertSee('30.00')
            ->assertSee('Confirmar pago')
            ->assertSee(route('admin.caja.mesas.cobrar', $pedido), false)
            ->assertSee('app-confirm-backdrop')
            ->assertSee('data-confirm-title="Confirmar pago"', false)
            ->assertSee('data-confirm-message="¿Confirmas el pago de Bs. 30.00 de Mesa 1?', false)
            ->assertDontSee('onclick="return confirm(', false)
            ->assertDontSee('Guardar corrección')
            ->assertDontSee('Cancelar pedido');

        $items = $pedido->items()->orderBy('id')->get();
        $this->actingAs($this->mesero)
            ->patch(route('admin.caja.mesas.update', $pedido), [
                'items' => $items->map(fn ($item) => [
                    'id' => $item->id,
                    'cantidad' => 1,
                ])->all(),
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('admin.caja.mesas.update', $pedido), [
                'items' => [
                    ['id' => $items[0]->id, 'cantidad' => 1, 'observacion' => 'Sin azúcar'],
                    ['id' => $items[1]->id, 'cantidad' => 4],
                ],
            ])
            ->assertRedirect();
        $pedido->refresh();
        $this->assertSame('50.00', $pedido->total);

        $paymentResponse = $this->actingAs($this->admin)
            ->post(route('admin.caja.mesas.cobrar', $pedido), [
                'pago_tipo' => 'qr',
            ]);

        $pedido->refresh();
        $venta = Order::findOrFail($pedido->venta_id);
        $paymentResponse->assertRedirect(route('admin.caja.ficha', $venta));
        $this->actingAs($this->admin)
            ->get(route('admin.caja.ficha', $venta))
            ->assertOk()
            ->assertSee('Mesa 1')
            ->assertSee('QR');
        $this->assertSame(MesaPedido::ESTADO_PAGADO, $pedido->estado);
        $this->assertSame('mesa', $venta->origen);
        $this->assertSame('qr', $venta->pago_tipo);
        $this->assertSame('50.00', $venta->total_final);
        $this->assertSame(5, $this->product->fresh()->stock);
    }

    public function test_unavailable_products_cannot_be_requested_and_cashier_rejects_underpayment(): void
    {
        $this->product->update(['en_menu_digital' => false]);
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.id');
        $this->assertSame(10, $this->product->fresh()->stock);

        $this->product->update(['en_menu_digital' => true]);
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 2]],
        ])->assertCreated();
        $pedido = MesaPedido::sole();

        $this->actingAs($this->mesero)
            ->patch(route('admin.mesas.pedidos.enviar-caja', $pedido))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->from(route('admin.caja.pedidos-mesa'))
            ->post(route('admin.caja.mesas.cobrar', $pedido), [
                'pago_tipo' => 'efectivo',
                'pago_recibido' => '5.00',
            ])
            ->assertRedirect(route('admin.caja.pedidos-mesa'))
            ->assertSessionHasErrors('pago_recibido');

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_tables_can_be_created_and_waiter_permissions_are_limited_to_table_service(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.mesas.store'), ['nombre' => 'Patio 2'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('mesas', ['nombre' => 'Patio 2', 'activa' => true]);
        $this->assertTrue($this->mesero->can('admin.mesas.ver'));
        $this->assertTrue($this->mesero->can('admin.mesas.atender'));
        $this->assertFalse($this->mesero->can('admin.mesas.gestionar'));
        $this->assertFalse($this->mesero->can('admin.caja.ver'));

        $this->actingAs($this->mesero)
            ->post(route('admin.mesas.store'), ['nombre' => 'Mesa no autorizada'])
            ->assertForbidden();
    }

    public function test_cashier_can_cancel_a_sent_table_order_without_creating_a_sale_or_changing_stock(): void
    {
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 2]],
        ])->assertCreated();
        $pedido = MesaPedido::sole();

        $this->actingAs($this->mesero)
            ->patch(route('admin.mesas.pedidos.enviar-caja', $pedido))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->patch(route('admin.caja.mesas.cancelar', $pedido), [
                'motivo' => 'El cliente cambió de idea',
            ])
            ->assertRedirect();

        $this->assertSame(MesaPedido::ESTADO_CANCELADO, $pedido->fresh()->estado);
        $this->assertStringContainsString('El cliente cambió de idea', $pedido->fresh()->observaciones);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $this->product->fresh()->stock);
    }

    public function test_customer_can_cancel_a_waiter_call_before_confirmation(): void
    {
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 2]],
        ])->assertCreated();
        $pedido = MesaPedido::sole();

        $this->postJson(route('menu.mesa.cancelar', $this->mesa->qr_token))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(MesaPedido::ESTADO_CANCELADO, $pedido->fresh()->estado);
        $this->assertStringContainsString('Cancelado por el cliente', $pedido->fresh()->observaciones);
        $this->assertFalse($this->mesa->pedidoAbierto()->exists());
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(10, $this->product->fresh()->stock);

        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 1]],
        ])->assertCreated();
        $this->assertSame(2, MesaPedido::count());
    }

    public function test_customer_can_add_products_to_a_call_before_waiter_confirmation(): void
    {
        $extraProduct = Product::create([
            'nombre' => 'Brownie de nuez',
            'precio' => 8,
            'stock' => 6,
            'estado' => true,
            'en_menu_digital' => true,
        ]);
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 1]],
        ])->assertCreated();

        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [
                ['id' => $this->product->id, 'cantidad' => 2, 'observacion' => 'Sin azúcar'],
                ['id' => $extraProduct->id, 'cantidad' => 1],
            ],
        ])->assertCreated();

        $pedido = MesaPedido::with('items')->sole();
        $this->assertSame(MesaPedido::ESTADO_PENDIENTE_MESERO, $pedido->estado);
        $this->assertSame('28.00', $pedido->total);
        $this->assertCount(2, $pedido->items);
        $this->assertSame(10, $this->product->fresh()->stock);
        $this->assertSame(6, $extraProduct->fresh()->stock);
    }

    public function test_different_customers_create_separate_calls_visible_to_the_waiter(): void
    {
        foreach (['b', 'c', 'd'] as $visitor) {
            $this->withCookie(config('session.cookie'), str_repeat($visitor, 40))
                ->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
                    'items' => [[
                        'id' => $this->product->id,
                        'cantidad' => 1,
                        'observacion' => 'Cliente '.$visitor,
                    ]],
                ])
                ->assertCreated();
        }

        $this->assertSame(3, $this->mesa->pedidos()->count());
        $this->assertSame(3, $this->mesa->pedidoAbierto()
            ->where('estado', MesaPedido::ESTADO_PENDIENTE_MESERO)
            ->count());

        $response = $this->actingAs($this->mesero)->get(route('admin.mesas.index'))
            ->assertOk()
            ->assertSee('Hay 3 pedidos esperando al mesero.')
            ->assertSee('Cliente b')
            ->assertSee('Cliente c')
            ->assertSee('Cliente d');

        $this->assertSame(3, substr_count($response->getContent(), 'Confirmar y enviar a Caja'));
    }

    public function test_customer_cannot_cancel_a_waiter_call_after_it_was_sent_to_cashier(): void
    {
        $this->postJson(route('menu.mesa.solicitar', $this->mesa->qr_token), [
            'items' => [['id' => $this->product->id, 'cantidad' => 1]],
        ])->assertCreated();
        $pedido = MesaPedido::sole();

        $this->actingAs($this->mesero)
            ->patch(route('admin.mesas.pedidos.enviar-caja', $pedido))
            ->assertRedirect();

        $this->postJson(route('menu.mesa.cancelar', $this->mesa->qr_token))
            ->assertStatus(409);

        $this->assertSame(MesaPedido::ESTADO_EN_CAJA, $pedido->fresh()->estado);
        $this->assertSame(10, $this->product->fresh()->stock);
    }
}
