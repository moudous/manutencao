<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('manut_lancamentos', 'data_inicio')) {
            Schema::table('manut_lancamentos', function (Blueprint $table): void {
                $table->dateTime('data_inicio')->nullable()->after('data_agendamento');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('manut_lancamentos', 'data_inicio')) {
            Schema::table('manut_lancamentos', fn (Blueprint $table) => $table->dropColumn('data_inicio'));
        }
    }
};
