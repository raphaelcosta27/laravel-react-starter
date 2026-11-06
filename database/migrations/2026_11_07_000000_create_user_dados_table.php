<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('full_name');
            $table->string('preferred_name', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 50)->nullable();
            $table->char('cpf', 11)->nullable();
            $table->string('avatar_path')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->string('active_cpf', 11)
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN cpf END')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dados');
    }
};
