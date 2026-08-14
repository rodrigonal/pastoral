<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastorais', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('titulo')->nullable();
            $table->text('agradecimento')->nullable();
            $table->unsignedInteger('frase_id')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->index('data');
        });

        Schema::create('pastoral_imagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pastoral_id')->constrained('pastorais')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastoral_imagens');
        Schema::dropIfExists('pastorais');
    }
};
