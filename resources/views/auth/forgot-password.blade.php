@extends('layouts.auth')

@section('title', 'Quên mật khẩu')

@section('content')

<div class="auth-page">
  <div class="auth-container forgot-container">

    <div class="auth-image forgot-image">
      <img src="{{ asset('assets/auth/images/forgot/forgot01.jpg') }}" alt="Pet Hotel Forgot Password">
    </div>

    <div class="auth-form-wrapper">
      <form action="{{ route('authentication.forgot-password.store') }}" method="POST" class="auth-form forgot-form">
        @csrf

        <h1>Quên mật khẩu</h1>

        <p class="auth-desc">
          Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.
        </p>

        @if (session('status'))
          <div class="auth-alert auth-alert--success">
            {{ session('status') }}
          </div>
        @endif

        @if ($errors->any())
          <div class="auth-alert">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>

        <button type="submit" class="login-submit forgot-submit">
          Gửi yêu cầu
        </button>

        <div class="back-login-row">
          <span>Đã nhớ mật khẩu?</span>
          <a href="{{ route('authentication.login') }}">Đăng nhập</a>
        </div>

      </form>
    </div>

  </div>
</div>

@endsection
