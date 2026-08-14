<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('benfeitores', function (Blueprint $table) {
            $table->foreignId('membro_id')->nullable()->after('nome')->constrained('users')->nullOnDelete();
            $table->index('membro_id');
        });
    }

    public function down(): void
    {
        Schema::table('benfeitores', function (Blueprint $table) {
            $table->dropForeign(['membro_id']);
            $table->dropColumn('membro_id');
        });
    }
};
