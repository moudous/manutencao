<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('manut_agenda_preventiva')
            ->whereNull('proxima_agenda')
            ->whereNotNull('ultima_agenda')
            ->whereNotNull('periodicidade')
            ->update(['proxima_agenda' => DB::raw('DATE_ADD(ultima_agenda, INTERVAL periodicidade DAY)')]);

        DB::table('manut_agenda_preventiva')
            ->whereNull('proximo_orcamento')
            ->whereNotNull('proxima_agenda')
            ->whereNotNull('orcamento')
            ->update(['proximo_orcamento' => DB::raw('DATE_SUB(proxima_agenda, INTERVAL orcamento DAY)')]);
    }

    public function down(): void
    {
        // Datas podem ter sido ajustadas manualmente após a migração.
    }
};
