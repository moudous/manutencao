<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_ambientes')) return;
        Schema::create('manut_ambientes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('titulo', 50)->nullable();
            $table->smallInteger('ativo')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('alterado_em')->nullable();
            $table->dateTime('apagado_em')->nullable();
            $table->integer('consultorios')->nullable();
        });
    }

    public function down(): void { Schema::dropIfExists('manut_ambientes'); }
};
