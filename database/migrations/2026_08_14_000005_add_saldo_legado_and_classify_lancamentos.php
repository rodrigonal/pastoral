<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controle_saldos', function (Blueprint $table) {
            $table->decimal('saldo_legado', 15, 2)->default(0)->after('saldo_em_maos');
        });

        if (class_exists(\App\Support\ClassificacaoContaLancamentos::class)
            && Schema::hasTable('lancamentos')
            && Schema::hasColumn('lancamentos', 'is_historico')) {
            \App\Support\ClassificacaoContaLancamentos::executar();
        }
    }

    public function down(): void
    {
        Schema::table('controle_saldos', function (Blueprint $table) {
            $table->dropColumn('saldo_legado');
        });
    }
};
