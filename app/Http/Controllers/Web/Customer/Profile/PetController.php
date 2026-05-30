<?php

namespace App\Http\Controllers\Web\Customer\Profile;

use App\Http\Controllers\Web\WebController;
use App\Models\BookingRoomPet;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PetController extends WebController
{
    public function index(): View|RedirectResponse
    {
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
        ]);

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

        if ($this->petIsInRoom($pet)) {
            return redirect()
                ->route('profile.pets.index')
                ->withErrors(['pet' => 'Không thể sửa thông tin vì thú cưng này đang được lưu trú trong chuồng/phòng.']);
        }

        return view('client.pets.edit', [
            'id' => $petId,
            'petId' => $petId,
            'pet' => $pet,
        ]);
    }

    public function update(Request $request, string $petId): RedirectResponse
    {
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

        if ($this->petIsInRoom($pet)) {
            return redirect()
                ->route('profile.pets.index')
                ->withErrors(['pet' => 'Không thể sửa thông tin vì thú cưng này đang được lưu trú trong chuồng/phòng.']);
        }

        $validated = $request->validate([
            'pet_name' => ['required', 'string', 'max:60'],
            'species' => ['required', Rule::in(['DOG', 'CAT'])],
            'gender' => ['nullable', Rule::in(['MALE', 'FEMALE', 'UNKNOWN'])],
            'breed' => ['nullable', 'string', 'max:50'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.1', 'max:999.99'],
            'special_notes' => ['nullable', 'string', 'max:1000'],
            'pet_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $petData = [
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
                        ? 'Không thể sửa thông tin vì thú cưng này đang được lưu trú trong chuồng/phòng.'
                        : 'Có thể chỉnh sửa thông tin thú cưng.'
                );
            });
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
