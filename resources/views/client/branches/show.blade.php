@extends('layouts.client')

@section('title', 'Chi tiết chi nhánh')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/client/css/branch-show.css') }}">
@endpush

@section('content')

@php
    $branchName = $branch['name'] ?? 'Pet Hotel Quận 7';
    $branchAddress = $branch['address'] ?? '123 Đường Nguyễn Văn Linh, Quận 7, TP.HCM';
    $branchPhone = $branch['phone'] ?? '1900 1234';
    $branchHours = $branch['hours'] ?? '8:00 - 20:00';
    $branchRating = $branch['rating'] ?? '4.8';
    $branchReviews = $branch['reviews'] ?? 127;
    $branchBanner = $bannerImage ?? $branch['image'] ?? asset('assets/client/images/right-home-500x554.png');
    $bookingUrl = $branch['bookingUrl'] ?? url('/booking/branch/'.$branchId);
@endphp

<div class="branch-show-page">
    <section class="bh-hero" style="--branch-banner: url('{{ $branchBanner }}')">
        <div class="bh-hero-inner">
            <div class="bh-hero-left">
                <div class="bh-badge">
                    <i class="fa-solid fa-paw"></i>
                    Chi nhánh chính thức
                </div>

                <h1 class="bh-hero-title">{{ $branchName }}</h1>

                <div class="bh-stars">
                    @for ($i = 0; $i < 5; $i++)
                        <i class="fa-solid fa-star"></i>
                    @endfor
                    <span class="rating-num">{{ $branchRating }}</span>
                    <span class="rating-count">({{ $branchReviews }} đánh giá)</span>
                </div>
            </div>

            <a href="{{ $bookingUrl }}" class="bh-book-btn">
                <i class="fa-regular fa-calendar-check"></i>
                Đặt phòng ngay
            </a>
        </div>
    </section>

    <section class="bh-contact-strip">
        <div class="bh-contact-inner">
            <div class="bh-contact-item">
                <div class="bh-contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <div class="bh-contact-label">Địa chỉ</div>
                    <div class="bh-contact-value">{{ $branchAddress }}</div>
                </div>
            </div>

            <div class="bh-contact-item">
                <div class="bh-contact-icon"><i class="fa-solid fa-phone"></i></div>
                <div>
                    <div class="bh-contact-label">Số điện thoại</div>
                    <div class="bh-contact-value">{{ $branchPhone }}</div>
                </div>
            </div>

            <div class="bh-contact-item">
                <div class="bh-contact-icon"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <div class="bh-contact-label">Giờ mở cửa</div>
                    <div class="bh-contact-value">{{ $branchHours }}</div>
                </div>
            </div>
        </div>
    </section>

    <main class="bh-main">
        <div class="bh-section-header anim anim-d1">
            <div class="section-icon"><i class="fa-solid fa-bed"></i></div>
            <h2>Loại phòng</h2>
        </div>

        <div class="bh-rooms anim anim-d2">
            @foreach ($roomTypes as $roomType)
            <article class="bh-room-card {{ $loop->first ? 'active' : '' }}">
                <div class="room-img-wrap {{ $roomType['tone'] }}">
                    <i class="fa-solid {{ $roomType['icon'] }}"></i>
                </div>

                <div class="room-copy">
                    <div class="room-tier">{{ $roomType['name'] }}</div>
                    <p class="room-desc">{{ $roomType['description'] }}</p>

                    <div class="room-meta">
                        <span><i class="fa-solid fa-paw"></i> {{ $roomType['capacity'] }}</span>
                        <span><i class="fa-solid fa-weight-hanging"></i> {{ $roomType['weight'] }}</span>
                    </div>
                </div>

                <div class="room-price-center">
                    <span>Giá phòng</span>
                    <strong>{{ $roomType['price'] }}</strong>
                </div>

                <span class="room-badge">{{ $roomType['badge'] }}</span>

                <div class="room-actions">
                    <a href="{{ $bookingUrl }}" class="btn-book-room">Đặt phòng</a>
                    <a href="{{ $roomType['detailUrl'] }}" class="btn-view-detail">Xem chi tiết</a>
                </div>
            </article>
            @endforeach
        </div>

        <div class="bh-divider"></div>

        <div class="bh-section-header anim anim-d3">
            <div class="section-icon"><i class="fa-solid fa-spa"></i></div>
            <h2>Dịch vụ Spa</h2>
        </div>

        <div class="bh-spa-grid anim anim-d4">
            <div class="spa-row">
                <span class="spa-name"><i class="fa-solid fa-shower"></i> Tắm cơ bản</span>
                <span class="spa-price">100.000đ</span>
            </div>

            <div class="spa-row">
                <span class="spa-name"><i class="fa-solid fa-scissors"></i> Cắt tỉa lông</span>
                <span class="spa-price">150.000đ</span>
            </div>

            <div class="spa-row">
                <span class="spa-name"><i class="fa-solid fa-hand-sparkles"></i> Cắt móng</span>
                <span class="spa-price">50.000đ</span>
            </div>

            <div class="spa-row">
                <span class="spa-name"><i class="fa-solid fa-spa"></i> Massage thư giãn</span>
                <span class="spa-price">200.000đ</span>
            </div>
        </div>

        <a href="{{ route('branches.index') }}" class="bh-back">
            <i class="fa-solid fa-arrow-left"></i>
            Quay lại danh sách chi nhánh
        </a>
    </main>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/client/js/client/branch-show.js') }}"></script>
@endpush
