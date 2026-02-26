<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('tipo', 10);
            $table->string('categoria', 20);
            $table->decimal('valor', 15, 2);
            $table->string('descricao');
            $table->text('observacao')->nullable();
            $table->string('anexo_path')->nullable();
            $table->foreignId('segmento_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index('data');
            $table->index('tipo');
            $table->index('categoria');
            $table->index('segmento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
