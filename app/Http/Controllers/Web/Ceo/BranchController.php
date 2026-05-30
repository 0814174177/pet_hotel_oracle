<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends WebController
{
    public function index(): View
    {
        return view('pages.ceo.branch-management');
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly luu chi nhanh.');
    }
}
