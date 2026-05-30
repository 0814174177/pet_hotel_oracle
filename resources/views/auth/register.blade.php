@extends('layouts.auth')

@section('title', 'Đăng kí')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/auth/css/register.css') }}">
@endpush

@section('content')

<div class="auth-page">
  <div class="auth-container register-container">
    <div class="auth-image register-image">
      <img src="{{ asset('assets/auth/images/register/register01.jpg') }}" alt="Pet Hotel Register">
    </div>

    <div class="auth-form-wrapper">
      <form id="registerForm" action="{{ route('authentication.register.store') }}" method="POST"
        class="auth-form register-form">
        @csrf

        <h1>Tạo tài khoản mới</h1>

        <div class="form-group">
          <label for="name">Họ và tên</label>
          <input type="text" id="name" name="name" value="{{ old('name') }}">
          <span class="error-msg" id="error-name"></span>
        </div>

        <div class="form-group">
          <label for="phone">Số điện thoại</label>
          <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
          <span class="error-msg" id="error-phone"></span>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}">
          <span class="error-msg" id="error-email"></span>
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu</label>
          <div class="password-input">
            <input type="password" id="password" name="password">
            <button type="button" class="toggle-password" data-target="password">
              <i class="fa-regular fa-eye-slash"></i>
            </button>
          </div>
          <span class="error-msg" id="error-password"></span>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Nhập lại mật khẩu</label>
          <div class="password-input">
            <input type="password" id="password_confirmation" name="password_confirmation">
            <button type="button" class="toggle-password" data-target="password_confirmation">
              <i class="fa-regular fa-eye-slash"></i>
            </button>
          </div>
          <span class="error-msg" id="error-password_confirmation"></span>
        </div>

        <button type="submit" class="login-submit register-submit" id="submitBtn">Đăng kí</button>

        <div class="register-row login-row">
          <span>Đã có tài khoản?</span>
          <a href="{{ route('authentication.login') }}">Đăng nhập</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection


@push('scripts')
<script src="{{ asset('assets/auth/js/register.js') }}"></script>
@endpush
