@extends('layouts.client')

@section('title', 'Dịch vụ Grooming')

@section('content')

@php
$randomGroomingImage = fn (): string => asset('assets/client/images/service/grooming/'.random_int(1, 12).'.jpg');

$groomingHeroImage = $randomGroomingImage();

@endphp

<section class="grooming-hero" style="background-image: url('{{ $groomingHeroImage }}')">
  <div class="grooming-hero-overlay"></div>

  <div class="grooming-hero-content">
    <h1>Dịch vụ Grooming</h1>
    <p>Chăm sóc, làm đẹp và vệ sinh toàn diện cho thú cưng của bạn</p>

    <a href="{{ url('/booking') }}" class="grooming-primary-btn">
      Đặt lịch ngay
    </a>
  </div>
</section>

<section class="grooming-section">
  <div class="grooming-container">
    <h2 class="grooming-section-title">Dịch vụ nổi bật</h2>

    <div class="grooming-service-grid">

      <div class="grooming-service-card">
        <div class="grooming-service-icon">
          <i class="fa-solid fa-shower"></i>
        </div>
        <h3>Tắm thú cưng</h3>
        <p>Thời gian 60 phút, chỉ áp dụng cho chó.</p>
        <strong>100.000đ</strong>
      </div>

      <div class="grooming-service-card">
        <div class="grooming-service-icon">
          <i class="fa-solid fa-scissors"></i>
        </div>
        <h3>Grooming toàn diện</h3>
        <p>Thời gian 120 phút, chỉ áp dụng cho chó.</p>
        <strong>250.000đ</strong>
      </div>

      <div class="grooming-service-card">
        <div class="grooming-service-icon">
          <i class="fa-regular fa-hand"></i>
        </div>
        <h3>Kiểm tra sức khỏe cơ bản</h3>
        <p>Thời gian 30 phút, áp dụng cho tất cả các loài.</p>
        <strong>80.000đ</strong>
      </div>

      <div class="grooming-service-card">
        <div class="grooming-service-icon">
          <i class="fa-solid fa-tooth"></i>
        </div>
        <h3>Cắt móng</h3>
        <p>Thời gian 25 phút, áp dụng cho tất cả các loài.</p>
        <strong>50.000đ</strong>
      </div>

    </div>
  </div>
</section>

<section class="grooming-section">
  <div class="grooming-container">
    <h2 class="grooming-section-title">Quy trình Grooming</h2>

    <div class="grooming-process-grid">
      <div class="grooming-step">
        <span>1</span>
        <h3>Tiếp nhận thú cưng</h3>
        <p>Kiểm tra tình trạng da, lông và ghi chú yêu cầu của chủ nuôi.</p>
      </div>

      <div class="grooming-step">
        <span>2</span>
        <h3>Tắm & làm sạch</h3>
        <p>Sử dụng sản phẩm phù hợp với từng loại lông và làn da.</p>
      </div>

      <div class="grooming-step">
        <span>3</span>
        <h3>Sấy & chải lông</h3>
        <p>Làm khô kỹ, chải gỡ rối và giữ lông mềm mượt.</p>
      </div>

      <div class="grooming-step">
        <span>4</span>
        <h3>Cắt tỉa</h3>
        <p>Tạo kiểu gọn gàng theo nhu cầu và đặc điểm từng bé.</p>
      </div>

      <div class="grooming-step">
        <span>5</span>
        <h3>Bàn giao</h3>
        <p>Kiểm tra lại, ghi chú tình trạng và bàn giao thú cưng cho khách.</p>
      </div>
    </div>

    <div class="grooming-care-box">
      <h3>Ghi chú an toàn khi Grooming</h3>

      <div class="grooming-care-grid">
        <div class="grooming-care-item">
          <h4>Kiểm tra trước dịch vụ</h4>
          <p>
            Trước khi thực hiện grooming, nhân viên sẽ kiểm tra sơ bộ da, lông,
            móng và biểu hiện của thú cưng. Nếu phát hiện vết thương, kích ứng
            hoặc biểu hiện bất thường, nhân viên sẽ thông báo cho chủ nuôi trước khi xử lý.
          </p>
        </div>

        <div class="grooming-care-item">
          <h4>Xử lý khi thú cưng căng thẳng</h4>
          <p>
            Nếu thú cưng quá sợ hãi, phản ứng mạnh hoặc không hợp tác,
            quy trình có thể được tạm dừng để bé nghỉ ngơi. Pet Hotel ưu tiên
            sự an toàn và cảm giác thoải mái của thú cưng trong suốt quá trình chăm sóc.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

@endsection
