<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benfeitores', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->boolean('ativo')->default(true);
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('benfeitor_id')->nullable()->after('user_id')->constrained('benfeitores')->nullOnDelete();
            $table->boolean('classificado')->default(true)->after('benfeitor_id');
            $table->boolean('is_historico')->default(false)->after('classificado');
            $table->string('historico_bancario')->nullable()->after('is_historico');
            $table->string('documento')->nullable()->after('historico_bancario');

            $table->index('classificado');
            $table->index('is_historico');
            $table->index('benfeitor_id');
        });

        DB::table('lancamentos')->update(['is_historico' => true]);
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropForeign(['benfeitor_id']);
            $table->dropColumn([
                'benfeitor_id',
                'classificado',
                'is_historico',
                'historico_bancario',
                'documento',
            ]);
        });

        Schema::dropIfExists('benfeitores');
    }
};
