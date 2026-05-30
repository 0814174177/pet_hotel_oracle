@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')

@section('content')

<div class="auth-page">
  <div class="auth-container reset-container">

    <div class="auth-image reset-image">
      <img src="{{ asset('assets/auth/images/reset/reset01.jpg') }}" alt="Pet Hotel Reset Password">
    </div>

    <div class="auth-form-wrapper">
      <form action="{{ route('authentication.reset-password.store') }}" method="POST" class="auth-form reset-form">
        @csrf

        <input type="hidden" name="token" value="{{ $token ?? '' }}">

        <h1>Đặt lại mật khẩu</h1>

        <p class="auth-desc">
          Vui lòng nhập mật khẩu mới cho tài khoản của bạn.
        </p>

        @if ($errors->any())
          <div class="auth-alert">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="{{ old('email', $email ?? '') }}" required>
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu mới</label>

          <div class="password-input">
            <input type="password" id="password" name="password" required>

            <button type="button" class="toggle-password" data-target="password">
              <i class="fa-regular fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Nhập lại mật khẩu</label>

          <div class="password-input">
            <input type="password" id="password_confirmation" name="password_confirmation" required>

            <button type="button" class="toggle-password" data-target="password_confirmation">
              <i class="fa-regular fa-eye-slash"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="login-submit reset-submit">
          Xác nhận
        </button>

        <div class="back-login-row">
          <span>Quay lại trang</span>
          <a href="{{ route('authentication.login') }}">Đăng nhập</a>
        </div>

      </form>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
const toggleButtons = document.querySelectorAll('.toggle-password');

toggleButtons.forEach(function(button) {
  button.addEventListener('click', function() {
    const targetId = this.getAttribute('data-target');
    const input = document.getElementById(targetId);
    const icon = this.querySelector('i');

    const isPassword = input.type === 'password';

    input.type = isPassword ? 'text' : 'password';

    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
  });
});
</script>
@endpush
