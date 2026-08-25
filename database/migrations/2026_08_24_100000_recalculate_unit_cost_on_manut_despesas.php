<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('manut_despesas') || ! Schema::hasTable('manut_compras')) return;

        DB::table('manut_compras')->orderBy('id')->chunkById(100, function ($compras): void {
            foreach ($compras as $compra) {
                $totalUnidades = (float) $compra->quantidade * (float) $compra->quantidade_unitaria;
                $precoUnitario = $compra->preco !== null && $totalUnidades > 0 ? (float) $compra->preco / $totalUnidades : null;
                DB::table('manut_despesas')->where('compra_id', $compra->id)->update(['custo' => $precoUnitario]);
            }
        });
    }

    public function down(): void
    {
        // O preço anterior não pode ser reconstruído com segurança.
    }
};
