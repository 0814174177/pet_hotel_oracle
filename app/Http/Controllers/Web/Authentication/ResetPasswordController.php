<?php

namespace App\Http\Controllers\Web\Authentication;

use App\Http\Controllers\Web\WebController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends WebController
{
    public function show(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'token.required' => 'Liên kết đặt lại mật khẩu không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $email = strtolower(trim($validated['email']));

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (
            ! $record ||
            ! $record->created_at ||
            Carbon::parse($record->created_at)->addMinutes(config('auth.passwords.users.expire', 60))->isPast() ||
            ! hash_equals($record->token, hash('sha256', $validated['token']))
        ) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
                ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Không tìm thấy tài khoản phù hợp.',
                ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        return redirect()
            ->route('authentication.login')
            ->with('status', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
    }
}
