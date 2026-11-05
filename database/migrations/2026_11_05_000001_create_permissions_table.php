<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('code', 100);
            $table->string('description', 255);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_code', 100)
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN code END')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
