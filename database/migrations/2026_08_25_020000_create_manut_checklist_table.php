<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_checklist')) return;

        Schema::create('manut_checklist', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('ambiente_id')->nullable();
            $table->integer('responsavel')->nullable();
            $table->enum('turno', ['m', 't', 'n'])->nullable();
            $table->dateTime('inicio')->nullable();
            $table->dateTime('fim')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manut_checklist');
    }
};
