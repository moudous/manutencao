<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('manut_agenda_preventiva') || ! Schema::hasTable('manut_lancamentos')) return;

        DB::table('manut_agenda_preventiva')
            ->whereNull('apagado_em')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('manut_lancamentos')
                    ->whereColumn('manut_lancamentos.agenda_id', 'manut_agenda_preventiva.id')
                    ->whereNull('manut_lancamentos.data_arquivamento')
                    ->whereNull('manut_lancamentos.apagado_em');
            })
            ->orderBy('id')
            ->chunkById(100, function ($agendas): void {
                foreach ($agendas as $agenda) {
                    $agora = now();
                    $proximaAgenda = $agora->copy()->addDays(max(0, (int) $agenda->periodicidade));
                    $proximoOrcamento = $proximaAgenda->copy()->subDays(max(0, (int) $agenda->orcamento));
                    DB::table('manut_lancamentos')->insert([
                        'agenda_id'=>$agenda->id, 'ativos_id'=>$agenda->ativos_id, 'locais_id'=>$agenda->locais_id, 'solicitante'=>'Sistema',
                        'data_lancamento'=>$agora, 'data_orcamento'=>$proximoOrcamento, 'data_agendamento'=>$proximaAgenda,
                        'data_inicio'=>null, 'etapa'=>1, 'ativo'=>1, 'criado_em'=>$agora, 'atualizado_em'=>$agora,
                    ]);
                    DB::table('manut_agenda_preventiva')->where('id', $agenda->id)->update([
                        'proxima_agenda'=>$proximaAgenda, 'proximo_orcamento'=>$proximoOrcamento, 'atualizado_em'=>$agora,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Os lançamentos passam a fazer parte do histórico operacional e não são removidos no rollback.
    }
};
