<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_compras')) return;
        Schema::create('manut_compras', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('titulo',100)->nullable();
            $table->string('unidade',3)->nullable();
            $table->float('quantidade')->nullable();
            $table->float('quantidade_unitaria')->nullable();
            $table->float('qtde_utilizada')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('apagado_em')->nullable()->index();
            $table->dateTime('atualizado_em')->nullable();
            $table->string('comprador',50)->nullable();
            $table->double('preco')->nullable();
            $table->date('data_compra')->nullable();
            $table->smallInteger('disponivel')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('manut_compras'); }
};
