<?php

use Database\Seeders\ExtratoAgoSet2026Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lancamentos') || ! Schema::hasTable('users')) {
            return;
        }

        if (! class_exists(ExtratoAgoSet2026Seeder::class)) {
            return;
        }

        Artisan::call('db:seed', [
            '--class' => ExtratoAgoSet2026Seeder::class,
            '--force' => true,
        ]);

        if (class_exists(\App\Support\ClassificacaoContaLancamentos::class)
            && Schema::hasColumn('lancamentos', 'is_historico')) {
            \App\Support\ClassificacaoContaLancamentos::executar();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('lancamentos')) {
            return;
        }

        $csv = database_path('seeders/data/extrato-2026-08-17-a-2026-09-03.csv');
        if (! is_file($csv) || ! class_exists(\App\Services\ExtratoBancarioParser::class)) {
            return;
        }

        $parser = app(\App\Services\ExtratoBancarioParser::class);

        foreach ($parser->parse($csv) as $linha) {
            \App\Models\Lancamento::query()
                ->whereDate('data', $linha['data'])
                ->where('documento', $linha['documento'])
                ->where('valor', $linha['valor'])
                ->where('historico_bancario', $linha['historico'])
                ->delete();
        }
    }
};
