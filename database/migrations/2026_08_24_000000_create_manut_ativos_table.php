<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manut_ativos', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('codigo', 50)->nullable();
            $table->string('titulo', 250)->nullable();
            $table->text('descricao')->nullable();
            $table->date('data_aquisicao')->nullable();
            $table->integer('locais_id')->nullable();
            $table->smallInteger('ativo')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->dateTime('apagado_em')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manut_ativos');
    }
};
