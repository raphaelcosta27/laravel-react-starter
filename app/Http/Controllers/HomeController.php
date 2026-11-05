<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => config('template.registration_enabled'),
            'status' => [
                'database' => rescue(fn () => (bool) DB::connection()->getPdo(), false, false),
                'checkedAt' => now()->toIso8601String(),
            ],
        ]);
    }
}
