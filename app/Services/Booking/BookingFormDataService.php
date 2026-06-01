<?php

namespace App\Services\Booking;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Services\PublicBranchService;
use Throwable;

class BookingFormDataService
{
    public function __construct(
        private PublicBranchService $branches,
        private BookingRoomAvailabilityService $roomAvailability,
        private BookingPetEligibilityService $petEligibility
    ) {}

    public function bookingFormViewData(string $branchId, bool $isAuthenticated, ?User $user = null): array
    {
        $branches = $this->bookingBranches();
        abort_if($branches === [], 404);

        $selectedBranch = collect($branches)
            ->first(fn (array $branch): bool => (string) $branch['id'] === (string) $branchId)
            ?: $branches[0];

        return [
            'id' => $selectedBranch['id'],
            'branchId' => $selectedBranch['id'],
            'bookingData' => [
                'today' => now()->toDateString(),
                'isAuthenticated' => $isAuthenticated,
                'loginUrl' => route('authentication.login'),
                'branch' => $selectedBranch,
                'branches' => $branches,
                'roomTypes' => $this->bookingRoomTypes((string) $selectedBranch['id']),
                'roomTypeAvailabilityUrl' => route('booking.branch.room-types.availability', $selectedBranch['id']),
                'pets' => $this->petEligibility->customerPets($this->customerForUser($user)),
                'services' => $this->bookingServices(),
                'availability' => $this->roomAvailability->bookingAvailability((string) $selectedBranch['id']),
            ],
        ];
    }

    public function bookingBranches(): array
    {
        return $this->branches
            ->branches()
            ->values()
            ->all();
    }

    public function bookingRoomTypes(string $branchId): array
    {
        return $this->roomAvailability->getRoomTypeAvailability($branchId);
    }

    public function bookingServices(): array
    {
        try {
            return Service::where('is_active', 1)
                ->orderBy('service_name')
                ->get()
                ->map(fn (Service $service) => [
                    'id' => (string) $service->service_id,
                    'name' => $service->service_name,
                    'price' => (int) $service->base_price,
                    'species' => $service->species ?: 'ALL',
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    private function customerForUser(?User $user): ?Customer
    {
        if (! $user) {
            return null;
        }

        return $user->customer ?: Customer::where('user_id', $user->id)->first();
    }
}
