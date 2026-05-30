@extends('layouts.auth')

@section('title', 'Đăng nhập')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/auth/css/login.css') }}">
@endpush

@section('content')
<div class="auth-page">
  <div class="auth-container">

    <div class="auth-image">
      <img src="{{ asset('assets/auth/images/login/login01.jpg') }}" alt="Pet Hotel Login">
    </div>

    <div class="auth-form-wrapper">
      <form id="loginForm" action="{{ route('authentication.login.store') }}" method="POST" class="auth-form">
        @csrf

        <h1>Chào mừng trở lại</h1>

        @if (session('status'))
          <div class="auth-alert auth-alert--success">
            {{ session('status') }}
          </div>
        @endif

        <div id="general-error" class="auth-alert"
          style="display: none; color: red; margin-bottom: 15px; font-size: 14px;"></div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}">
          <span class="error-msg" id="error-email"></span>
        </div>

        <div class="form-group password-group">
          <label for="password">Mật khẩu</label>
          <div class="password-input">
            <input type="password" id="password" name="password">
            <button type="button" class="toggle-password">
              <i class="fa-regular fa-eye-slash"></i>
            </button>
          </div>
          <span class="error-msg" id="error-password"></span>
        </div>

        <div class="form-options">
          <label class="remember-box">
            <span>Ghi nhớ đăng nhập</span>
            <input type="checkbox" name="remember">
          </label>
          <a href="{{ route('authentication.forgot-password') }}">Quên mật khẩu</a>
        </div>

        <button type="submit" id="submitBtn" class="login-submit">
          Đăng nhập
        </button>

        <div class="register-row">
          <span>Chưa có tài khoản?</span>
          <a href="{{ route('authentication.register') }}">Đăng kí</a>
        </div>

      </form>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/auth/js/login.js') }}"></script>
@endpush
