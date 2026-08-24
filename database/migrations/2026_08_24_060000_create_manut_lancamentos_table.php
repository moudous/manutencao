<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_lancamentos')) return;
        Schema::create('manut_lancamentos', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('agenda_id')->nullable()->index();
            $table->integer('ativos_id')->nullable();
            $table->string('solicitante',50)->nullable();
            $table->dateTime('data_lancamento')->nullable();
            $table->string('observacao',512)->nullable();
            $table->dateTime('data_orcamento')->nullable();
            $table->integer('tecnicos_id')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('apagado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->integer('situacao_id')->nullable();
            $table->smallInteger('ativo')->nullable();
            $table->integer('locais_id')->nullable();
            $table->integer('tipos_id')->nullable();
            $table->string('problema',250)->nullable();
            $table->integer('etapa')->nullable();
            $table->dateTime('data_arquivamento')->nullable();
            $table->dateTime('data_agendamento')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('manut_lancamentos'); }
};
