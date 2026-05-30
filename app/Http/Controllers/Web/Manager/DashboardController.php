<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends WebController
{
    public function index(): View
    {
        return view('pages.manager.branch-dashboard');
    }

    public function filter(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly loc dashboard manager.');
    }
}
