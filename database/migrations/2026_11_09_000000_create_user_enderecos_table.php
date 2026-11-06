<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('postal_code', 20);
            $table->string('street');
            $table->string('number', 20);
            $table->string('complement', 100)->nullable();
            $table->string('reference', 150)->nullable();
            $table->string('neighborhood', 100);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('country', 100);
            $table->boolean('is_main')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_delivery')->default(false);
            $table->string('recipient_name', 150)->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
            $table->unsignedTinyInteger('active_main')
                ->nullable()
                ->storedAs('CASE WHEN deleted_at IS NULL AND is_main = 1 THEN 1 END');

            $table->unique(['user_id', 'active_main']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_enderecos');
    }
};
