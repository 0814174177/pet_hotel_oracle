<?php

namespace App\Http\Controllers\Web\Customer\Profile;

use App\Http\Controllers\Web\WebController;
use App\Models\BookingRoomPet;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PetController extends WebController
{
    public function index(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để xem thú cưng của bạn.');
        }

        $user->loadMissing('customer');

        $pets = $user->customer
            ? $this->customerPetsWithCareStatus($user->customer)
            : collect();

        return view('client.pets.index', compact('pets'));
    }

    public function create(): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfGuest('Vui lòng đăng nhập để thêm thú cưng.')) {
            return $redirect;
        }

        return view('client.pets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để thêm thú cưng.');
        }

        $user->loadMissing('customer');

        if (! $user->customer) {
            return back()
                ->withInput()
                ->withErrors(['pet_name' => 'Tài khoản hiện tại chưa có hồ sơ khách hàng.']);
        }

        $validated = $request->validate([
            'pet_name' => ['required', 'string', 'max:60'],
            'species' => ['required', Rule::in(['DOG', 'CAT'])],
            'gender' => ['nullable', Rule::in(['MALE', 'FEMALE', 'UNKNOWN'])],
            'breed' => ['nullable', 'string', 'max:50'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:999.99'],
            'special_notes' => ['nullable', 'string', 'max:1000'],
            'pet_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], $this->petValidationMessages());

        $petData = [
            'customer_id' => $user->customer->customer_id,
            'pet_name' => $validated['pet_name'],
            'species' => $validated['species'],
            'breed' => $validated['breed'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'special_notes' => $validated['special_notes'] ?? null,
            'sex' => $validated['gender'] ?? 'UNKNOWN',
        ];

        if ($request->hasFile('pet_image')) {
            $petData['pet_image'] = $request->file('pet_image')->store('pets', 'public');
        }

        Pet::create($petData);

        return redirect()
            ->route('profile.pets.index')
            ->with('status', 'Đã thêm thú cưng mới.');
    }

    public function edit(string $petId): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để chỉnh sửa thú cưng.');
        }

        $user->loadMissing('customer');

        $pet = $user->customer
            ? Pet::with('bookingRoomPets.bookingRoom.booking')
                ->where('customer_id', $user->customer->customer_id)
                ->where('pet_id', $petId)
                ->first()
            : null;

        if (! $pet) {
            return redirect()
                ->route('profile.pets.index')
                ->withErrors(['pet' => 'Không tìm thấy thú cưng trong hồ sơ của bạn.']);
        }

        return view('client.pets.edit', [
            'id' => $petId,
            'petId' => $petId,
            'pet' => $pet,
        ]);
    }

    public function update(Request $request, string $petId): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $this->redirectToLogin('Vui lòng đăng nhập để chỉnh sửa thú cưng.');
        }

        $user->loadMissing('customer');

        $pet = $user->customer
            ? Pet::with('bookingRoomPets.bookingRoom.booking')
                ->where('customer_id', $user->customer->customer_id)
                ->where('pet_id', $petId)
                ->first()
            : null;

        if (! $pet) {
            return redirect()
                ->route('profile.pets.index')
                ->withErrors(['pet' => 'Không tìm thấy thú cưng trong hồ sơ của bạn.']);
        }

        $validated = $request->validate([
            'pet_name' => ['required', 'string', 'max:60'],
            'species' => ['required', Rule::in(['DOG', 'CAT'])],
            'gender' => ['nullable', Rule::in(['MALE', 'FEMALE', 'UNKNOWN'])],
            'breed' => ['nullable', 'string', 'max:50'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:999.99'],
            'special_notes' => ['nullable', 'string', 'max:1000'],
            'pet_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ], $this->petValidationMessages());

        $petData = [
            'pet_name' => $validated['pet_name'],
            'species' => $validated['species'],
            'breed' => $validated['breed'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'special_notes' => $validated['special_notes'] ?? null,
            'sex' => $validated['gender'] ?? 'UNKNOWN',
        ];

        if ($request->hasFile('pet_image')) {
            $this->deleteStoredPetImage($pet->pet_image);
            $petData['pet_image'] = $request->file('pet_image')->store('pets', 'public');
        }

        $pet->update($petData);

        return redirect()
            ->route('profile.pets.index')
            ->with('status', 'Đã cập nhật thông tin thú cưng.');
    }

    private function customerPetsWithCareStatus($customer): EloquentCollection
    {
        return $customer->pets()
            ->with('bookingRoomPets.bookingRoom.booking')
            ->orderBy('pet_name')
            ->get()
            ->each(function (Pet $pet): void {
                $isInRoom = $this->petIsInRoom($pet);

                $pet->setAttribute('is_in_room', $isInRoom);
                $pet->setAttribute(
                    'room_status_label',
                    $isInRoom ? 'Đang ở trong phòng' : 'Không ở trong phòng'
                );
                $pet->setAttribute(
                    'room_status_message',
                    $isInRoom
                        ? 'Thú cưng đang lưu trú; chỉ cập nhật khi thông tin thật sự cần thiết.'
                        : 'Có thể chỉnh sửa thông tin thú cưng.'
                );
            });
    }

    private function petValidationMessages(): array
    {
        return [
            'pet_name.required' => 'Vui lòng nhập tên thú cưng.',
            'pet_name.max' => 'Tên thú cưng không được vượt quá 60 ký tự.',
            'species.required' => 'Vui lòng chọn loài thú cưng.',
            'species.in' => 'Pet Hotel chỉ hỗ trợ chó và mèo.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'breed.max' => 'Giống thú cưng không được vượt quá 50 ký tự.',
            'weight_kg.numeric' => 'Cân nặng phải là số.',
            'weight_kg.min' => 'Cân nặng phải lớn hơn hoặc bằng 0.1kg.',
            'weight_kg.max' => 'Cân nặng không được vượt quá 999.99kg.',
            'special_notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
            'pet_image.image' => 'File tải lên phải là ảnh.',
            'pet_image.mimes' => 'Ảnh phải có định dạng jpeg, png hoặc jpg.',
            'pet_image.max' => 'Ảnh quá lớn, vui lòng chọn ảnh dưới 2MB.',
        ];
    }

    private function deleteStoredPetImage(?string $path): void
    {
        if (blank($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/'));
        }

        Storage::disk('public')->delete($normalizedPath);
    }

    private function petIsInRoom(Pet $pet): bool
    {
        return BookingRoomPet::query()
            ->where('pet_id', $pet->pet_id)
            ->whereHas('bookingRoom.booking', function ($query): void {
                $query
                    ->where('status', 'CHECKED_IN')
                    ->whereNull('checkout_actual_at');
            })
            ->exists();
    }
}
