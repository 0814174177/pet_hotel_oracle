 @extends('layouts.client')

 @section('title', 'Trang chủ')

 @section('content')

 @php
 $randomRoomImage = fn(string $folder, string $species) => asset('assets/client/images/type-room/' . $folder . '/' .
 $species . '/' . random_int(1, 2) . '.jpg');


 $randomHomeImage = fn() => asset('assets/client/images/logo&banner/'.random_int(1, 4).'.jpg');

 $homeHeroImage = $randomHomeImage();
 $homeAboutImage = asset('assets/client/images/about/1.jpg');
 $homeNormalRoomImage = $randomRoomImage('normal', 'dog');
 $homeVipRoomImage = $randomRoomImage('vip', 'cat');
 $homeLuxuryRoomImage = $randomRoomImage('luxury', 'dog');
 @endphp

 @if (request()->boolean('registered'))
 <div class="home-success-modal" id="homeSuccessModal" role="dialog" aria-modal="true" aria-labelledby="homeSuccessTitle">
   <div class="home-success-card">
     <button type="button" class="home-success-close" aria-label="Đóng thông báo" data-close-register-modal>
       <i class="fa-solid fa-xmark"></i>
     </button>
     <span class="home-success-icon">
       <i class="fa-solid fa-check"></i>
     </span>
     <h2 id="homeSuccessTitle">Đăng ký thành công</h2>
     <p>Tài khoản của bạn đã sẵn sàng. Bạn có thể bắt đầu chọn chi nhánh và đặt phòng cho thú cưng.</p>
     <a href="{{ route('booking.select') }}">Đặt phòng ngay</a>
   </div>
 </div>
 @endif

 {{-- Hero section --}}
 <section class="home-hero" style="background-image: url('{{ $homeHeroImage }}')">
   <div class="hero-overlay"></div>

   <div class="hero-content">
     <h1>
       Chăm sóc thú cưng như<br>
       gia đình
     </h1>

     <a href="{{ url('/booking') }}" class="hero-btn">
       Đặt phòng ngay
     </a>
   </div>
 </section>

 {{-- About section --}}
 <section class="home-about">
   <div class="container">
     <div class="about-wrapper">
       <div class="about-image">
         <img src="{{ $homeAboutImage }}" alt="Về PetStay Hotel">
       </div>

       <div class="about-content">
         <h2>Về PetStay Hotel</h2>

         <p>
           Chúng tôi cung cấp dịch vụ khách sạn thú cưng cao cấp với đội ngũ
           chuyên nghiệp, yêu thương động vật. Mỗi bé cưng đều được chăm sóc
           tận tâm như thành viên trong gia đình.
         </p>

         <p>
           Với cơ sở vật chất hiện đại, không gian rộng rãi và các tiêu chuẩn
           vệ sinh nghiêm ngặt, chúng tôi cam kết mang đến trải nghiệm tốt
           nhất cho thú cưng của bạn.
         </p>
       </div>
     </div>
   </div>
 </section>

 {{-- Process section --}}
 <section class="home-process">
   <div class="container">
     <div class="section-heading">
       <h2>Quy trình nhận thú cưng</h2>
       <p>5 bước đơn giản để đảm bảo an toàn cho bé cưng</p>
     </div>

     <div class="process-list">
       <div class="process-item">
         <div class="process-icon">
           <i class="fa-solid fa-check"></i>
         </div>
         <div class="process-number">1</div>
         <h3>Kiểm tra vaccine</h3>
       </div>

       <div class="process-item">
         <div class="process-icon">
           <i class="fa-regular fa-heart"></i>
         </div>
         <div class="process-number">2</div>
         <h3>Kiểm tra sức khỏe</h3>
       </div>

       <div class="process-item">
         <div class="process-icon">
           <i class="fa-regular fa-clock"></i>
         </div>
         <div class="process-number">3</div>
         <h3>Ghi chú ăn uống</h3>
       </div>

       <div class="process-item">
         <div class="process-icon">
           <i class="fa-solid fa-shield-halved"></i>
         </div>
         <div class="process-number">4</div>
         <h3>Ghi chú đưa đón</h3>
       </div>

       <div class="process-item">
         <div class="process-icon">
           <i class="fa-regular fa-image"></i>
         </div>
         <div class="process-number">5</div>
         <h3>Nhận lưu giữ</h3>
       </div>
     </div>
   </div>
 </section>

 {{-- Quality section --}}
 <section class="home-quality">
   <div class="container">
     <div class="section-heading">
       <h2>Tiêu chuẩn chất lượng</h2>
       <p>Cam kết mang đến môi trường tốt nhất cho thú cưng</p>
     </div>

     <div class="quality-list">
       <div class="quality-card">
         <div class="quality-icon">
           <i class="fa-solid fa-shield-halved"></i>
         </div>
         <h3>Tiệt trùng</h3>
         <p>Vệ sinh tuyệt đối</p>
       </div>

       <div class="quality-card">
         <div class="quality-icon">
           <i class="fa-solid fa-heart-pulse"></i>
         </div>
         <h3>Điều hòa</h3>
         <p>Nhiệt độ phù hợp</p>
       </div>

       <div class="quality-card">
         <div class="quality-icon">
           <i class="fa-regular fa-face-smile"></i>
         </div>
         <h3>Không gian vui chơi</h3>
         <p>Rộng rãi, thoáng mát</p>
       </div>

       <div class="quality-card">
         <div class="quality-icon">
           <i class="fa-solid fa-check"></i>
         </div>
         <h3>Cát vệ sinh</h3>
         <p>Thay mới hằng ngày</p>
       </div>
     </div>
   </div>
 </section>

 {{-- Room section --}}
 <section class="home-rooms">
   <div class="container">
     <div class="section-heading">
       <h2>Loại phòng</h2>
       <p>Lựa chọn phù hợp với nhu cầu của bé cưng</p>
     </div>

     <div class="room-list">
       <div class="room-card">
         <img src="{{ $homeNormalRoomImage }}" alt="Phòng nhỏ">

         <div class="room-content">
           <h3>Phòng nhỏ</h3>
           <p>Phù hợp cho chó/mèo nhỏ dưới 10kg, tối đa 2 bé.</p>

           <a href="{{ route('type-room.show', 1) }}" class="room-btn">
             Xem chi tiết
           </a>
         </div>
       </div>

       <div class="room-card">
         <img src="{{ $homeVipRoomImage }}" alt="Phòng vừa">

         <div class="room-content">
           <h3>Phòng vừa</h3>
           <p>Phù hợp thú cưng từ 10kg đến 25kg, không gian thoải mái hơn.</p>

           <a href="{{ route('type-room.show', 2) }}" class="room-btn">
             Xem chi tiết
           </a>
         </div>
       </div>

       <div class="room-card">
         <img src="{{ $homeLuxuryRoomImage }}" alt="Phòng lớn">

         <div class="room-content">
           <h3>Phòng lớn</h3>
           <p>Phòng rộng cho thú cưng lớn, mỗi phòng 1 bé.</p>

           <a href="{{ route('type-room.show', 3) }}" class="room-btn">
             Xem chi tiết
           </a>
         </div>
       </div>
     </div>
   </div>
 </section>

 @endsection

 @push('scripts')
 <script>
   document.addEventListener('DOMContentLoaded', function () {
     const modal = document.getElementById('homeSuccessModal');

     if (!modal) {
       return;
     }

     const closeModal = function () {
       modal.hidden = true;
     };

     modal.addEventListener('click', function (event) {
       if (event.target === modal) {
         closeModal();
       }
     });

     document.querySelectorAll('[data-close-register-modal]').forEach(function (button) {
       button.addEventListener('click', closeModal);
     });
   });
 </script>
 @endpush
