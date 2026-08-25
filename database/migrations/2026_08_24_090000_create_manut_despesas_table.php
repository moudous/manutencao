<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_despesas')) return;

        Schema::create('manut_despesas', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('compra_id')->nullable()->index();
            $table->integer('lancamentos_id')->nullable()->index();
            $table->double('quantidade')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->dateTime('apagado_em')->nullable();
            $table->integer('tipos_despesa_id')->nullable();
            $table->double('custo')->nullable();
            $table->string('unidade', 3)->nullable();
        });
    }

    public function down(): void { Schema::dropIfExists('manut_despesas'); }
};
