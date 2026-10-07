<?php

use Database\Seeders\ExtratoAgo2026Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige a importação anterior: mantém só o período 11/08–31/08
     * e remove o PIX de 03/09 da folha "Últimos Lançamentos".
     */
    public function up(): void
    {
        if (! Schema::hasTable('lancamentos') || ! Schema::hasTable('users')) {
            return;
        }

        if (! class_exists(ExtratoAgo2026Seeder::class)) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => ExtratoAgo2026Seeder::class,
            '--force' => true,
        ]);

        if (class_exists(\App\Support\ClassificacaoContaLancamentos::class)
            && Schema::hasColumn('lancamentos', 'is_historico')) {
            \App\Support\ClassificacaoContaLancamentos::executar();
        }
    }

    public function down(): void
    {
        // Não recria o PIX indevido de 03/09.
    }
};
