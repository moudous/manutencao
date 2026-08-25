<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_orcamentos')) {
            return;
        }

        Schema::create('manut_orcamentos', function (Blueprint $table): void {
            $table->increments('id');
            $table->text('descricao')->nullable();
            $table->string('link', 300)->nullable();
            $table->string('centro_custo', 50)->nullable();
            $table->unsignedInteger('lancamentos_id')->nullable()->index();
            $table->integer('situacao_id')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->dateTime('apagado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manut_orcamentos');
    }
};
