@extends('layouts.client')

@section('title', 'Hệ thống chi nhánh')

@push('styles')
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')

<section class="branch-page">
  <div class="branch-shell">
    <div class="branch-list-panel">
      <div class="branch-top">
        <div>
          <span class="branch-eyebrow">Pet Hotel</span>
          <h1>Hệ thống chi nhánh</h1>
          <p>Tìm chi nhánh phù hợp theo khu vực, tên hoặc địa chỉ.</p>
        </div>
      </div>

      @include('client.branches.partials.branches-filter', [
        'districts' => $districts,
        'filters' => $filters,
      ])

      <div class="branch-feedback" data-branch-feedback hidden></div>

      @include('client.branches.partials.branches-detail', [
        'branches' => $branches,
      ])
    </div>

    @include('client.branches.partials.branches-map', [
      'branches' => $branches,
    ])
  </div>
</section>

@endsection

@push('scripts')
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="{{ asset('assets/client/js/branches.js') }}"></script>
@endpush
