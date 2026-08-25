<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('checklist_problemas')) return;
        Schema::create('checklist_problemas', function (Blueprint $table): void {
            $table->integer('checklistitemid')->primary();
            $table->integer('ambiente_id')->nullable();
            $table->string('problema', 50)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('checklist_problemas'); }
};
