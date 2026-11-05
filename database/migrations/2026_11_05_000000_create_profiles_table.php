<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->string('system_key', 50)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_name', 100)
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN name END')
                ->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
