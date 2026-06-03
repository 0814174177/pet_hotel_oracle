@php
  $method = $method ?? 'POST';
  $pet = $pet ?? null;
  $selectedSpecies = old('species', $pet->species ?? '');
  $selectedGender = old('gender', $pet->sex ?? 'UNKNOWN');
  $rawImage = $pet ? ($pet->pet_image ?? null) : null;
  $petImageUrl = null;

  if (filled($rawImage)) {
    $normalizedImage = ltrim((string) $rawImage, '/');

    if (str_starts_with($normalizedImage, 'http://') || str_starts_with($normalizedImage, 'https://')) {
      $petImageUrl = $normalizedImage;
    } elseif (str_starts_with($normalizedImage, 'storage/') || str_starts_with($normalizedImage, 'assets/')) {
      $petImageUrl = asset($normalizedImage);
    } else {
      $petImageUrl = asset('storage/'.$normalizedImage);
    }
  }
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="pet-form-card" id="pet-form">
  @csrf

  @if (strtoupper($method) !== 'POST')
  @method($method)
  @endif

  <div class="pet-avatar-section">
    <div class="pet-avatar-preview {{ $petImageUrl ? 'has-image' : '' }}" id="pet-avatar-container">
      <i class="fa-solid fa-paw" id="default-icon"></i>
      <img id="pet-img" src="{{ $petImageUrl ?: '' }}" alt="Ảnh thú cưng">
    </div>

    <label for="pet_image" class="pet-upload-btn">
      <i class="fa-solid fa-camera"></i>
      {{ $pet ? 'Thay đổi ảnh' : 'Tải ảnh lên' }}
    </label>

    <input type="file" id="pet_image" name="pet_image" accept=".jpeg, .png, .jpg" hidden>
    <span class="error-msg" id="error-pet_image">@error('pet_image'){{ $message }}@enderror</span>
  </div>

  <div class="pet-form-grid-2">
    <div class="pet-form-group">
      <label for="pet_name">Tên thú cưng <span class="required">*</span></label>
      <input type="text" id="pet_name" name="pet_name" class="pet-form-control"
        value="{{ old('pet_name', $pet->pet_name ?? '') }}" placeholder="Ví dụ: Mochi" required>
      <span class="error-msg" id="error-pet_name">@error('pet_name'){{ $message }}@enderror</span>
    </div>

    <div class="pet-form-group">
      <label for="species">Loài <span class="required">*</span></label>
      <select id="species" name="species" class="pet-form-control" required>
        <option value="" disabled @selected($selectedSpecies==='' )>Chọn loài</option>
        <option value="CAT" @selected($selectedSpecies==='CAT' )>Mèo</option>
        <option value="DOG" @selected($selectedSpecies==='DOG' )>Chó</option>
      </select>
      <span class="error-msg" id="error-species">@error('species'){{ $message }}@enderror</span>
    </div>

    <div class="pet-form-group">
      <label for="gender">Giới tính</label>
      <select id="gender" name="gender" class="pet-form-control">
        <option value="MALE" @selected($selectedGender==='MALE' )>Đực</option>
        <option value="FEMALE" @selected($selectedGender==='FEMALE' )>Cái</option>
        <option value="UNKNOWN" @selected($selectedGender==='UNKNOWN' )>Chưa rõ</option>
      </select>
      <span class="error-msg" id="error-gender">@error('gender'){{ $message }}@enderror</span>
    </div>

    <div class="pet-form-group">
      <label for="weight_kg">Cân nặng</label>
      <div class="input-with-suffix">
        <input type="number" id="weight_kg" name="weight_kg" class="pet-form-control"
          value="{{ old('weight_kg', $pet->weight_kg ?? '') }}" step="0.1" min="0.1" placeholder="3.5">
        <span class="suffix">kg</span>
      </div>
      <span class="error-msg" id="error-weight_kg">@error('weight_kg'){{ $message }}@enderror</span>
    </div>
  </div>

  <div class="pet-form-group">
    <label for="breed">Giống</label>
    <input type="text" id="breed" name="breed" class="pet-form-control" value="{{ old('breed', $pet->breed ?? '') }}"
      placeholder="Ví dụ: Poodle, Anh lông ngắn...">
    <span class="error-msg" id="error-breed">@error('breed'){{ $message }}@enderror</span>
  </div>

  <div class="pet-form-group">
    <label for="special_notes">Ghi chú</label>
    <textarea id="special_notes" name="special_notes" class="pet-form-control" rows="4"
      placeholder="Tính cách, thói quen, lưu ý chăm sóc...">{{ old('special_notes', $pet->special_notes ?? '') }}</textarea>
    <span class="error-msg" id="error-special_notes">@error('special_notes'){{ $message }}@enderror</span>
  </div>

  <div class="pet-form-actions">
    <button type="submit" class="pet-primary-btn" id="submitBtn">
      <i class="{{ $submitIcon ?? 'fa-solid fa-plus' }}"></i>
      {{ $submitLabel ?? 'Lưu thú cưng' }}
    </button>
  </div>
</form>
