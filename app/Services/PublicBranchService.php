<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicBranchService
{
    public function branches(): Collection
    {
        return Branch::query()
            ->where('is_active', 1)
            ->orderBy('branch_name')
            ->get()
            ->values()
            ->map(fn (Branch $branch, int $index): array => $this->formatBranch($branch, $index));
    }

    public function find(string|int $branchId): ?array
    {
        return $this->branches()->firstWhere('id', (int) $branchId);
    }

    public function filter(?string $keyword = null, ?string $district = null): Collection
    {
        $search = $this->searchable(trim((string) $keyword));
        $selectedDistrict = trim((string) ($district ?: 'all')) ?: 'all';
        $normalizedDistrict = $this->searchable($selectedDistrict);

        return $this->branches()
            ->filter(function (array $branch) use ($search, $selectedDistrict, $normalizedDistrict): bool {
                $matchesDistrict = $selectedDistrict === 'all'
                    || $this->searchable($branch['district'] ?? '') === $normalizedDistrict;

                $matchesSearch = $search === '' || Str::contains(
                    $this->searchable(implode(' ', [
                        $branch['name'] ?? '',
                        $branch['district'] ?? '',
                        $branch['address'] ?? '',
                        $branch['phone'] ?? '',
                        $branch['email'] ?? '',
                    ])),
                    $search
                );

                return $matchesDistrict && $matchesSearch;
            })
            ->values();
    }

    private function formatBranch(Branch $branch, int $index): array
    {
        $branchId = (int) $branch->branch_id;
        $district = $this->districtFrom($branch->branch_name.' '.$branch->address);
        $meta = $this->branchMeta($branch, $district, $index);
        $openTime = $branch->getAttribute('open_time');
        $closeTime = $branch->getAttribute('close_time');
        $rating = $branch->getAttribute('rating');
        $reviewCount = $branch->getAttribute('review_count');
        $imageUrl = $this->branchImageUrl($branch, $index);

        return [
            'id' => $branchId,
            'branch_id' => $branchId,
            'name' => $branch->branch_name,
            'branch_name' => $branch->branch_name,
            'address' => $branch->address,
            'phone' => $branch->phone ?: 'Đang cập nhật',
            'email' => $branch->email,
            'district' => $district,
            'open_time' => (string) ($openTime ?: $meta['open_time']),
            'close_time' => (string) ($closeTime ?: $meta['close_time']),
            'hours' => (string) ($openTime ?: $meta['open_time']).' - '.(string) ($closeTime ?: $meta['close_time']),
            'rating' => (string) ($rating ?? $meta['rating']),
            'reviews' => $reviewCount !== null ? (int) $reviewCount : (int) $meta['review_count'],
            'review_count' => $reviewCount !== null ? (int) $reviewCount : (int) $meta['review_count'],
            'image' => $imageUrl,
            'image_url' => $imageUrl,
            'map' => $meta['map'],
            'detailUrl' => route('branches.show', $branchId),
            'detail_url' => route('branches.show', $branchId),
            'bookingUrl' => url('/booking/branch/'.$branchId),
            'booking_url' => url('/booking/branch/'.$branchId),
        ];
    }

    private function branchMeta(Branch $branch, string $district, int $index): array
    {
        $districtMeta = [
            'Quận 1' => ['open_time' => '7:30', 'close_time' => '21:00', 'rating' => '4.6', 'review_count' => 89, 'map' => ['x' => 34, 'y' => 34, 'lat' => 10.7731, 'lng' => 106.7043]],
            'Thủ Đức' => ['open_time' => '8:00', 'close_time' => '19:00', 'rating' => '4.5', 'review_count' => 61, 'map' => ['x' => 72, 'y' => 28, 'lat' => 10.8509, 'lng' => 106.7716]],
            'Quận 7' => ['open_time' => '8:00', 'close_time' => '20:00', 'rating' => '4.8', 'review_count' => 127, 'map' => ['x' => 72, 'y' => 68, 'lat' => 10.7381, 'lng' => 106.7067]],
            'Gò Vấp' => ['open_time' => '8:00', 'close_time' => '20:00', 'rating' => '4.5', 'review_count' => 73, 'map' => ['x' => 42, 'y' => 42, 'lat' => 10.8281, 'lng' => 106.6781]],
            'Bình Thạnh' => ['open_time' => '8:00', 'close_time' => '20:00', 'rating' => '4.7', 'review_count' => 203, 'map' => ['x' => 49, 'y' => 48, 'lat' => 10.8077, 'lng' => 106.707]],
        ];

        $meta = $districtMeta[$district] ?? [
            'open_time' => '8:00',
            'close_time' => '20:00',
            'rating' => '4.5',
            'review_count' => 0,
            'map' => [
                'x' => 28 + (($index * 17) % 48),
                'y' => 30 + (($index * 19) % 42),
                'lat' => 10.7769 + (($index % 5) - 2) * 0.018,
                'lng' => 106.7009 + (($index % 4) - 1.5) * 0.018,
            ],
        ];

        $coordinates = $this->coordinatesFromDatabase($branch);

        if ($coordinates !== null) {
            $meta['map']['lat'] = $coordinates['lat'];
            $meta['map']['lng'] = $coordinates['lng'];
        }

        return $meta;
    }

    private function coordinatesFromDatabase(Branch $branch): ?array
    {
        $lat = $branch->getAttribute('latitude')
            ?? $branch->getAttribute('lat')
            ?? $branch->getAttribute('map_lat');
        $lng = $branch->getAttribute('longitude')
            ?? $branch->getAttribute('lng')
            ?? $branch->getAttribute('map_lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }

    private function branchImageUrl(Branch $branch, int $index): string
    {
        $storedImage = $branch->getAttribute('image_url') ?: $branch->getAttribute('image');

        if (filled($storedImage)) {
            $storedImage = (string) $storedImage;

            return Str::startsWith($storedImage, ['http://', 'https://', '/'])
                ? $storedImage
                : asset($storedImage);
        }

        $images = $this->branchImageUrls();

        if ($images === []) {
            return asset('assets/client/images/right-home-500x554.png');
        }

        $imageIndex = max(0, ((int) $branch->branch_id) - 1) % count($images);

        return $images[$imageIndex] ?? $images[$index % count($images)];
    }

    private function branchImageUrls(): array
    {
        static $urls = null;

        if ($urls !== null) {
            return $urls;
        }

        $directory = public_path('assets/client/images/branches');

        if (! is_dir($directory)) {
            return $urls = [];
        }

        $files = [];

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $files = [
                ...$files,
                ...(glob($directory.DIRECTORY_SEPARATOR.'*.'.$extension) ?: []),
                ...(glob($directory.DIRECTORY_SEPARATOR.'*.'.strtoupper($extension)) ?: []),
            ];
        }

        $files = array_values(array_unique($files));
        usort($files, 'strnatcasecmp');

        return $urls = collect($files)
            ->take(10)
            ->map(fn (string $path): string => asset('assets/client/images/branches/'.rawurlencode(basename($path))))
            ->values()
            ->all();
    }

    private function districtFrom(string $value): string
    {
        $searchable = $this->searchable($value);

        if (preg_match('/\bquan\s+(\d{1,2})\b/', $searchable, $matches)) {
            return 'Quận '.$matches[1];
        }

        foreach ($this->districtAliases() as $district => $aliases) {
            if (Str::contains($searchable, $aliases)) {
                return $district;
            }
        }

        return 'Khu vực khác';
    }

    private function districtAliases(): array
    {
        return [
            'Thủ Đức' => ['thu duc', 'tp thu duc', 'thanh pho thu duc'],
            'Gò Vấp' => ['go vap'],
            'Bình Thạnh' => ['binh thanh'],
            'Phú Nhuận' => ['phu nhuan'],
            'Tân Bình' => ['tan binh'],
            'Tân Phú' => ['tan phu'],
            'Bình Tân' => ['binh tan'],
            'Bình Chánh' => ['binh chanh'],
            'Nhà Bè' => ['nha be'],
            'Củ Chi' => ['cu chi'],
            'Hóc Môn' => ['hoc mon'],
            'Cần Giờ' => ['can gio'],
        ];
    }

    private function searchable(?string $value): string
    {
        return Str::lower(Str::ascii($value ?? ''));
    }
}
