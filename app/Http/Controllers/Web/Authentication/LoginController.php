<?php

namespace App\Http\Controllers\Web\Authentication;

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class LoginController extends WebController
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $email = strtolower(trim($validated['email']));

        try {
            // Eager Loading để tối ưu truy vấn dữ liệu liên quan
            $user = User::with(['customer', 'employee'])
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email hoặc mật khẩu không chính xác.'
                ], 422);
            }

            if (! $user->is_active || ($user->isStaff() && $user->employee && ! $user->employee->isWorking())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tài khoản này đang bị khóa. Vui lòng liên hệ quản trị viên.'
                ], 422);
            }

            Auth::login($user, $request->boolean('remember'));

            $user->forceFill([
                'last_login_at' => now(),
            ])->save();

            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'redirect_url' => url($this->redirectPathFor($user))
            ], 200);

        } catch (\Exception $e) {
            Log::error('Lỗi hệ thống đăng nhập: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Hệ thống đang bận, vui lòng thử lại sau.'
            ], 500);
        }
    }

    /**
     * [POST] Xử lý đăng xuất tài khoản.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        // Xóa toàn bộ dữ liệu phiên làm việc hiện tại
        $request->session()->invalidate();
        
        // Cấp mới CSRF Token để bảo mật, chống tấn công giả mạo
        $request->session()->regenerateToken();

        return redirect()->route('authentication.login');
    }

    /**
     * Phân quyền điều hướng dựa trên Role của Pet Hotel.
     */
    private function redirectPathFor(User $user): string
    {
        return match ($user->role) {
            'ADMIN', 'CEO' => '/ceo/dashboard',
            'MANAGER', 'RECEPTIONIST', 'GROOMER' => '/manager/dashboard',
            default => '/', // Khách hàng (CUSTOMER)
        };
    }
}
