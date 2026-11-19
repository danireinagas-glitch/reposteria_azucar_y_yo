<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->uuid('qr_token')->unique();
            $table->boolean('activa')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('mesa_pedidos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->string('estado', 24)->default('pendiente_mesero')->index();
            $table->foreignId('mesero_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cajero_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venta_id')->nullable()->unique()->constrained('ventas')->nullOnDelete();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('enviado_a_caja_en')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();
            $table->index(['mesa_id', 'estado']);
        });

        Schema::create('mesa_pedido_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mesa_pedido_id')->constrained('mesa_pedidos')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('nombre_producto', 255);
            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('cantidad');
            $table->string('observacion', 500)->nullable();
            $table->timestamps();
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_pedido_items');
        Schema::dropIfExists('mesa_pedidos');
        Schema::dropIfExists('mesas');
    }
};
