<div class="booking-main">

  <div class="booking-branch-card-v2">
    <img src="{{ $branch['image'] }}" alt="{{ $branch['name'] }}">

    <div class="booking-branch-info">
      <h2>{{ $branch['name'] }}</h2>
      <p><i class="fa-solid fa-location-dot"></i> {{ $branch['address'] }}</p>
      <p><i class="fa-solid fa-phone"></i> {{ $branch['phone'] }}</p>
      <p><i class="fa-regular fa-clock"></i> {{ $branch['hours'] }}</p>
    </div>
  </div>

  <form action="{{ route('booking.store') }}" method="POST" id="bookingForm" class="booking-form-v2">
    @csrf
    <input type="hidden" name="branch_id" value="{{ $branch['id'] }}">
    <input type="hidden" name="room_type" id="bookingRoomType">
    <input type="hidden" name="checkin_expected_at" id="bookingCheckinValue">
    <input type="hidden" name="checkout_expected_at" id="bookingCheckoutValue">
    <input type="hidden" name="booking_action" id="bookingAction" value="pay">
    <div id="bookingHiddenFields"></div>

    <div class="booking-box-v2">
      <h3>
        <i class="fa-solid fa-briefcase"></i>
        Loại phòng
        <small>{{ count($roomTypes) }} lựa chọn phù hợp với nhu cầu lưu trú</small>
      </h3>

      <div class="booking-room-list-v2" id="bookingRoomTypeList">
        @foreach ($roomTypes as $roomType)
        <button type="button" class="booking-room-card-v2" aria-pressed="false"
          data-room-id="{{ $roomType['id'] }}"
          data-room-name="{{ $roomType['name'] }}" data-room-price="{{ $roomType['price'] }}"
          data-room-available-rooms="{{ $roomType['availableRoomsCount'] ?? 0 }}"
          data-room-min-weight="{{ $roomType['minWeight'] ?? '' }}"
          data-room-max-pets="{{ $roomType['maxPets'] }}" data-room-max-weight="{{ $roomType['maxWeight'] ?? '' }}">
          <span class="room-selected-badge">
            <i class="fa-solid fa-check"></i>
            Đang chọn
          </span>

          <span class="room-card-head">
            <span class="room-icon {{ $roomType['iconClass'] }}">
              <i class="fa-solid fa-briefcase"></i>
            </span>

            <span class="room-title-group">
              <strong>{{ $roomType['name'] }}</strong>
              <span class="room-price">{{ number_format($roomType['price'], 0, ',', '.') }}đ/đêm</span>
            </span>
          </span>

          <span class="room-detail">{{ $roomType['detail'] }}</span>

          <span class="room-meta-list">
            <span><i class="fa-solid fa-paw"></i> Tối đa {{ $roomType['maxPets'] }} bé</span>
            <span><i class="fa-solid fa-weight-scale"></i>
              @if (($roomType['minWeight'] ?? null) !== null && ($roomType['maxWeight'] ?? null) !== null)
                {{ $roomType['minWeight'] }} - {{ $roomType['maxWeight'] }}kg
              @elseif (($roomType['minWeight'] ?? null) !== null)
                Từ {{ $roomType['minWeight'] }}kg
              @elseif (($roomType['maxWeight'] ?? null) !== null)
                Đến {{ $roomType['maxWeight'] }}kg
              @else
                Không giới hạn kg
              @endif
            </span>
          </span>

          <span class="room-detail-link">
            Xem chi tiết
            <i class="fa-solid fa-arrow-right"></i>
          </span>
        </button>
        @endforeach
      </div>

      <p class="booking-availability-status" id="bookingAvailabilityStatus" hidden></p>

      <p class="booking-date-prerequisite" id="bookingDatePrerequisite">
        Vui lòng chọn loại phòng trước khi xem lịch trống.
      </p>
    </div>

    <div class="booking-box-v2 booking-step-panel" id="bookingDatePanel" hidden>

      <div class="booking-calendar-title">
        <strong>
          <i class="fa-solid fa-calendar-days"></i>
          Lịch trống - Phòng <span id="bookingCalendarRoom">đã chọn</span>
        </strong>
        <span>Chọn ngày nhận và ngày trả theo từng tháng</span>
      </div>

      <div class="booking-month-calendar">
        <div class="booking-calendar-nav">
          <button type="button" class="booking-month-btn" id="bookingPrevMonthBtn">
            <i class="fa-solid fa-chevron-left"></i>
            Tháng trước
          </button>

          <strong id="bookingCalendarMonthLabel">Tháng --/----</strong>

          <button type="button" class="booking-month-btn" id="bookingNextMonthBtn">
            Tháng sau
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>

        <div class="booking-weekdays" aria-hidden="true">
          <span>T2</span>
          <span>T3</span>
          <span>T4</span>
          <span>T5</span>
          <span>T6</span>
          <span>T7</span>
          <span>CN</span>
        </div>

        <div class="booking-calendar-grid" id="bookingCalendarGrid" aria-describedby="bookingCalendarLegend"></div>

        <div class="booking-calendar-legend" id="bookingCalendarLegend" aria-label="Chú thích màu lịch">
          <span><i class="legend-dot available"></i><strong>Xanh</strong> Còn trống, có thể chọn.</span>
          <span><i class="legend-dot selected"></i><strong>Cam</strong> Ngày đang được chọn.</span>
          <span><i class="legend-dot unavailable"></i><strong>Hồng</strong> Kín lịch, không thể chọn.</span>
          <span><i class="legend-dot past"></i><strong>Xám</strong> Đã qua hoặc không thể đặt.</span>
        </div>

      </div>

      <div class="booking-date-inputs">
        <div class="form-group">
          <label>Ngày nhận phòng <span>*</span></label>
          <input type="date" id="checkinDisplay">
        </div>

        <div class="form-group">
          <label>Ngày trả phòng <span>*</span></label>
          <input type="date" id="checkoutDisplay">
        </div>
      </div>

      <p class="booking-step-message" id="bookingDateMessage">Chọn ngày nhận và ngày trả phòng.</p>
    </div>

    <div class="booking-box-v2 booking-step-panel" id="bookingPetPanel" hidden>
      <header class="room-card-header">
        <h3>
          <i class="fa-regular fa-face-smile"></i>
          Chọn thú cưng
          <small>(chọn ít nhất 1 bé)</small>
        </h3>
        <span class="room-condition-message" data-room-message hidden></span>
      </header>

      <div class="pet-select-list" id="petSelectList">
        @foreach ($pets as $pet)
        @php($petInRoom = $pet['is_in_room'] ?? false)
        <div class="pet-item {{ $petInRoom ? 'ineligible pet-in-room' : '' }}" data-pet-id="{{ $pet['id'] }}"
          data-pet-name="{{ $pet['name'] }}" data-pet-species="{{ $pet['species'] }}"
          data-pet-breed="{{ $pet['breed'] }}" data-pet-sex="{{ $pet['sex'] }}" data-pet-weight="{{ $pet['weight'] ?? '' }}"
          data-pet-in-room="{{ $petInRoom ? '1' : '0' }}"
          data-pet-room-message="{{ $pet['room_status_message'] ?? 'Thú cưng này đang ở trong phòng khác.' }}">
          <label class="pet-item-select">
            <span class="pet-avatar">
              <i class="fa-regular fa-face-smile"></i>
            </span>

            <span class="pet-item-info">
              <strong>{{ $pet['name'] }}</strong>
              <span>{{ $pet['species'] }} · {{ $pet['breed'] }} · {{ $pet['sex'] }} · {{ $pet['weight'] !== null ? $pet['weight'].'kg' : 'Chưa cập nhật kg' }}</span>
            </span>

            <input type="checkbox" class="pet-checkbox" @disabled($petInRoom)>
            <span class="pet-check-ui"><i class="fa-solid fa-check"></i></span>
          </label>

          <div class="pet-item-actions">
            <p class="pet-status">
              {{ $petInRoom ? ($pet['room_status_message'] ?? 'Thú cưng này đang ở trong phòng khác.') : 'Chọn bé để kiểm tra điều kiện phòng.' }}
            </p>
            <button type="button" class="pet-service-btn" disabled>+ Thêm dịch vụ</button>
          </div>
        </div>
        @endforeach

        <button type="button" class="add-pet-btn-v2" id="addPetBtn"
          data-add-pet-url="{{ route('profile.pets.create') }}">
          <span>+</span>
          + Thêm thú cưng mới
        </button>
      </div>
    </div>

    <div class="booking-alert" id="bookingAlert" hidden></div>
  </form>
</div>
