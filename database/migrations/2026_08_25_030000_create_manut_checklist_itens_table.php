<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_checklist_itens')) return;
        Schema::create('manut_checklist_itens', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('checklist');
            $table->integer('ambiente_id')->nullable();
            $table->integer('equipamento_id')->nullable();
            $table->smallInteger('ok')->nullable();
            $table->unique(['checklist', 'ambiente_id', 'equipamento_id'], 'checklist_item_unico');
        });
    }
    public function down(): void { Schema::dropIfExists('manut_checklist_itens'); }
};
