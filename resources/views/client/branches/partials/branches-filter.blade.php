@php
$searchValue = $filters['search'] ?? '';
$selectedDistrict = $filters['district'] ?? 'all';
@endphp

<form
  class="branch-filter"
  method="GET"
  action="{{ route('branches.index') }}"
  data-branch-filter
  data-api-url="{{ route('branches.filter') }}"
>
  <div class="branch-search">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input
      type="text"
      name="keyword"
      value="{{ $searchValue }}"
      placeholder="Tìm theo tên, địa chỉ hoặc số điện thoại"
      autocomplete="off"
      data-branch-keyword
    >
  </div>

  <select name="district" aria-label="Lọc theo khu vực" data-branch-district>
    <option value="all" @selected($selectedDistrict === 'all')>Tất cả quận/huyện</option>
    @foreach ($districts as $district)
    <option value="{{ $district }}" @selected($selectedDistrict === $district)>
      {{ $district }}
    </option>
    @endforeach
  </select>

  <button type="button" class="branch-filter-btn" data-branch-reset>
    <i class="fa-solid fa-rotate-left"></i>
    Xóa lọc
  </button>
</form>
