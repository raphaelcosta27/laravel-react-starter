<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PostalCodeController;
use App\Http\Controllers\ProfileAddressController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScreenNotificationController;
use App\Http\Middleware\EnsureNotificationsEnabled;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/dashboard', DashboardController::class)->middleware('account.ready')->name('dashboard');

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
    Route::post('/profile/addresses', [ProfileAddressController::class, 'store'])->name('profile.addresses.store');
    Route::patch('/profile/addresses/{address}', [ProfileAddressController::class, 'update'])->whereNumber('address')->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [ProfileAddressController::class, 'destroy'])->whereNumber('address')->name('profile.addresses.destroy');
    Route::get('/postal-code/{postalCode}', PostalCodeController::class)->where('postalCode', '[0-9]{8}')->middleware('throttle:30,1')->name('postal-code.show');
});

Route::middleware(['account.ready', EnsureNotificationsEnabled::class])->prefix('notifications')->group(function () {
    Route::get('/', [ScreenNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/unread', [ScreenNotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/seen', [ScreenNotificationController::class, 'seen'])->name('notifications.seen');
    Route::delete('/{notification}', [ScreenNotificationController::class, 'destroy'])->whereNumber('notification')->name('notifications.destroy');
});

Route::middleware('install.pending')->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'show'])->name('show');
    Route::post('/unlock', [InstallController::class, 'unlock'])->middleware('throttle:5,1')->name('unlock');
    Route::post('/environment', [InstallController::class, 'environment'])->name('environment');
    Route::post('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/user', [InstallController::class, 'user'])->name('user');
});

Route::get('/install/finish/{token}', [InstallController::class, 'finish'])->name('install.finish');

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
