<?php

use App\Actions\Admin\ReorganizePermissions;
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Em uma instalação nova a tabela está vazia, e o seeder cria o catálogo.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module', 50)->nullable()->after('description')->index();
        });

        if (Permission::withTrashed()->exists()) {
            app(ReorganizePermissions::class)->handle();
        }
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropColumn('module');
        });
    }
};
