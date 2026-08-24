<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('manut_agenda_preventiva')) {
            Schema::table('manut_agenda_preventiva', function (Blueprint $table): void {
                if (! Schema::hasColumn('manut_agenda_preventiva', 'proxima_agenda')) {
                    $table->dateTime('proxima_agenda')->nullable()->after('ultima_agenda');
                }
                if (! Schema::hasColumn('manut_agenda_preventiva', 'proximo_orcamento')) {
                    $table->dateTime('proximo_orcamento')->nullable()->after('orcamento');
                }
            });

            return;
        }

        Schema::create('manut_agenda_preventiva', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('obs', 250)->nullable();
            $table->dateTime('ultima_agenda')->nullable();
            $table->dateTime('proxima_agenda')->nullable();
            $table->dateTime('proximo_orcamento')->nullable();
            $table->integer('ativos_id')->nullable()->index();
            $table->integer('periodicidade')->nullable();
            $table->integer('orcamento')->nullable();
            $table->integer('criado_por')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->dateTime('apagado_em')->nullable()->index();
            $table->smallInteger('ativo')->nullable();
            $table->integer('locais_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('manut_agenda_preventiva')) {
            Schema::table('manut_agenda_preventiva', function (Blueprint $table): void {
                $columns = collect(['proxima_agenda', 'proximo_orcamento'])
                    ->filter(fn (string $column): bool => Schema::hasColumn('manut_agenda_preventiva', $column))
                    ->all();
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
