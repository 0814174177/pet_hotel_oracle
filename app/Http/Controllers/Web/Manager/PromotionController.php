<?php

namespace App\Http\Controllers\Web\Manager;

use App\Http\Controllers\Web\WebController;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use Illuminate\Contracts\View\View;

class PromotionController extends WebController
{
    public function __construct(private PromotionRepositoryInterface $promotions)
    {
    }

    public function index(): View
    {
        $coupons = $this->promotions->managementCoupons();

        return view('pages.manager.promotion-management', [
            'coupons' => $coupons,
        ]);
    }
}
