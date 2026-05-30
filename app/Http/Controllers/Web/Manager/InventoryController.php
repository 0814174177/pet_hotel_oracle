<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends WebController
{
    public function index(): View
    {
        return view('pages.manager.inventory-management');
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('status', 'Xu ly cap nhat ton kho.');
    }
}
