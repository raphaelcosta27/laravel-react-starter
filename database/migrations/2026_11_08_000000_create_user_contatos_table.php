<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type', 10);
            $table->string('value');
            $table->boolean('is_whatsapp')->default(false);
            $table->boolean('is_emergency')->default(false);
            $table->string('relationship', 50)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->string('active_value')
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL THEN value END');

            $table->unique(['user_id', 'active_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_contatos');
    }
};
