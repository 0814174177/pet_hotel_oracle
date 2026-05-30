<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinanceController extends WebController
{
    public function index(): View
    {
        return view('pages.ceo.finance-analytics');
    }

    public function export(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly xuat bao cao tai chinh.');
    }
}
