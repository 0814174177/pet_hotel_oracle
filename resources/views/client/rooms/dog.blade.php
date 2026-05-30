@extends('layouts.client')

@section('title', 'Khách sạn cho Chó')

@php
  $randomPetHotelImage = function (string $folder, array $prefixes = [''], ?string $fallback = null): string {
    $publicRoot = public_path();
    $folder = trim($folder, '/');
    $directory = $publicRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $folder);
    $files = [];

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
      foreach (glob($directory.DIRECTORY_SEPARATOR.'*.'.$extension) ?: [] as $file) {
        $name = pathinfo($file, PATHINFO_FILENAME);

        foreach ($prefixes as $prefix) {
          $pattern = $prefix === ''
            ? '/^(0?[1-9]|10)$/i'
            : '/^'.preg_quote($prefix, '/').'(0?[1-9]|10)$/i';

          if (preg_match($pattern, $name)) {
            $files[] = $file;
            break;
          }
        }
      }
    }

    $files = array_values(array_unique(array_filter($files, 'is_file')));

    if ($files === []) {
      return asset($fallback ?: $folder.'/1.jpg');
    }

    $selected = $files[array_rand($files)];
    $relative = substr($selected, strlen($publicRoot));
    $relative = str_replace('\\', '/', ltrim($relative, '/\\'));

    return asset($relative);
  };

  $dogHeroImage = $randomPetHotelImage('assets/client/images/dog', ['dog'], 'assets/client/images/dog/dog01.jpg');
  $dogStandardRoomImage = $randomPetHotelImage('assets/client/images/type-room/normal/dog');
  $dogVipRoomImage = $randomPetHotelImage('assets/client/images/type-room/vip/dog');
  $dogLuxuryRoomImage = $randomPetHotelImage('assets/client/images/type-room/luxury/dog');
@endphp

@section('content')

{{-- Hero --}}
<section class="dog-hero" style="background-image: url('{{ $dogHeroImage }}')">
  <div class="dog-hero-overlay"></div>

  <div class="dog-hero-content">
    <h1>Khách sạn cho Chó</h1>
    <p>Không gian rộng rãi, hoạt động phong phú cho những người bạn trung thành</p>

    <a href="{{ url('/booking') }}" class="dog-primary-btn">
      Đặt phòng ngay
    </a>
  </div>
</section>

{{-- Care Standards --}}
<section class="dog-section dog-standards">
  <div class="dog-container">
    <h2 class="dog-section-title">Tiêu chuẩn chăm sóc</h2>

    <div class="standard-grid">
      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h3>An toàn tuyệt đối</h3>
        <p>Khu vực tách biệt, giám sát 24/7</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-regular fa-heart"></i>
        </div>
        <h3>Chăm sóc tận tâm</h3>
        <p>Nhân viên yêu chó, kinh nghiệm lâu năm</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-wave-square"></i>
        </div>
        <h3>Hoạt động phong phú</h3>
        <p>Chạy nhảy, vui chơi mỗi ngày</p>
      </div>

      <div class="standard-card">
        <div class="standard-icon">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <h3>Dinh dưỡng khoa học</h3>
        <p>Thực đơn cân đối cho từng giống chó</p>
      </div>
    </div>
  </div>
</section>

{{-- Rooms --}}
<section class="dog-section dog-rooms">
  <div class="dog-container">
    <h2 class="dog-section-title">Các loại phòng</h2>

    {{-- Room 1 --}}
    <div class="dog-room-card">
      <div class="dog-room-image">
        <img src="{{ $dogStandardRoomImage }}" alt="Phòng nhỏ">
      </div>

      <div class="dog-room-content">
        <div class="dog-room-header">
          <div>
            <h3>Phòng nhỏ</h3>
            <p class="room-area">Diện tích: khoảng 3m²</p>
          </div>

          <div class="room-price">
            <strong>150.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="room-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Điều hòa</li>
            <li><i class="fa-solid fa-check"></i> Đồ chơi cơ bản</li>
            <li><i class="fa-solid fa-check"></i> Bát ăn riêng</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Giường êm ái</li>
            <li><i class="fa-solid fa-check"></i> Theo dõi 24/7</li>
          </ul>
        </div>

        <div class="room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng nhỏ phù hợp với các bé chó nhỏ dưới 10kg, tối đa 2 bé/phòng.
            Không gian được vệ sinh thường xuyên, có giường nằm, bát ăn riêng
            và nhân viên theo dõi trong ngày.
          </p>
        </div>

        <div class="room-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Trước khi nhận phòng, thú cưng cần được kiểm tra tình trạng sức khỏe
            và xác nhận thông tin tiêm phòng. Nếu bé có dấu hiệu mệt, bỏ ăn hoặc
            có vấn đề bất thường, nhân viên sẽ thông báo ngay cho chủ nuôi để xử lý kịp thời.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'small', 'species' => 'dog']) }}" class="dog-room-btn">Xem chi tiết</a>
      </div>
    </div>

    {{-- Room 2 --}}
    <div class="dog-room-card reverse">
      <div class="dog-room-image">
        <img src="{{ $dogVipRoomImage }}" alt="Phòng vừa">
      </div>

      <div class="dog-room-content">
        <div class="dog-room-header">
          <div>
            <h3>Phòng vừa</h3>
            <p class="room-area">Diện tích: khoảng 5m²</p>
          </div>

          <div class="room-price">
            <strong>220.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="room-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Không gian rộng</li>
            <li><i class="fa-solid fa-check"></i> Bữa ăn đặc biệt</li>
            <li><i class="fa-solid fa-check"></i> Sân chơi riêng</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Đồ chơi cao cấp</li>
            <li><i class="fa-solid fa-check"></i> Tắm mỗi ngày</li>
          </ul>
        </div>

        <div class="room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng vừa dành cho các bé chó từ 10kg đến 25kg, cần không gian rộng hơn
            và lịch chăm sóc ổn định. Bé được bố trí khu vực riêng, đồ chơi phù hợp
            và chế độ ăn theo ghi chú của chủ nuôi.
          </p>
        </div>

        <div class="room-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Nhân viên sẽ theo dõi biểu hiện ăn uống, vận động và tinh thần của bé mỗi ngày.
            Trường hợp thú cưng có tiền sử bệnh, dị ứng hoặc cần uống thuốc,
            thông tin sẽ được ghi chú riêng để đảm bảo chăm sóc đúng cách.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'medium', 'species' => 'dog']) }}" class="dog-room-btn">Xem chi tiết</a>
      </div>
    </div>

    {{-- Room 3 --}}
    <div class="dog-room-card">
      <div class="dog-room-image">
        <img src="{{ $dogLuxuryRoomImage }}" alt="Phòng lớn">
      </div>

      <div class="dog-room-content">
        <div class="dog-room-header">
          <div>
            <h3>Phòng lớn</h3>
            <p class="room-area">Diện tích: khoảng 8m²</p>
          </div>

          <div class="room-price">
            <strong>350.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="room-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Không gian riêng biệt</li>
            <li><i class="fa-solid fa-check"></i> Thực đơn cao cấp</li>
            <li><i class="fa-solid fa-check"></i> Vườn chơi riêng</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Chăm sóc thư giãn hằng ngày</li>
            <li><i class="fa-solid fa-check"></i> Chăm sóc 1-1</li>
          </ul>
        </div>

        <div class="room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng lớn phù hợp với các bé chó từ 25kg đến 50kg hoặc các bé cần không gian riêng.
            Mỗi phòng nhận 1 bé để đảm bảo an toàn, thoải mái và dễ theo dõi sức khỏe.
          </p>
        </div>

        <div class="room-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Với gói sang trọng, thú cưng được theo dõi sức khỏe theo từng ca chăm sóc.
            Các thay đổi về ăn uống, vận động hoặc dấu hiệu bất thường sẽ được ghi nhận
            và báo lại cho chủ nuôi khi cần thiết.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'large', 'species' => 'dog']) }}" class="dog-room-btn">Xem chi tiết</a>
      </div>
    </div>
  </div>
</section>

{{-- Daily Care Process --}}
<section class="dog-section dog-daily-process">
  <div class="dog-container">
    <h2 class="dog-section-title">Quy trình chăm sóc hằng ngày</h2>

    <div class="daily-process-grid">
      <div class="daily-step">
        <span>1</span>
        <h3>Đón tiếp & nhận phòng</h3>
        <p>Khám sức khỏe ban đầu</p>
      </div>

      <div class="daily-step">
        <span>2</span>
        <h3>Sắp xếp phòng</h3>
        <p>Bố trí phòng phù hợp</p>
      </div>

      <div class="daily-step">
        <span>3</span>
        <h3>Chế độ ăn uống</h3>
        <p>Theo thời gian biểu cố định</p>
      </div>

      <div class="daily-step">
        <span>4</span>
        <h3>Vui chơi vận động</h3>
        <p>Hoạt động ngoài trời</p>
      </div>

      <div class="daily-step">
        <span>5</span>
        <h3>Chăm sóc y tế</h3>
        <p>Theo dõi sức khỏe hằng ngày</p>
      </div>
    </div>

    {{-- Bổ sung theo ghi chú ảnh cuối --}}
    <div class="care-note-box">
      <h3>Quy trình & Y tế được áp dụng trong suốt thời gian lưu trú</h3>

      <div class="care-note-content">
        <div class="care-note-item">
          <h4>Quy trình tiếp nhận</h4>
          <p>
            Khi thú cưng đến khách sạn, nhân viên sẽ kiểm tra sổ tiêm dại hoặc
            thông tin vaccine cần thiết, cân nặng, tình trạng sức khỏe ban đầu,
            sau đó ghi chú thói quen ăn uống, giờ sinh hoạt và các yêu cầu riêng
            trước khi ký nhận lưu trú.
          </p>
        </div>

        <div class="care-note-item">
          <h4>Y tế & xử lý tình huống</h4>
          <p>
            Nếu thú cưng có dấu hiệu bất thường như mệt mỏi, bỏ ăn, tiêu hóa kém
            hoặc gặp sự cố sức khỏe trong thời gian lưu trú, nhân viên sẽ tách bé
            sang khu vực theo dõi, liên hệ chủ nuôi và hỗ trợ đưa đến cơ sở thú y
            khi cần thiết.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
