<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileContactController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('account.ready')->name('dashboard');

Route::middleware('account.ready')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/avatar', [ProfileAvatarController::class, 'show'])->name('profile.avatar.show');
    Route::post('/profile/avatar', [ProfileAvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileAvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    Route::post('/profile/contacts', [ProfileContactController::class, 'store'])->name('profile.contacts.store');
    Route::patch('/profile/contacts/{contact}', [ProfileContactController::class, 'update'])->whereNumber('contact')->name('profile.contacts.update');
    Route::delete('/profile/contacts/{contact}', [ProfileContactController::class, 'destroy'])->whereNumber('contact')->name('profile.contacts.destroy');
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
