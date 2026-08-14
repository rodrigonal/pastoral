<?php

use App\Support\ClassificacaoContaLancamentos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lancamentos') && Schema::hasColumn('lancamentos', 'is_historico')) {
            ClassificacaoContaLancamentos::executar();
        }
    }

    public function down(): void
    {
        //
    }
};
