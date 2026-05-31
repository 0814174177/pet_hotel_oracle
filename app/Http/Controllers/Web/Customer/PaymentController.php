<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Web\WebController;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends WebController
{
    public function __construct(private PaymentRepositoryInterface $payments)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfGuest('Vui lòng đăng nhập trước khi thanh toán.')) {
            return $redirect;
        }

        $bookingId = $request->query('booking_id');

        if (! $bookingId) {
            return $this->missingPaymentRedirect('Không tìm thấy thông tin booking cần thanh toán.');
        }

        return $this->paymentViewForBooking((string) $bookingId);
    }

    public function show(string $bookingId): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfGuest('Vui lòng đăng nhập trước khi thanh toán.')) {
            return $redirect;
        }

        return $this->paymentViewForBooking($bookingId);
    }

    public function process(Request $request, string $bookingId): RedirectResponse
    {
        if ($redirect = $this->redirectIfGuest('Vui lòng đăng nhập trước khi thanh toán.')) {
            return $redirect;
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customer', 'phone')->ignore(Auth::user()?->customer?->customer_id, 'customer_id'),
            ],
            'customer_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(Auth::id()),
            ],
            'payment_method' => ['required', Rule::in(['cod'])],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'customer_name.required' => 'Vui lòng nhập họ và tên.',
            'customer_name.max' => 'Họ và tên không được vượt quá 100 ký tự.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'customer_phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'customer_email.required' => 'Vui lòng nhập email.',
            'customer_email.email' => 'Email không hợp lệ.',
            'customer_email.unique' => 'Email này đã được sử dụng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Pet Hotel hiện chỉ hỗ trợ thanh toán trực tiếp khi nhận phòng.',
            'coupon_code.max' => 'Mã giảm giá không được vượt quá 50 ký tự.',
        ]);

        $booking = $this->payments->confirmBookingPaymentForUser(
            Auth::user(),
            $bookingId,
            $validated['payment_method'],
            $validated['coupon_code'] ?? null,
            [
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'],
            ]
        );

        if (! $booking) {
            return $this->missingPaymentRedirect('Đơn booking không tồn tại hoặc không thuộc tài khoản của bạn.');
        }

        return redirect()
            ->route('payment.success', ['booking_id' => $booking->booking_id])
            ->with('status', 'Thanh toán thành công. Cảm ơn bạn đã sử dụng dịch vụ của Pet Hotel.');
    }

    public function applyCoupon(Request $request, string $bookingId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'exists' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        $validated = $request->validate([
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ], [
            'coupon_code.max' => 'Mã giảm giá không được vượt quá 50 ký tự.',
        ]);

        $preview = $this->payments->previewCouponForUser(
            Auth::user(),
            $bookingId,
            $validated['coupon_code'] ?? null
        );

        if (! $preview) {
            return response()->json([
                'exists' => false,
                'message' => 'Hóa đơn không tồn tại.',
            ], 404);
        }

        return response()->json([
            'exists' => true,
            'payment' => $preview,
        ]);
    }

    public function success(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfGuest('Vui lòng đăng nhập để xem kết quả thanh toán.')) {
            return $redirect;
        }

        $bookingId = $request->query('booking_id');

        if (! $bookingId) {
            return redirect()->route('profile.history-booking.index');
        }

        $orderStatus = $this->payments->orderStatusForUser(Auth::user(), (string) $bookingId);

        if (! $orderStatus || strtoupper((string) $orderStatus['status']) !== 'COMPLETED') {
            return redirect()
                ->route('payment.show', $bookingId)
                ->withErrors(['payment' => 'Thanh toán chưa được ghi nhận. Vui lòng xác nhận thanh toán trước.']);
        }

        $data = $this->payments->paymentPageDataForUser(Auth::user(), (string) $bookingId);

        if (! $data) {
            return $this->missingPaymentRedirect('Đơn booking không tồn tại hoặc không thuộc tài khoản của bạn.');
        }

        return view('client.payments.success', $data);
    }

    public function failed(Request $request): RedirectResponse
    {
        $bookingId = $request->query('booking_id');

        if ($bookingId) {
            $this->payments->cancelPendingPaymentForUser(Auth::user(), (string) $bookingId);

            return redirect()
                ->route('profile.history-booking.index')
                ->withErrors(['payment' => 'Thanh toán đã hủy. Phòng đã được mở lại nếu booking chưa hoàn tất.']);
        }

        return $this->missingPaymentRedirect('Thanh toán chưa thành công.');
    }

    public function checkStatus(string $bookingId): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'exists' => false,
                'message' => 'Bạn chưa đăng nhập.',
            ], 401);
        }

        $orderStatus = $this->payments->orderStatusForUser(Auth::user(), $bookingId);

        if (! $orderStatus) {
            return response()->json([
                'exists' => false,
                'message' => 'Hóa đơn không tồn tại.',
            ], 404);
        }

        return response()->json([
            'exists' => true,
            ...$orderStatus,
        ]);
    }

    private function paymentViewForBooking(string $bookingId): View|RedirectResponse
    {
        $data = $this->payments->paymentPageDataForUser(Auth::user(), $bookingId);

        if (! $data) {
            return $this->missingPaymentRedirect('Đơn booking không tồn tại hoặc không thuộc tài khoản của bạn.');
        }

        return view('client.payments.create', $data);
    }

    private function missingPaymentRedirect(string $message): RedirectResponse
    {
        return redirect()
            ->route('profile.history-booking.index')
            ->withErrors(['payment' => $message]);
    }
}
