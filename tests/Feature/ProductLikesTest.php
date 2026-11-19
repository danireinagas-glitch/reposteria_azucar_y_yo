<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductLikesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_visitor_can_like_and_unlike_a_product_once(): void
    {
        $product = Product::create([
            'nombre' => 'Torta favorita',
            'precio' => 25,
            'stock' => 5,
            'estado' => true,
            'destacado' => true,
        ]);

        $this->withoutMiddleware(EncryptCookies::class);
        $this->withCredentials();
        $this->withUnencryptedCookie(config('session.cookie'), str_repeat('a', 40))
            ->get('/')
            ->assertOk()
            ->assertSee('aria-pressed="false"', false);

        $this->withUnencryptedCookie(config('session.cookie'), str_repeat('a', 40))
            ->postJson(route('productos.me-gusta', $product), ['liked' => true])
            ->assertOk()
            ->assertJsonPath('liked', true);
        $this->withUnencryptedCookie(config('session.cookie'), str_repeat('a', 40))
            ->postJson(route('productos.me-gusta', $product), ['liked' => true])
            ->assertOk();

        $this->assertDatabaseCount('product_likes', 1);
        $this->withUnencryptedCookie(config('session.cookie'), str_repeat('a', 40))
            ->get(route('catalogo'))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false);

        $this->withUnencryptedCookie(config('session.cookie'), str_repeat('a', 40))
            ->postJson(route('productos.me-gusta', $product), ['liked' => false])
            ->assertOk()
            ->assertJsonPath('liked', false);

        $this->assertDatabaseCount('product_likes', 0);
    }

    public function test_general_report_lists_products_ordered_by_total_likes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROL_ADMIN);

        $mostLiked = Product::create([
            'nombre' => 'Torta más gustada',
            'precio' => 30,
            'stock' => 4,
            'estado' => true,
        ]);
        $lessLiked = Product::create([
            'nombre' => 'Cupcake',
            'precio' => 10,
            'stock' => 8,
            'estado' => true,
        ]);

        foreach (['visitante-a', 'visitante-b', 'visitante-c'] as $visitor) {
            DB::table('product_likes')->insert([
                'product_id' => $mostLiked->id,
                'visitor_hash' => hash('sha256', $visitor),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('product_likes')->insert([
            'product_id' => $lessLiked->id,
            'visitor_hash' => hash('sha256', 'visitante-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reportes.index'))
            ->assertOk()
            ->assertSee('Productos con más me gusta')
            ->assertSee('me gustas')
            ->assertSee('me gusta');

        $this->assertMatchesRegularExpression('/>\s*3\s*me gustas/s', $response->getContent());
        $this->assertMatchesRegularExpression('/>\s*1\s*me gusta/s', $response->getContent());
        $this->assertLessThan(
            strpos($response->getContent(), 'Cupcake'),
            strpos($response->getContent(), 'Torta más gustada')
        );
    }
}
