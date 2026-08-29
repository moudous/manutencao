<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manut_pessoas', function (Blueprint $table): void {
            $table->json('perfis')->nullable()->after('perfil_id');
            $table->timestamp('ultimo_login_em')->nullable()->after('ativo')->index();
        });
    }

    public function down(): void
    {
        Schema::table('manut_pessoas', function (Blueprint $table): void {
            $table->dropIndex(['ultimo_login_em']);
            $table->dropColumn(['perfis', 'ultimo_login_em']);
        });
    }
};
