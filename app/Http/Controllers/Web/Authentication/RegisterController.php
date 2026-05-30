<?php

namespace App\Http\Controllers\Web\Authentication;

use App\Http\Controllers\Web\WebController;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;   
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisterController extends WebController
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): JsonResponse
    {
    // 1. Gộp dữ liệu trước khi Validate
        $request->merge([
            'full_name' => $request->input('full_name', $request->input('name')),
        ]);

        // 2. Định nghĩa thông báo lỗi bằng tiếng Việt (Tùy chọn)
        $messages = [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.unique' => 'Số điện thoại này đã tồn tại trong hệ thống.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải chứa ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ];

        // 3. Thực thi Validation
        // Chú ý: Vì AJAX gửi Header 'Accept: application/json', nếu có lỗi, 
        // Laravel tự động dừng tại đây và ném về HTTP 422 kèm chuỗi JSON chứa các lỗi.
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:customer,phone'], // Kiểm tra lại: Tên bảng trong CSDL thường là số nhiều 'customers'
            'address' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $messages);

        // 4. Xử lý Logic Database với Try-Catch
        try {
            $user = DB::transaction(function () use ($validated): User {
                $user = User::create([
                    'name' => $validated['full_name'],
                    'email' => strtolower(trim($validated['email'])),
                    'password' => Hash::make($validated['password']),
                    'role' => 'CUSTOMER',
                    'is_active' => true,
                ]);

                Customer::create([
                    'user_id' => $user->id,
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'] ?? null,
                ]);

                return $user;
            });

            // Đăng nhập và cấp mới Session
            Auth::login($user);
            $request->session()->regenerate();

            // 5. Trả về JSON thành công cho AJAX
            return response()->json([
                'status' => 'success',
                'message' => 'Đăng ký tài khoản thành công.',
                'redirect_url' => url('/?registered=1')
            ], 200);

        } catch (\Exception $e) {
            // Ghi lại lỗi vào file log của Laravel (storage/logs/laravel.log)
            Log::error('Lỗi đăng ký thành viên: ' . $e->getMessage());

            // Trả về JSON lỗi hệ thống (HTTP 500)
            return response()->json([
                'status' => 'error',
                'message' => 'Hệ thống đang bận hoặc có sự cố, vui lòng thử lại sau.'
            ], 500);
        }
    }
}
