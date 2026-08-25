<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('manut_situacao_lancamento') || DB::table('manut_situacao_lancamento')->exists()) return;
        $agora = now();
        DB::table('manut_situacao_lancamento')->insert([
            ['titulo'=>'Concluída', 'ativo'=>1, 'criado_em'=>$agora, 'atualizado_em'=>$agora],
            ['titulo'=>'Concluída com pendências', 'ativo'=>1, 'criado_em'=>$agora, 'atualizado_em'=>$agora],
            ['titulo'=>'Não concluída', 'ativo'=>1, 'criado_em'=>$agora, 'atualizado_em'=>$agora],
        ]);
    }

    public function down(): void
    {
        DB::table('manut_situacao_lancamento')->whereIn('titulo', ['Concluída', 'Concluída com pendências', 'Não concluída'])->delete();
    }
};
