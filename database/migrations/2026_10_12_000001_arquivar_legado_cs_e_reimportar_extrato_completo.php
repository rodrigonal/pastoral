<?php

use App\Support\ResetExtratoContaAtual;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lancamentos') || ! Schema::hasTable('users')) {
            return;
        }

        ResetExtratoContaAtual::executar();
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_legado_cs');
    }
};
