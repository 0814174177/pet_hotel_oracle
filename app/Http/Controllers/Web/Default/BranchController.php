<?php

namespace App\Http\Controllers\Web\Default;

use App\Http\Controllers\Web\WebController;
use App\Models\TypeRoom;
use App\Services\PublicBranchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class BranchController extends WebController
{
    public function __construct(private PublicBranchService $branches)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', $request->query('keyword', ''))),
            'district' => trim((string) $request->query('district', 'all')) ?: 'all',
        ];

        $allBranches = $this->branches->branches();
        $filteredBranches = $this->branches->filter($filters['search'], $filters['district']);
        $districts = $allBranches
            ->pluck('district')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('client.branches.index', [
            'branches' => $filteredBranches,
            'districts' => $districts,
            'filters' => $filters,
            'totalBranches' => $allBranches->count(),
        ]);
    }

    public function show(string $branchId): View
    {
        $branch = $this->branches->find($branchId);

        abort_unless($branch, 404);

        return view('client.branches.show', [
            'id' => $branchId,
            'branchId' => $branchId,
            'branch' => $branch,
            'bannerImage' => $branch['image'],
            'roomTypes' => $this->branchRoomTypes((int) $branchId),
        ]);
    }

    public function filter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
        ]);

        $keyword = trim((string) ($validated['keyword'] ?? ''));
        $district = trim((string) ($validated['district'] ?? 'all')) ?: 'all';
        $data = $this->branches->filter($keyword, $district);

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $data->count(),
        ]);
    }

    private function branchRoomTypes(int $branchId): Collection
    {
        try {
            $typeRooms = TypeRoom::query()
                ->where('is_active', true)
                ->whereHas('rooms', fn ($query) => $query->where('branch_id', $branchId))
                ->orderBy('base_price_per_day')
                ->get();

            if ($typeRooms->isEmpty()) {
                $typeRooms = TypeRoom::query()
                    ->where('is_active', true)
                    ->orderBy('base_price_per_day')
                    ->get();
            }

            if ($typeRooms->isNotEmpty()) {
                return $typeRooms
                    ->values()
                    ->map(fn (TypeRoom $typeRoom, int $index): array => $this->formatTypeRoom($typeRoom, $index));
            }
        } catch (Throwable) {
            return $this->fallbackTypeRooms();
        }

        return $this->fallbackTypeRooms();
    }

    private function formatTypeRoom(TypeRoom $typeRoom, int $index): array
    {
        return [
            'id' => (int) $typeRoom->type_room_id,
            'name' => $this->displayTypeRoomName($typeRoom),
            'description' => $typeRoom->notes ?: 'Thông tin loại phòng đang được cập nhật.',
            'capacity' => $this->capacityText((int) $typeRoom->max_slot),
            'weight' => $this->weightText($typeRoom->pet_weight_min_kg, $typeRoom->pet_weight_max_kg),
            'price' => number_format((float) $typeRoom->base_price_per_day, 0, ',', '.').'đ / đêm',
            'icon' => $this->roomTypeIcon($index),
            'tone' => $this->roomTypeTone($index),
            'badge' => $this->roomTypeBadge($index),
            'detailUrl' => route('type-room.show', $typeRoom->type_room_id),
        ];
    }

    private function fallbackTypeRooms(): Collection
    {
        return collect([
            [
                'id' => 1,
                'name' => 'Phòng nhỏ',
                'description' => 'Không gian lưu trú cơ bản, sạch sẽ và phù hợp với thú cưng nhỏ.',
                'capacity' => '1 - 2 bé',
                'weight' => 'Dưới 10kg',
                'price' => '150.000đ / đêm',
                'icon' => 'fa-house',
                'tone' => 'gray',
                'badge' => 'Phổ biến',
                'detailUrl' => route('type-room.show', 1),
            ],
            [
                'id' => 2,
                'name' => 'Phòng vừa',
                'description' => 'Không gian rộng hơn, chăm sóc kỹ hơn và phù hợp thú cưng cỡ vừa.',
                'capacity' => '1 - 2 bé',
                'weight' => '10kg - 25kg',
                'price' => '220.000đ / đêm',
                'icon' => 'fa-star',
                'tone' => 'yellow',
                'badge' => 'Bán chạy',
                'detailUrl' => route('type-room.show', 2),
            ],
            [
                'id' => 3,
                'name' => 'Phòng lớn',
                'description' => 'Không gian riêng tư, cao cấp, phù hợp thú cưng lớn hoặc cần chăm sóc 1-1.',
                'capacity' => '1 bé/phòng',
                'weight' => '25kg - 45kg',
                'price' => '350.000đ / đêm',
                'icon' => 'fa-crown',
                'tone' => 'purple',
                'badge' => 'Cao cấp',
                'detailUrl' => route('type-room.show', 3),
            ],
        ]);
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

    private function capacityText(int $maxSlot): string
    {
        return $maxSlot > 1 ? '1 - '.$maxSlot.' bé' : '1 bé/phòng';
    }

    private function weightText(null|string|float $min, null|string|float $max): string
    {
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

    private function numberText(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function roomTypeIcon(int $index): string
    {
        return ['fa-house', 'fa-star', 'fa-crown'][$index % 3];
    }

    private function roomTypeTone(int $index): string
    {
        return ['gray', 'yellow', 'purple'][$index % 3];
    }

    private function roomTypeBadge(int $index): string
    {
        return ['Phổ biến', 'Bán chạy', 'Cao cấp'][$index % 3];
    }
}
