<?php

namespace App\Http\Controllers\Web\Customer\Profile;

use App\Http\Controllers\Web\WebController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends WebController
{
    public function show(): View|RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để xem hồ sơ cá nhân.');
        }

        $profile = $this->profilePayload($user);

        return view('client.profile.index', compact('profile', 'user'));
    }

    public function edit(): View|RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để chỉnh sửa hồ sơ cá nhân.');
        }

        $profile = $this->profilePayload($user);

        return view('client.profile.edit', compact('profile', 'user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để cập nhật hồ sơ cá nhân.');
        }

        $user->loadMissing(['customer', 'employee']);
        $person = $user->customer ?? $user->employee;

        $phoneRules = ['required', 'string', 'max:20'];

        if ($user->customer) {
            $phoneRules[] = Rule::unique('customer', 'phone')
                ->ignore($user->customer->customer_id, 'customer_id');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => $phoneRules,
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthday' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($validated['new_password']) && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
            return back()
                ->withInput()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        DB::transaction(function () use ($user, $person, $validated, $request): void {
            $user->forceFill([
                'name' => $validated['full_name'],
                'email' => strtolower(trim($validated['email'])),
            ]);

            if (! empty($validated['new_password'])) {
                $user->password = Hash::make($validated['new_password']);
            }

            $user->save();

            if (! $person) {
                return;
            }

            $personUpdates = [
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'birthday' => $validated['birthday'] ?? null,
            ];

            if ($person->getTable() === 'customer') {
                $personUpdates['address'] = $validated['address'] ?? null;
            }

            if ($request->hasFile('avatar')) {
                $personUpdates['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $person->forceFill($personUpdates)->save();
        });

        return redirect()
            ->route('profile.index')
            ->with('status', 'Đã cập nhật thông tin cá nhân.');
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private function profilePayload(User $user): array
    {
        $user->loadMissing(['customer', 'employee.branch']);

        $person = $user->customer ?? $user->employee;
        $fullName = $this->stringOrDefault($person?->full_name, $this->stringOrDefault($user->name, 'Khách hàng Pet Hotel'));
        $birthday = $person?->birthday ?? '';
        $avatar = $person?->avatar ?? null;

        return [
            'avatar_text' => $this->initials($fullName),
            'avatar_url' => $avatar ? asset('storage/'.$avatar) : '',
            'full_name' => $fullName,
            'email' => $this->stringOrDefault($user->email),
            'phone' => $this->stringOrDefault($person?->phone),
            'birthday' => $birthday ?: '',
            'address' => $this->stringOrDefault($user->customer?->address ?? $user->employee?->branch?->address),
            'member_since' => $this->stringOrDefault($user->created_at?->format('m/Y')),
            'defaults' => [
                'full_name' => 'Nhập họ và tên',
                'phone' => 'Nhập số điện thoại',
                'birthday' => '',
                'address' => 'Nhập địa chỉ',
            ],
        ];
    }

    private function stringOrDefault(mixed $value, string $default = ''): string
    {
        return filled($value) ? (string) $value : $default;
    }

    private function initials(string $name): string
    {
        $words = Str::of($name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->values();

        if ($words->isEmpty()) {
            return 'PH';
        }

        return $words
            ->take(-2)
            ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
