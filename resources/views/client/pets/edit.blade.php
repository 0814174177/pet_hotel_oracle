@extends('layouts.client')

@section('title', 'Chỉnh sửa thú cưng')

@section('content')

@php
    $currentPetId = $pet->pet_id ?? $id ?? $petId ?? null;
@endphp

<section class="account-page pet-form-page">
  <div class="account-container">
    <div class="pet-form-header">
      <h1>Chỉnh sửa thú cưng</h1>

      <a href="{{ route('profile.pets.index') }}" class="pet-back-btn">
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
        'action' => route('profile.pets.update', $currentPetId),
        'pet' => $pet,
        'submitLabel' => 'Lưu thay đổi',
        'submitIcon' => 'fa-regular fa-floppy-disk',
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
