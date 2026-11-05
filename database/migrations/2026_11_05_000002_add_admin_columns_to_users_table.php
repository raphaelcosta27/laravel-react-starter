<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes()->index();
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['profile_id']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['deleted_at']);
            $table->dropIndex(['name']);
            $table->dropColumn(['profile_id', 'is_active', 'deleted_at']);
        });
    }
};
