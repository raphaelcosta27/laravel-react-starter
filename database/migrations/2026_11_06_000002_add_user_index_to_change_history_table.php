<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * No MySQL, a chave estrangeira de `user_id` passa a usar o índice composto e
     * recusa a remoção dele enquanto não tiver outro.
     */
    public function down(): void
    {
        Schema::table('change_history', function (Blueprint $table) {
            $table->index('user_id');
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
