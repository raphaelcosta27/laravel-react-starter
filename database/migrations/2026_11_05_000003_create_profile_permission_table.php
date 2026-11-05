<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->restrictOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['profile_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_permission');
    }
};
