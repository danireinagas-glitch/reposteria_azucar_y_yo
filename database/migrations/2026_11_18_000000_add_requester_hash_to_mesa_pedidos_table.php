<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesa_pedidos', function (Blueprint $table): void {
            $table->string('requester_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('mesa_pedidos', function (Blueprint $table): void {
            $table->dropIndex(['requester_hash']);
            $table->dropColumn('requester_hash');
        });
    }
};
