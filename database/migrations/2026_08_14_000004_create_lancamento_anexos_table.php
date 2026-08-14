<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamento_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lancamento_id')->constrained('lancamentos')->cascadeOnDelete();
            $table->string('path');
            $table->string('nome_original')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
        });

        $agora = now();

        DB::table('lancamentos')
            ->whereNotNull('anexo_path')
            ->where('anexo_path', '!=', '')
            ->orderBy('id')
            ->get(['id', 'anexo_path'])
            ->each(function (object $lancamento) use ($agora) {
                DB::table('lancamento_anexos')->insert([
                    'lancamento_id' => $lancamento->id,
                    'path' => $lancamento->anexo_path,
                    'nome_original' => basename($lancamento->anexo_path),
                    'ordem' => 1,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
            });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropColumn('anexo_path');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->string('anexo_path')->nullable();
        });

        $primeiroPorLancamento = DB::table('lancamento_anexos')
            ->orderBy('ordem')
            ->orderBy('id')
            ->get()
            ->unique('lancamento_id');

        foreach ($primeiroPorLancamento as $anexo) {
            DB::table('lancamentos')
                ->where('id', $anexo->lancamento_id)
                ->update(['anexo_path' => $anexo->path]);
        }

        Schema::dropIfExists('lancamento_anexos');
    }
};
