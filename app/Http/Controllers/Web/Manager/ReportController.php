<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends WebController
{
    public function index(): View
    {
        return view('pages.manager.branch-revenue-reports');
    }

    public function export(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly xuat bao cao manager.');
    }
}
