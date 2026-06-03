<?php

namespace App\Http\Controllers\Web\Ceo;

use App\Http\Controllers\Web\WebController;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PromotionController extends WebController
{
    public function index(): View
    {
        $coupons = Coupon::query()
            ->select([
                'coupon_id',
                'coupon_code',
                'employee_id',
                'discount_type',
                'discount_value',
                'max_discount',
                'min_order_value',
                'max_uses',
                'used_count',
                'effective_from',
                'expired_at',
                'is_active',
                'notes',
            ])
            ->orderBy('coupon_id')
            ->get()
            ->map(function (Coupon $coupon): array {
                return [
                    'id' => (int) $coupon->coupon_id,
                    'code' => $coupon->coupon_code,
                    'empId' => $coupon->employee_id !== null ? (int) $coupon->employee_id : null,
                    'type' => strtoupper((string) $coupon->discount_type),
                    'value' => $this->numberOrNull($coupon->discount_value),
                    'maxDiscount' => $this->numberOrNull($coupon->max_discount),
                    'minOrder' => $this->numberOrNull($coupon->min_order_value),
                    'maxUses' => $coupon->max_uses !== null ? (int) $coupon->max_uses : null,
                    'usedCount' => (int) ($coupon->used_count ?? 0),
                    'from' => $this->dateString($coupon->effective_from),
                    'expired' => $this->dateString($coupon->expired_at),
                    'active' => (bool) $coupon->is_active,
                    'notes' => $coupon->notes,
                ];
            })
            ->values();

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

        $discountValue = (float) $validated['discount_value'];
        $maxDiscount = $validated['discount_type'] === 'FIXED'
            ? $discountValue
            : $this->numberOrNull($validated['max_discount'] ?? null);

        Coupon::create([
            'coupon_code' => $validated['coupon_code'],
            'employee_id' => (int) $employee->employee_id,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $discountValue,
            'max_discount' => $maxDiscount,
            'min_order_value' => $this->numberOrNull($validated['min_order_value'] ?? null) ?? 0,
            'max_uses' => isset($validated['max_uses']) ? (int) $validated['max_uses'] : null,
            'used_count' => 0,
            'effective_from' => Carbon::parse($validated['effective_from'])->startOfDay(),
            'expired_at' => Carbon::parse($validated['expired_at'])->endOfDay(),
            'is_active' => $request->boolean('is_active') ? 1 : 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('ceo.promotions')
            ->with('status', 'Đã thêm mã khuyến mãi mới.');
    }

    public function end(Request $request, Coupon $coupon): RedirectResponse|JsonResponse
    {
        $coupon->update([
            'is_active' => 0,
        ]);

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

    private function numberOrNull(mixed $value): ?float
    {
        return $value !== null ? (float) $value : null;
    }

    private function dateString(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
