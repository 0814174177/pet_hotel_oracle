<?php

namespace App\Http\Controllers\Web\Authentication;

use App\Http\Controllers\Web\WebController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends WebController
{
    private const RESET_LINK_SENT_MESSAGE = 'Hệ thống sẽ gửi link đặt lại mật khẩu nếu email tồn tại.';

    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Định dạng email không hợp lệ.',
        ]);

        $email = strtolower(trim($validated['email']));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()->with('status', self::RESET_LINK_SENT_MESSAGE);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => hash('sha256', $token),
                'created_at' => now(),
            ]
        );

        $resetUrl = route('authentication.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);

        Mail::send('emails.reset-password', [
            'user' => $user,
            'resetUrl' => $resetUrl,
        ], function ($message) use ($email): void {
            $message->to($email)
                ->subject('Đặt lại mật khẩu Pet Hotel');
        });

        return back()->with('status', self::RESET_LINK_SENT_MESSAGE);
    }
}
