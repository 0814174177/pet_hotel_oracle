<?php

namespace App\Http\Controllers\Web\Default;

use App\Http\Controllers\Web\WebController;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\Service;
use App\Models\TypeRoom;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RoomController extends WebController
{
    public function index(): View
    {
        return view('client.rooms.dog');
    }

    public function dog(): View
    {
        return view('client.rooms.dog');
    }

    public function cat(): View
    {
        return view('client.rooms.cat');
    }

    public function show(string $roomId): View
    {
        $view = $roomId === 'cat' ? 'client.rooms.cat' : 'client.rooms.dog';

        return view($view, [
            'id' => $roomId,
            'roomId' => $roomId,
        ]);
    }

    public function byTypeAndPet(string $type, string $pet): View
    {
        return $this->showByTypeAndSpecies(request(), $type, $pet);
    }

    public function showByTypeAndSpecies(Request $request, string $type, string $species): View
    {
        $typeRoomId = $this->typeRoomIdForSlug($type);

        if ($typeRoomId === null || ! array_key_exists($species, $this->speciesOptions())) {
            abort(404);
        }

        $validated = $request->validate([
            'check_in' => ['nullable', 'date', 'required_with:check_out'],
            'check_out' => ['nullable', 'date', 'required_with:check_in', 'after:check_in'],
        ], [
            'check_in.required_with' => 'Vui lòng nhập đủ ngày nhận và ngày trả phòng.',
            'check_out.required_with' => 'Vui lòng nhập đủ ngày nhận và ngày trả phòng.',
            'check_out.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        return $this->detailView(
            $typeRoomId,
            $species,
            $validated['check_in'] ?? null,
            $validated['check_out'] ?? null,
            $type
        );
    }

    public function typeRoom(string $typeRoomId): View
    {
        return $this->detailView((int) $typeRoomId);
    }

    private function detailView(
        int $typeRoomId,
        ?string $pet = null,
        ?string $checkIn = null,
        ?string $checkOut = null,
        ?string $typeSlug = null
    ): View {
        $roomSlug = $this->roomSlugForTypeRoom($typeRoomId);
        $typeRoomModel = TypeRoom::where('type_room_id', $typeRoomId)->firstOrFail();
        $rooms = Room::with('branch')
            ->where('type_room_id', $typeRoomModel->type_room_id)
            ->orderBy('branch_id')
            ->orderBy('room_number')
            ->get();
        $busyRoomIds = $this->busyRoomIds($rooms->pluck('room_id'), $checkIn, $checkOut);
        $hasDateRange = filled($checkIn) && filled($checkOut);
        $roomRows = $rooms->map(fn (Room $room): array => $this->roomPayload($room, $typeRoomModel, $busyRoomIds, $hasDateRange));

        $viewData = [
            'typeRoom' => $this->typeRoomPayload($typeRoomModel, $rooms, $pet, $roomRows, $typeSlug ?: $roomSlug),
            'rooms' => $rooms,
            'roomRows' => $roomRows,
            'pet' => $pet,
            'species' => $pet ?: 'dog',
            'type' => $typeSlug ?: $roomSlug,
            'typeLabel' => $this->typeLabel($typeSlug ?: $roomSlug),
            'speciesLabel' => $this->petLabel($pet) ?? 'thú cưng',
            'totalRooms' => $rooms->count(),
            'availableCount' => $roomRows->where('is_available', true)->count(),
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'hasDateRange' => $hasDateRange,
            'dogRoomUrl' => route('rooms.by-type-species', ['type' => $roomSlug, 'species' => 'dog']),
            'catRoomUrl' => route('rooms.by-type-species', ['type' => $roomSlug, 'species' => 'cat']),
            'services' => $this->servicesForSpecies($pet ?: 'dog'),
            'availableRooms' => $roomRows->where('is_available', true)->values(),
            'price' => (float) $typeRoomModel->base_price_per_day,
            'maxPets' => (int) $typeRoomModel->max_slot,
            'minWeightKg' => $typeRoomModel->pet_weight_min_kg !== null ? (float) $typeRoomModel->pet_weight_min_kg : null,
            'maxWeightKg' => $typeRoomModel->pet_weight_max_kg !== null ? (float) $typeRoomModel->pet_weight_max_kg : null,
        ];

        return view('client.rooms.type-room', $viewData);
    }

    private function typeRoomIdForSlug(string $type): ?int
    {
        return match ($type) {
            'small', 'normal' => 1,
            'medium', 'vip' => 2,
            'large', 'luxury' => 3,
            default => null,
        };
    }

    private function roomSlugForTypeRoom(string|int $typeRoomId): string
    {
        return match ((int) $typeRoomId) {
            1 => 'small',
            2 => 'medium',
            3 => 'large',
            default => abort(404),
        };
    }

    private function typeRoomPayload(TypeRoom $typeRoom, Collection $rooms, ?string $pet = null, ?Collection $roomRows = null, ?string $typeSlug = null): array
    {
        $name = $typeSlug ? $this->typeLabel($typeSlug) : $this->displayTypeRoomName($typeRoom);
        $petLabel = $this->petLabel($pet);

        if ($petLabel !== null) {
            $name .= ' cho '.mb_strtolower($petLabel, 'UTF-8');
        }

        return [
            'id' => $typeRoom->type_room_id,
            'name' => $name,
            'label' => $this->displayTypeRoomName($typeRoom),
            'price' => 'Từ '.number_format((float) $typeRoom->base_price_per_day, 0, ',', '.').'đ/ngày',
            'price_raw' => (float) $typeRoom->base_price_per_day,
            'area' => $this->roomAreaText($typeRoom, $pet),
            'capacity' => $this->capacityText((int) $typeRoom->max_slot),
            'weight' => $this->weightText($typeRoom),
            'description' => $typeRoom->notes ?: 'Thông tin loại phòng đang được cập nhật.',
            'features' => $this->roomFeatures($rooms, $roomRows),
            'care' => $typeRoom->notes ?: 'Nhân viên sẽ ghi nhận tình trạng, cân nặng và thói quen chăm sóc khi nhận phòng.',
            'images' => $this->typeRoomImages($this->typeRoomImageFolders($typeRoom, $pet)),
            'requiredAttributes' => $this->requiredAttributes($typeRoom, $rooms, $roomRows),
        ];
    }

    private function petLabel(?string $pet): ?string
    {
        return match ($pet) {
            'dog' => 'Chó',
            'cat' => 'Mèo',
            default => null,
        };
    }

    private function speciesOptions(): array
    {
        return [
            'dog' => 'DOG',
            'cat' => 'CAT',
        ];
    }

    private function servicesForSpecies(string $species): Collection
    {
        $databaseSpecies = $this->speciesOptions()[$species] ?? strtoupper($species);

        return Service::query()
            ->where('is_active', 1)
            ->whereIn('species', ['ALL', $databaseSpecies])
            ->orderBy('service_name')
            ->get();
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'small', 'normal' => 'Phòng nhỏ',
            'medium', 'vip' => 'Phòng vừa',
            'large', 'luxury' => 'Phòng lớn',
            default => abort(404),
        };
    }

    private function requiredAttributes(TypeRoom $typeRoom, Collection $rooms, ?Collection $roomRows = null): array
    {
        return [
            'Mã loại phòng' => (string) $typeRoom->type_room_id,
            'Tên loại phòng' => $this->displayTypeRoomName($typeRoom),
            'Giá cơ bản/ngày' => number_format((float) $typeRoom->base_price_per_day, 0, ',', '.').'đ',
            'Số thú cưng tối đa' => $this->capacityText((int) $typeRoom->max_slot),
            'Cân nặng phù hợp' => $this->weightText($typeRoom),
            'Số phòng trong hệ thống' => (string) $rooms->count(),
            'Số phòng còn trống' => (string) ($roomRows?->where('is_available', true)->count() ?? $rooms->where('status', 'AVAILABLE')->count()),
            'Ghi chú' => $typeRoom->notes ?: 'Đang cập nhật',
        ];
    }

    private function capacityText(int $maxSlot): string
    {
        return $maxSlot > 1 ? '1 - '.$maxSlot.' bé' : '1 bé/phòng';
    }

    private function weightText(TypeRoom $typeRoom): string
    {
        $min = $typeRoom->pet_weight_min_kg;
        $max = $typeRoom->pet_weight_max_kg;

        if ($min !== null && $max !== null) {
            return $this->numberText((float) $min).'kg - '.$this->numberText((float) $max).'kg';
        }

        if ($max !== null) {
            return 'Dưới '.$this->numberText((float) $max).'kg';
        }

        if ($min !== null) {
            return 'Từ '.$this->numberText((float) $min).'kg';
        }

        return 'Đang cập nhật';
    }

    private function roomFeatures(Collection $rooms, ?Collection $roomRows = null): array
    {
        $availableCount = $roomRows?->where('is_available', true)->count() ?? $rooms->where('status', 'AVAILABLE')->count();
        $branches = $rooms
            ->map(fn (Room $room) => $room->branch?->branch_name)
            ->filter()
            ->unique()
            ->values();

        return [
            'Số phòng hiện có: '.$rooms->count(),
            'Phòng sẵn sàng: '.$availableCount,
            'Có tại: '.($branches->isNotEmpty() ? $branches->implode(', ') : 'Đang cập nhật'),
        ];
    }

    private function busyRoomIds(Collection $roomIds, ?string $checkIn, ?string $checkOut): Collection
    {
        if (! filled($checkIn) || ! filled($checkOut) || $roomIds->isEmpty()) {
            return collect();
        }

        $effectiveStatuses = ['PENDING', 'PENDING_PAYMENT', 'HOLDING', 'CONFIRMED', 'CHECKED_IN'];

        return BookingRoom::query()
            ->whereIn('room_id', $roomIds->all())
            ->whereHas('booking', function ($query) use ($checkIn, $checkOut, $effectiveStatuses): void {
                $query->whereIn('status', $effectiveStatuses)
                    ->where('checkin_expected_at', '<', Carbon::parse($checkOut)->startOfDay()->toDateTimeString())
                    ->where('checkout_expected_at', '>', Carbon::parse($checkIn)->startOfDay()->toDateTimeString());
            })
            ->pluck('room_id')
            ->unique()
            ->values();
    }

    private function roomPayload(Room $room, TypeRoom $typeRoom, Collection $busyRoomIds, bool $hasDateRange): array
    {
        $isAvailable = $room->status === 'AVAILABLE' && (! $hasDateRange || ! $busyRoomIds->contains($room->room_id));
        $bookingUrl = route('booking.select');

        return [
            'id' => $room->room_id,
            'number' => $room->room_number,
            'branch_id' => $room->branch_id,
            'branch_name' => $room->branch?->branch_name ?? 'Đang cập nhật',
            'type_name' => $this->displayTypeRoomName($typeRoom),
            'price' => (float) $typeRoom->base_price_per_day,
            'price_text' => number_format((float) $typeRoom->base_price_per_day, 0, ',', '.').'đ/ngày',
            'weight_text' => $this->weightText($typeRoom),
            'capacity_text' => $this->capacityText((int) $typeRoom->max_slot),
            'status' => $room->status,
            'is_available' => $isAvailable,
            'availability_text' => $isAvailable ? 'Còn trống' : ($hasDateRange ? 'Đã được giữ' : 'Chưa sẵn sàng'),
            'booking_url' => $bookingUrl,
        ];
    }

    private function typeRoomImageFolders(TypeRoom $typeRoom, ?string $pet = null): array
    {
        $folder = match ((int) $typeRoom->type_room_id) {
            2 => 'vip',
            3 => 'luxury',
            default => 'normal',
        };

        if (in_array($pet, ['dog', 'cat'], true)) {
            return [
                'assets/client/images/type-room/'.$folder.'/'.$pet,
            ];
        }

        return [
            'assets/client/images/type-room/'.$folder.'/dog',
            'assets/client/images/type-room/'.$folder.'/cat',
        ];
    }

    private function displayTypeRoomName(TypeRoom $typeRoom): string
    {
        return match ((int) $typeRoom->type_room_id) {
            1 => 'Phòng nhỏ',
            2 => 'Phòng vừa',
            3 => 'Phòng lớn',
            default => $typeRoom->type_name,
        };
    }

    private function roomAreaText(TypeRoom $typeRoom, ?string $pet): string
    {
        $dogAreas = [
            1 => '3 m²',
            2 => '5 m²',
            3 => '8 m²',
        ];

        $catAreas = [
            1 => '2 m²',
            2 => '4 m²',
            3 => '6 m²',
        ];

        $areas = $pet === 'cat' ? $catAreas : $dogAreas;

        return $areas[(int) $typeRoom->type_room_id] ?? 'Đang cập nhật';
    }

    private function typeRoomImages(array $directories): array
    {
        $images = [];
        $publicRoot = str_replace('\\', '/', public_path());

        foreach ($directories as $directory) {
            $files = glob(public_path($directory).'/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE) ?: [];

            foreach ($files as $file) {
                $normalizedFile = str_replace('\\', '/', $file);
                $relativePath = ltrim(str_replace($publicRoot, '', $normalizedFile), '/');
                $images[] = asset($relativePath);
            }
        }

        return $images;
    }

    private function numberText(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }
}
