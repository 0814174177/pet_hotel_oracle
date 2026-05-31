@extends('layouts.client')

@section('title', 'Thêm thú cưng')

@section('content')

<section class="account-page pet-form-page">
  <div class="account-container">

    <div class="pet-form-header">
      <h1>Thêm thú cưng</h1>

      <a href="{{ url('/profile/pets') }}" class="pet-back-btn">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
      </a>
    </div>

    @if ($errors->any())
      <div class="pet-form-alert pet-form-alert--error">
        {{ $errors->first() }}
      </div>
    @endif

    @include('client.pets._form', [
        'action' => url('/profile/pets'),
        'submitLabel' => 'Thêm thú cưng',
        'submitIcon' => 'fa-solid fa-plus',
    ])

  </div>
</section>

@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/client/css/pet-form.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('assets/client/js/client/pet-form.js') }}"></script>
@endpush
