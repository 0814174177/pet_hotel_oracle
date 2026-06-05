<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Http\Controllers\Web\WebController;
use App\Models\Coupon;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PromotionController extends WebController
{
    public function __construct(private PromotionRepositoryInterface $promotions)
    {
    }

    public function index(): View
    {
        $coupons = $this->promotions->managementCoupons();

        return view('pages.ceo.promotion-management', compact('coupons'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'coupon_code' => strtoupper(trim((string) $request->input('coupon_code'))),
            'discount_type' => strtoupper((string) $request->input('discount_type')),
        ]);

        $validated = $request->validate([
            'coupon_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupon', 'coupon_code'),
            ],
            'discount_type' => ['required', Rule::in(['PERCENT', 'FIXED'])],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'max_discount' => ['nullable', 'numeric', 'gte:0'],
            'min_order_value' => ['nullable', 'numeric', 'gte:0'],
            'max_uses' => ['nullable', 'integer', 'gte:1'],
            'effective_from' => ['required', 'date'],
            'expired_at' => ['required', 'date', 'after:effective_from'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], [
            'coupon_code.required' => 'Vui lòng nhập mã coupon.',
            'coupon_code.regex' => 'Mã coupon chỉ được dùng chữ không dấu, số, gạch ngang hoặc gạch dưới.',
            'coupon_code.unique' => 'Mã coupon này đã tồn tại.',
            'discount_type.required' => 'Vui lòng chọn loại khuyến mãi.',
            'discount_type.in' => 'Loại khuyến mãi không hợp lệ.',
            'discount_value.required' => 'Vui lòng nhập giá trị giảm.',
            'discount_value.gt' => 'Giá trị giảm phải lớn hơn 0.',
            'max_discount.gte' => 'Giảm tối đa không được âm.',
            'min_order_value.gte' => 'Đơn tối thiểu không được âm.',
            'max_uses.gte' => 'Lượt dùng tối đa phải từ 1 trở lên.',
            'effective_from.required' => 'Vui lòng nhập ngày bắt đầu.',
            'expired_at.required' => 'Vui lòng nhập ngày hết hạn.',
            'expired_at.after' => 'Ngày hết hạn phải sau ngày bắt đầu.',
        ]);

        if ($validated['discount_type'] === 'PERCENT' && (float) $validated['discount_value'] > 100) {
            return back()
                ->withInput()
                ->withErrors(['discount_value' => 'Giá trị giảm theo phần trăm phải từ 1 đến 100.']);
        }

        $employee = Auth::user()?->employee;

        if (! $employee) {
            return back()
                ->withInput()
                ->withErrors(['employee_id' => 'Không xác định được nhân viên từ tài khoản hiện tại.']);
        }

        $this->promotions->createCoupon(
            $validated,
            (int) $employee->employee_id,
            $request->boolean('is_active')
        );

        return redirect()
            ->route('ceo.promotions')
            ->with('status', 'Đã thêm mã khuyến mãi mới.');
    }

    public function end(Request $request, Coupon $coupon): RedirectResponse|JsonResponse
    {
        $this->promotions->deactivateCoupon($coupon);

        $message = 'Đã kết thúc mã khuyến mãi.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'coupon_id' => (int) $coupon->coupon_id,
            ]);
        }

        return redirect()
            ->route('ceo.promotions')
            ->with('status', $message);
    }
}
