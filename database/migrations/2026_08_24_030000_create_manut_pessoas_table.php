<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('manut_pessoas', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('nome', 250);
            $table->string('email', 250)->unique();
            $table->string('perfil', 250)->nullable();
            $table->unsignedBigInteger('perfil_id')->nullable();
            $table->unsignedInteger('locais_id')->nullable()->index();
            $table->smallInteger('ativo')->default(1);
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manut_pessoas');
    }
};
