<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screen_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->string('type', 20);
            $table->string('link', 2048)->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at', 'seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screen_notifications');
    }
};
