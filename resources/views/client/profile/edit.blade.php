@extends('layouts.client')

@section('title', 'Chỉnh sửa hồ sơ cá nhân')

@section('content')

@php
$birthday = $profile['birthday'] ?? null;
$birthdayValue = $birthday
? \Illuminate\Support\Carbon::parse($birthday)->format('Y-m-d')
: '';
$avatarUrl = $profile['avatar_url'] ?? null;
$profileDefaults = $profile['defaults'] ?? [];
@endphp

<section class="account-page profile-edit-page">
  <div class="account-container">

    <div class="account-header">
      <h1>Chỉnh sửa hồ sơ cá nhân</h1>

      <a href="{{ url('/profile') }}" class="light-btn">
        <i class="fa-solid fa-arrow-left" style="padding-right: 0.5rem"></i>
        Quay lại
      </a>
    </div>

    @if ($errors->any())
    <div class="profile-edit-alert profile-edit-alert--error">
      {{ $errors->first() }}
    </div>
    @endif

    @if (session('status'))
    <div class="profile-edit-alert profile-edit-alert--success">
      {{ session('status') }}
    </div>
    @endif

    <form action="{{ url('/profile') }}" method="POST" enctype="multipart/form-data"
      class="profile-card account-form-card" id="profile-form">
      @csrf

      <div class="avatar-upload-section">
        <div class="avatar-preview">
          @if ($avatarUrl)
          <img id="avatar-img" src="{{ $avatarUrl }}" alt="Avatar">
          @else
          <div id="avatar-placeholder" class="avatar-placeholder">{{ $profile['avatar_text'] }}</div>
          <img id="avatar-img" src="" alt="Avatar" hidden>
          @endif
        </div>

        <div class="avatar-actions">
          <label for="avatar" class="upload-btn">
            <i class="fa-solid fa-camera"></i>
            Thay đổi ảnh
          </label>
          <input type="file" id="avatar" name="avatar" accept="image/*" hidden>
          <p class="text-muted mt-2">Định dạng JPEG, PNG, JPG. Tối đa 2MB.</p>
        </div>
      </div>

      <hr class="section-divider">

      <h3 class="section-title">Thông tin cá nhân</h3>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="full_name">Họ và tên</label>
          <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $profile['full_name'] ?? '') }}"
            placeholder="{{ $profileDefaults['full_name'] ?? 'Nhập họ và tên' }}" required>
        </div>

        <div class="form-group">
          <label for="phone">Số điện thoại</label>
          <input type="text" id="phone" name="phone" value="{{ old('phone', $profile['phone'] ?? '') }}"
            placeholder="{{ $profileDefaults['phone'] ?? 'Nhập số điện thoại' }}" required>
        </div>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $profile['email'] ?? '') }}" required>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="birthday">Ngày sinh</label>
          <input type="date" id="birthday" name="birthday" value="{{ old('birthday', $birthdayValue) }}">
        </div>

        <div class="form-group">
          <label for="address">Địa chỉ</label>
          <input type="text" id="address" name="address" value="{{ old('address', $profile['address'] ?? '') }}"
            placeholder="{{ $profileDefaults['address'] ?? 'Nhập địa chỉ' }}">
        </div>
      </div>

      <hr class="section-divider">

      <div class="password-toggle-header">
        <h3 class="section-title">Thay đổi mật khẩu</h3>
        <button type="button" id="toggle-password-btn" class="outline-btn">
          <i class="fa-solid fa-chevron-down"></i>
          Thay đổi
        </button>
      </div>

      <div id="password-section" class="password-collapse">
        <div class="password-content">
          <div class="form-group">
            <label for="current_password">Mật khẩu hiện tại</label>
            <input type="password" id="current_password" name="current_password" class="pwd-input"
              placeholder="Nhập mật khẩu hiện tại">
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="new_password">Mật khẩu mới</label>
              <input type="password" id="new_password" name="new_password" class="pwd-input" placeholder="Mật khẩu mới">
            </div>

            <div class="form-group">
              <label for="new_password_confirmation">Xác nhận mật khẩu mới</label>
              <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="pwd-input"
                placeholder="Nhập lại mật khẩu mới">
            </div>
          </div>

          <p id="password-error" class="text-error" hidden></p>
        </div>
      </div>

      <div class="form-actions mt-4">
        <button type="submit" class="orange-btn">
          <i class="fa-regular fa-floppy-disk"></i>
          Lưu thay đổi
        </button>
      </div>
    </form>

  </div>
</section>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/client/css/profile-edit.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('assets/client/js/client/profile-edit.js') }}"></script>
@endpush
