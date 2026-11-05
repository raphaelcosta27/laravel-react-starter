<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->index();
            $table->string('operation', 20);
            $table->string('table_name', 64);
            $table->string('record_id', 64);
            $table->string('field', 64);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('source', 20);
            $table->timestamp('created_at')->index();
            $table->index(['table_name', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_history');
    }
};
