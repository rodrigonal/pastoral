<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamento_segmento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lancamento_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segmento_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lancamento_id', 'segmento_id']);
        });

        // Migrar dados existentes de segmento_id para a pivot
        $lancamentos = DB::table('lancamentos')->whereNotNull('segmento_id')->get();
        foreach ($lancamentos as $l) {
            DB::table('lancamento_segmento')->insert([
                'lancamento_id' => $l->id,
                'segmento_id' => $l->segmento_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropForeign(['segmento_id']);
            $table->dropColumn('segmento_id');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('segmento_id')->nullable()->after('anexo_path')->constrained()->restrictOnDelete();
        });

        $pivot = DB::table('lancamento_segmento')->get();
        foreach ($pivot as $p) {
            DB::table('lancamentos')->where('id', $p->lancamento_id)->update([
                'segmento_id' => $p->segmento_id,
            ]);
        }

        Schema::dropIfExists('lancamento_segmento');
    }
};
