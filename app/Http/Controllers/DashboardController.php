<?php

namespace App\Http\Controllers;

use App\Support\Dashboard\Dashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Dashboard $dashboard): Response
    {
        return Inertia::render('Dashboard', ['blocks' => $dashboard->for($request->user())]);
    }
}
