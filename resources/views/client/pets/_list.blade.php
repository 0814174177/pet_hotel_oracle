@php
$speciesLabels = [
  'DOG' => 'Chó',
  'CAT' => 'Mèo',
];

$speciesIcons = [
  'DOG' => 'fa-solid fa-dog',
  'CAT' => 'fa-solid fa-cat',
];
@endphp

<div class="pet-grid">
  @forelse ($pets as $pet)
    @php
      $species = strtoupper((string) $pet->species);
      $speciesLabel = $speciesLabels[$species] ?? 'Thú cưng';
      $speciesIcon = $speciesIcons[$species] ?? 'fa-solid fa-paw';
      $rawImage = $pet->pet_image ?? null;
      $petImageUrl = null;

      if (filled($rawImage)) {
        $normalizedImage = ltrim((string) $rawImage, '/');

        if (str_starts_with($normalizedImage, 'http://') || str_starts_with($normalizedImage, 'https://')) {
          $petImageUrl = $normalizedImage;
        } elseif (str_starts_with($normalizedImage, 'storage/') || str_starts_with($normalizedImage, 'assets/')) {
          $petImageUrl = asset($normalizedImage);
        } else {
          $petImageUrl = asset('storage/'.$normalizedImage);
        }
      }
      $isInRoom = (bool) ($pet->is_in_room ?? false);
      $details = collect([
        $speciesLabel,
        $pet->breed,
        filled($pet->weight_kg) ? rtrim(rtrim(number_format((float) $pet->weight_kg, 2, ',', '.'), '0'), ',').' kg' : null,
        filled($pet->age) ? $pet->age.' tuổi' : null,
      ])->filter()->implode(' · ');
      $careNote = $pet->special_notes ?? null;
    @endphp

    <div class="pet-card">
      <div class="pet-card-top">
        <div class="pet-icon {{ $petImageUrl ? 'pet-icon--image' : '' }}">
          @if ($petImageUrl)
            <img src="{{ $petImageUrl }}" alt="{{ $pet->pet_name }}">
          @else
            <i class="{{ $speciesIcon }}"></i>
          @endif
        </div>

        <div class="pet-info">
          <h3>{{ $pet->pet_name }}</h3>
          <p>{{ $details ?: 'Chưa cập nhật thông tin' }}</p>
        </div>

        <span class="pet-room-state {{ $isInRoom ? 'pet-room-state--active' : 'pet-room-state--idle' }}">
          {{ $pet->room_status_label ?? ($isInRoom ? 'Đang ở trong phòng' : 'Không ở trong phòng') }}
        </span>
      </div>

      <span class="pet-status">{{ filled($careNote) ? $careNote : 'Chưa có ghi chú chăm sóc' }}</span>

      @if ($isInRoom)
        <p class="pet-care-warning">
          <i class="fa-solid fa-circle-exclamation"></i>
          {{ $pet->room_status_message }}
        </p>
      @endif

      <div class="pet-actions">
        <a href="{{ url('/profile/pets/'.$pet->pet_id.'/edit') }}" class="pet-edit-btn">
          <i class="fa-regular fa-pen-to-square"></i>
          Sửa
        </a>

        <a href="#" class="pet-delete-btn">
          <i class="fa-regular fa-trash-can"></i>
          Xóa
        </a>
      </div>
    </div>
  @empty
    <div class="pet-empty-card">
      <i class="fa-regular fa-face-smile"></i>
      <h3>Chưa có thú cưng</h3>
      <p>Thêm hồ sơ thú cưng đầu tiên để đặt phòng và dùng dịch vụ nhanh hơn.</p>
    </div>
  @endforelse

  <a href="{{ url('/profile/pets/create') }}" class="add-pet-card">
    <i class="fa-solid fa-plus"></i>
    <span>Thêm thú cưng</span>
  </a>
</div>
