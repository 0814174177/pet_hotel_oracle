@extends('layouts.client')

@section('title', 'Khách sạn cho Mèo')

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

  $catHeroImage = $randomPetHotelImage('assets/client/images/cat', ['cat', 'meo'], 'assets/client/images/cat/meo1.jpg');
  $catStandardRoomImage = $randomPetHotelImage('assets/client/images/type-room/normal/cat');
  $catVipRoomImage = $randomPetHotelImage('assets/client/images/type-room/vip/cat');
  $catLuxuryRoomImage = $randomPetHotelImage('assets/client/images/type-room/luxury/cat');
@endphp

@section('content')

<section class="cat-hero" style="background-image: url('{{ $catHeroImage }}')">
  <div class="cat-hero-overlay"></div>

  <div class="cat-hero-content">
    <h1>Khách sạn cho Mèo</h1>
    <p>Không gian yên tĩnh, sạch sẽ và an toàn cho những bé mèo đáng yêu</p>

    <a href="{{ url('/booking') }}" class="cat-primary-btn">
      Đặt phòng ngay
    </a>
  </div>
</section>

<section class="cat-section">
  <div class="cat-container">
    <h2 class="cat-section-title">Tiêu chuẩn chăm sóc</h2>

    <div class="cat-standard-grid">
      <div class="cat-standard-card">
        <div class="cat-standard-icon">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h3>An toàn tuyệt đối</h3>
        <p>Khu vực riêng biệt, hạn chế tiếng ồn và giám sát hằng ngày</p>
      </div>

      <div class="cat-standard-card">
        <div class="cat-standard-icon">
          <i class="fa-regular fa-heart"></i>
        </div>
        <h3>Chăm sóc nhẹ nhàng</h3>
        <p>Nhân viên hiểu tập tính mèo, chăm sóc cẩn thận và kiên nhẫn</p>
      </div>

      <div class="cat-standard-card">
        <div class="cat-standard-icon">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>
        <h3>Không gian sạch sẽ</h3>
        <p>Cát vệ sinh thay mới, khu vực nghỉ ngơi luôn được làm sạch</p>
      </div>

      <div class="cat-standard-card">
        <div class="cat-standard-icon">
          <i class="fa-solid fa-bowl-food"></i>
        </div>
        <h3>Dinh dưỡng phù hợp</h3>
        <p>Thực đơn theo thói quen ăn uống và tình trạng sức khỏe của mèo</p>
      </div>
    </div>
  </div>
</section>

<section class="cat-section cat-rooms">
  <div class="cat-container">
    <h2 class="cat-section-title">Các loại phòng</h2>

    <div class="cat-room-card">
      <div class="cat-room-image">
        <img src="{{ $catStandardRoomImage }}" alt="Phòng nhỏ cho mèo">
      </div>

      <div class="cat-room-content">
        <div class="cat-room-header">
          <div>
            <h3>Phòng nhỏ</h3>
            <p>Diện tích: khoảng 2m²</p>
          </div>

          <div class="cat-room-price">
            <strong>150.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="cat-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Điều hòa</li>
            <li><i class="fa-solid fa-check"></i> Khay cát riêng</li>
            <li><i class="fa-solid fa-check"></i> Bát ăn riêng</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Đệm nằm êm ái</li>
            <li><i class="fa-solid fa-check"></i> Theo dõi hằng ngày</li>
          </ul>
        </div>

        <div class="cat-room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng nhỏ phù hợp với mèo nhỏ dưới 10kg, tối đa 2 bé/phòng.
            Mỗi bé có khu vực nghỉ ngơi, khay cát và bát ăn riêng,
            được nhân viên theo dõi tình trạng ăn uống mỗi ngày.
          </p>
        </div>

        <div class="cat-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Trước khi nhận phòng, mèo sẽ được kiểm tra tình trạng sức khỏe cơ bản
            và ghi nhận thông tin vaccine nếu có. Nếu bé có dấu hiệu bỏ ăn, căng thẳng
            hoặc tiêu hóa bất thường, nhân viên sẽ báo ngay cho chủ nuôi.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'small', 'species' => 'cat']) }}" class="cat-room-btn">
          Xem chi tiết
        </a>
      </div>
    </div>

    <div class="cat-room-card reverse">
      <div class="cat-room-image">
        <img src="{{ $catVipRoomImage }}" alt="Phòng vừa cho mèo">
      </div>

      <div class="cat-room-content">
        <div class="cat-room-header">
          <div>
            <h3>Phòng vừa</h3>
            <p>Diện tích: khoảng 4m²</p>
          </div>

          <div class="cat-room-price">
            <strong>220.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="cat-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Không gian rộng</li>
            <li><i class="fa-solid fa-check"></i> Trụ cào móng</li>
            <li><i class="fa-solid fa-check"></i> Đồ chơi riêng</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Theo dõi bằng hình ảnh</li>
            <li><i class="fa-solid fa-check"></i> Chải lông nhẹ nhàng</li>
          </ul>
        </div>

        <div class="cat-room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng vừa dành cho mèo từ 10kg đến 25kg hoặc các bé cần không gian thoải mái hơn,
            có khu vực leo trèo, trụ cào móng và đồ chơi riêng. Nhân viên ghi chú
            thói quen sinh hoạt và khẩu phần ăn phù hợp với từng bé.
          </p>
        </div>

        <div class="cat-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Trong thời gian lưu trú, mèo được theo dõi mức độ ăn uống, đi vệ sinh,
            tinh thần và phản ứng với môi trường. Trường hợp bé nhút nhát hoặc dễ căng thẳng,
            nhân viên sẽ hạn chế tiếp xúc mạnh và tạo không gian yên tĩnh hơn.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'medium', 'species' => 'cat']) }}" class="cat-room-btn">
          Xem chi tiết
        </a>
      </div>
    </div>

    <div class="cat-room-card">
      <div class="cat-room-image">
        <img src="{{ $catLuxuryRoomImage }}" alt="Phòng lớn cho mèo">
      </div>

      <div class="cat-room-content">
        <div class="cat-room-header">
          <div>
            <h3>Phòng lớn</h3>
            <p>Diện tích: khoảng 6m²</p>
          </div>

          <div class="cat-room-price">
            <strong>350.000đ</strong>
            <span>/ngày</span>
          </div>
        </div>

        <div class="cat-feature-grid">
          <ul>
            <li><i class="fa-solid fa-check"></i> Không gian riêng biệt</li>
            <li><i class="fa-solid fa-check"></i> Khu leo trèo riêng</li>
            <li><i class="fa-solid fa-check"></i> Thực đơn cao cấp</li>
          </ul>

          <ul>
            <li><i class="fa-solid fa-check"></i> Chăm sóc 1-1</li>
            <li><i class="fa-solid fa-check"></i> Cập nhật hình ảnh hằng ngày</li>
          </ul>
        </div>

        <div class="cat-room-description">
          <h4>Mô tả chi tiết</h4>
          <p>
            Phòng lớn phù hợp với mèo lớn hoặc các bé cần không gian riêng tư.
            Mỗi phòng nhận 1 bé để đảm bảo yên tĩnh, dễ theo dõi và chăm sóc theo tính cách từng bé.
          </p>
        </div>

        <div class="cat-health-note">
          <h4>Y tế & chăm sóc</h4>
          <p>
            Với gói sang trọng, mèo được theo dõi kỹ về ăn uống, vệ sinh, mức độ vận động
            và biểu hiện tinh thần. Nếu phát hiện dấu hiệu bất thường, nhân viên sẽ ghi nhận,
            thông báo cho chủ nuôi và hỗ trợ liên hệ thú y khi cần.
          </p>
        </div>

        <a href="{{ route('rooms.by-type-species', ['type' => 'large', 'species' => 'cat']) }}" class="cat-room-btn">
          Xem chi tiết
        </a>
      </div>
    </div>
  </div>
</section>

<section class="cat-section">
  <div class="cat-container">
    <h2 class="cat-section-title">Quy trình chăm sóc hằng ngày</h2>

    <div class="cat-process-grid">
      <div class="cat-step">
        <span>1</span>
        <h3>Đón tiếp & nhận phòng</h3>
        <p>Kiểm tra sức khỏe ban đầu</p>
      </div>

      <div class="cat-step">
        <span>2</span>
        <h3>Sắp xếp phòng</h3>
        <p>Bố trí không gian phù hợp</p>
      </div>

      <div class="cat-step">
        <span>3</span>
        <h3>Chế độ ăn uống</h3>
        <p>Theo thói quen của từng bé</p>
      </div>

      <div class="cat-step">
        <span>4</span>
        <h3>Vệ sinh & vui chơi</h3>
        <p>Thay cát, chơi nhẹ nhàng</p>
      </div>

      <div class="cat-step">
        <span>5</span>
        <h3>Chăm sóc y tế</h3>
        <p>Theo dõi sức khỏe hằng ngày</p>
      </div>
    </div>

    <div class="cat-care-note-box">
      <h3>Quy trình & Y tế được áp dụng trong suốt thời gian lưu trú</h3>

      <div class="cat-care-note-content">
        <div class="cat-care-note-item">
          <h4>Quy trình tiếp nhận</h4>
          <p>
            Khi mèo đến khách sạn, nhân viên sẽ kiểm tra thông tin vaccine nếu có,
            tình trạng sức khỏe ban đầu, cân nặng, thói quen ăn uống, thói quen dùng cát,
            mức độ thân thiện và các yêu cầu riêng của chủ nuôi trước khi nhận lưu trú.
          </p>
        </div>

        <div class="cat-care-note-item">
          <h4>Y tế & xử lý tình huống</h4>
          <p>
            Nếu mèo có biểu hiện căng thẳng, bỏ ăn, nôn ói, tiêu hóa bất thường hoặc
            gặp sự cố sức khỏe, nhân viên sẽ tách bé sang khu vực yên tĩnh để theo dõi,
            liên hệ chủ nuôi và hỗ trợ đưa đến cơ sở thú y khi cần thiết.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
