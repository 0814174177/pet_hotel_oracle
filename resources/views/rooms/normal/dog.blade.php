@extends('layouts.client')

@section('title', 'Phong Thuong cho Cho')

@php
  $money = fn ($amount) => number_format((float) $amount, 0, ',', '.').'d';
  $images = $typeRoom['images'] ?? [];
  $mainImage = $images[0] ?? asset('assets/client/images/type-room/normal/dog/1.jpg');
  $firstAvailableRoom = collect($roomRows ?? [])->firstWhere('is_available', true);
  $bookingUrl = $firstAvailableRoom['booking_url'] ?? url('/booking');
  $availabilityText = ($availableCount ?? 0) > 0 ? 'Con phong' : 'Het phong';
  $availabilityClass = ($availableCount ?? 0) > 0 ? 'rd-status--available' : 'rd-status--busy';
  $description = $typeRoom['description'] ?? 'Dang cap nhat';
  $conditionLines = [
      'Ngay tra phong phai sau ngay nhan phong.',
      'Thu cung can co thong tin can nang phu hop voi loai phong.',
      'Phong chi duoc giu cho sau khi he thong tao booking thanh cong.',
  ];
@endphp

@section('content')

<div class="rd-page" data-rd-price="{{ $price ?? $typeRoom['price_raw'] ?? 0 }}" data-rd-booking-url="{{ $bookingUrl }}">
<section class="dog-hero" style="background-image: url('{{ $mainImage }}')">
  <div class="dog-hero-overlay"></div>

  <div class="dog-hero-content">
    <h1>Phong Thuong cho Cho</h1>
    <p>{{ $description }}</p>

    <a href="{{ $bookingUrl }}" class="dog-primary-btn">
      Dat phong ngay
    </a>
  </div>
</section>

<section class="dog-section dog-standards">
  <div class="dog-container">
    <h2 class="dog-section-title">Thong tin phong</h2>

    <div class="standard-grid">
      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-dog"></i>
        </div>
        <h3>Loai thu cung</h3>
        <p>Dog / Cho</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-door-open"></i>
        </div>
        <h3>Loai phong</h3>
        <p>Normal / Phong Thuong</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-paw"></i>
        </div>
        <h3>Suc chua toi da</h3>
        <p>{{ $typeRoom['capacity'] ?? (($maxPets ?? null) ? $maxPets.' be' : 'Dang cap nhat') }}</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-weight-scale"></i>
        </div>
        <h3>Can nang phu hop</h3>
        <p>{{ $typeRoom['weight'] ?? 'Dang cap nhat' }}</p>
      </div>
    </div>
  </div>
</section>

<section class="dog-section dog-rooms">
  <div class="dog-container">
    <h2 class="dog-section-title">Phong Thuong cho cho</h2>

    <div class="dog-room-card">
      <div class="dog-room-image">
        <img src="{{ $mainImage }}" alt="Phong Thuong cho cho">
      </div>

      <div class="dog-room-content">
        <div class="dog-room-header">
          <div>
            <h3>{{ $typeRoom['name'] ?? 'Phong Thuong cho cho' }}</h3>
            <p class="room-area">Tong phong: {{ $totalRooms ?? 0 }} | Con trong: {{ $availableCount ?? 0 }}</p>
          </div>

          <div class="room-price">
            <strong>{{ $money($price ?? $typeRoom['price_raw'] ?? 0) }}</strong>
            <span>/ngay</span>
          </div>
        </div>

        <div class="room-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Ten loai phong: {{ $typeRoom['label'] ?? 'Dang cap nhat' }}</li>
            <li><i class="fa-solid fa-check"></i> So phong con trong: {{ $availableCount ?? 0 }}</li>
            <li><i class="fa-solid fa-check"></i> Tong so phong: {{ $totalRooms ?? 0 }}</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Gia: {{ $typeRoom['price'] ?? $money($price ?? 0).'/ngay' }}</li>
            <li><i class="fa-solid fa-check"></i> Trang thai: <span class="rd-status {{ $availabilityClass }}">{{ $availabilityText }}</span></li>
            <li><i class="fa-solid fa-check"></i> Ma loai phong: {{ $typeRoom['id'] ?? 'Dang cap nhat' }}</li>
          </ul>
        </div>

        <div class="room-description">
          <h4>Mo ta phong</h4>
          <p>{{ $description }}</p>
        </div>

        <div class="room-health-note">
          <h4>Dieu kien dat phong</h4>
          <ul>
            @foreach ($conditionLines as $line)
              <li>{{ $line }}</li>
            @endforeach
          </ul>
        </div>

        <a href="{{ $bookingUrl }}" class="dog-room-btn">Dat phong</a>
      </div>
    </div>
  </div>
</section>

<section class="dog-section">
  <div class="dog-container">
    <h2 class="dog-section-title">Hinh anh phong</h2>

    <div class="rd-gallery">
      <div class="rd-gallery-main">
        <img class="rd-gallery-main__img" id="rdMainImage" src="{{ $mainImage }}" alt="Phong Thuong cho cho">
      </div>

      @if (! empty($images))
        <div class="rd-gallery-thumbs">
          @foreach ($images as $index => $image)
            <button class="rd-gallery-thumb {{ $index === 0 ? 'rd-gallery-thumb--active' : '' }}" type="button"
              onclick="rdSwitchImage('{{ $image }}', this)" aria-label="Xem anh {{ $index + 1 }}">
              <img src="{{ $image }}" alt="Phong Thuong cho cho {{ $index + 1 }}">
            </button>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

<section class="dog-section dog-daily-process">
  <div class="dog-container">
    <h2 class="dog-section-title">Dich vu di kem</h2>

    <div class="daily-process-grid">
      @forelse ($services as $service)
        <div class="daily-step">
          <span>{{ $loop->iteration }}</span>
          <h3>{{ $service->service_name }}</h3>
          <p>{{ $money($service->base_price) }}{{ $service->duration_minutes ? ' | '.$service->duration_minutes.' phut' : '' }}</p>
        </div>
      @empty
        <div class="daily-step">
          <span>!</span>
          <h3>Dang cap nhat</h3>
          <p>Chua co dich vu di kem cho loai phong nay.</p>
        </div>
      @endforelse
    </div>
  </div>
</section>

</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/client/js/type-room.js') }}"></script>
@endpush
