<?php

use App\Http\Controllers\Admin\EnvironmentController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserAvatarController;
use App\Http\Controllers\Admin\UserBulkActionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserPermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('account.ready')->prefix('admin')->name('admin.')->group(function () {
    Route::get('environment', [EnvironmentController::class, 'index'])
        ->middleware('can:configura_env')
        ->name('environment.index');

    Route::patch('environment', [EnvironmentController::class, 'update'])
        ->middleware('can:configura_env')
        ->name('environment.update');

    Route::get('profiles', [ProfileController::class, 'index'])
        ->middleware('can:visualizar_perfil')
        ->name('profiles.index');

    Route::get('profiles/export', [ExportController::class, 'profiles'])
        ->middleware(['can:visualizar_perfil', 'can:exportar_dados'])
        ->name('profiles.export');

    Route::get('profiles/create', [ProfileController::class, 'create'])
        ->middleware(['can:visualizar_perfil', 'can:cadastrar_perfil'])
        ->name('profiles.create');

    Route::post('profiles', [ProfileController::class, 'store'])
        ->middleware('can:cadastrar_perfil')
        ->name('profiles.store');

    Route::get('profiles/{profile}/edit', [ProfileController::class, 'edit'])
        ->middleware(['can:visualizar_perfil', 'can:editar_perfil'])
        ->name('profiles.edit');

    Route::patch('profiles/{profile}', [ProfileController::class, 'update'])
        ->middleware('can:editar_perfil')
        ->name('profiles.update');

    Route::delete('profiles/{profile}', [ProfileController::class, 'destroy'])
        ->middleware('can:excluir_perfil')
        ->name('profiles.destroy');

    Route::post('profiles/{profile}/restore', [ProfileController::class, 'restore'])
        ->middleware('can:restaurar_perfil')
        ->withTrashed()
        ->name('profiles.restore');

    Route::get('profiles/{profile}/users', [ProfileController::class, 'users'])
        ->middleware('can:visualizar_perfil')
        ->name('profiles.users');

    Route::get('permissions', [PermissionController::class, 'index'])
        ->middleware('can:visualizar_permissao')
        ->name('permissions.index');

    Route::get('permissions/export', [ExportController::class, 'permissions'])
        ->middleware(['can:visualizar_permissao', 'can:exportar_dados'])
        ->name('permissions.export');

    Route::get('permissions/create', [PermissionController::class, 'create'])
        ->middleware(['can:visualizar_permissao', 'can:cadastrar_permissao'])
        ->name('permissions.create');

    Route::post('permissions', [PermissionController::class, 'store'])
        ->middleware('can:cadastrar_permissao')
        ->name('permissions.store');

    Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])
        ->middleware(['can:visualizar_permissao', 'can:editar_permissao'])
        ->name('permissions.edit');

    Route::patch('permissions/{permission}', [PermissionController::class, 'update'])
        ->middleware('can:editar_permissao')
        ->name('permissions.update');

    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])
        ->middleware('can:excluir_permissao')
        ->name('permissions.destroy');

    Route::post('permissions/{permission}/restore', [PermissionController::class, 'restore'])
        ->middleware('can:restaurar_permissao')
        ->withTrashed()
        ->name('permissions.restore');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('can:visualizar_usuario')
        ->name('users.index');

    Route::get('users/export', [ExportController::class, 'users'])
        ->middleware(['can:visualizar_usuario', 'can:exportar_dados'])
        ->name('users.export');

    Route::post('users/bulk', UserBulkActionController::class)
        ->middleware('can:visualizar_usuario')
        ->name('users.bulk');

    Route::get('users/create', [UserController::class, 'create'])
        ->middleware(['can:visualizar_usuario', 'can:cadastrar_usuario'])
        ->name('users.create');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('can:cadastrar_usuario')
        ->name('users.store');

    Route::get('users/{user}/avatar', [UserAvatarController::class, 'show'])
        ->middleware('can:visualizar_usuario')
        ->withTrashed()
        ->name('users.avatar');

    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware(['can:visualizar_usuario', 'can:editar_usuario'])
        ->name('users.edit');

    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('can:editar_usuario')
        ->name('users.update');

    Route::patch('users/{user}/status', [UserController::class, 'status'])
        ->middleware('can:alterar_situacao_usuario')
        ->name('users.status');

    Route::delete('users/{user}', [UserController::class, 'destroy'])
        ->middleware('can:excluir_usuario')
        ->name('users.destroy');

    Route::post('users/{user}/restore', [UserController::class, 'restore'])
        ->middleware('can:restaurar_usuario')
        ->withTrashed()
        ->name('users.restore');

    Route::get('users/{user}/permissions', [UserPermissionController::class, 'edit'])
        ->middleware('can:gerenciar_permissoes_usuario')
        ->name('users.permissions');

    Route::put('users/{user}/permissions', [UserPermissionController::class, 'update'])
        ->middleware('can:gerenciar_permissoes_usuario')
        ->name('users.permissions.update');

    Route::post('users/{user}/password-change', [UserController::class, 'passwordChange'])
        ->middleware(['can:solicitar_troca_senha', 'throttle:admin-emails'])
        ->name('users.password-change');

    Route::post('users/{user}/authentication-email', [UserController::class, 'authenticationEmail'])
        ->middleware(['can:enviar_email_autenticacao', 'throttle:admin-emails'])
        ->name('users.authentication-email');
});
