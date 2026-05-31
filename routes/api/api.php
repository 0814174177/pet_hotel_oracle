<?php

use App\Http\Controllers\Web\Customer\BookingController as CustomerBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Laravel tu dong gan prefix /api cho file nay.
| Web routes hien thi trang, API routes tra ve JSON cho frontend.
*/

Route::get('/', fn () => response()->json([
    'status' => 'ok',
]))->name('api.index');
Route::get('/booking/branch/{branchId}/room-types/availability', [CustomerBookingController::class, 'roomTypeAvailability'])
    ->name('api.booking.branch.room-types.availability');

require __DIR__.'/dashboard/index.php';
