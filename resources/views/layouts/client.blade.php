<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pet Hotel')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- CSS chính của client --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/style.css') }}">

    {{-- CSS trang hệ thống chi nhánh --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/branches.css') }}">

     {{-- CSS trang Booking --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/booking.css') }}">

     {{-- CSS trang hồ sơ, lịch sử thú cưng --}}    
    <link rel="stylesheet" href="{{ asset('assets/client/css/account.css') }}">

    {{-- CSS trang thanh toán, lịch sử thú cưng --}}    
    <link rel="stylesheet" href="{{ asset('assets/client/css/payment.css') }}">

    {{-- CSS trang khách sạn cho chó --}}   
    <link rel="stylesheet" href="{{ asset('assets/client/css/dog-hotel.css') }}">

    {{-- CSS trang khách sạn cho mèo --}}   
    <link rel="stylesheet" href="{{ asset('assets/client/css/cat-hotel.css') }}">

    {{-- CSS trang chi tiết loại phòng --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/type-room.css') }}">

    {{-- CSS trang chính sách --}} 
    <link rel="stylesheet" href="{{ asset('assets/client/css/policy.css') }}">

    {{-- CSS trang dịch vụ --}} 
    <link rel="stylesheet" href="{{ asset('assets/client/css/grooming.css') }}">

     {{-- CSS reponsive --}} 
    <link rel="stylesheet" href="{{ asset('assets/client/css/reponsive.css') }}">

    {{-- CSS navbar client --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/partials/navbar.css') }}">

    {{-- CSS footer client --}}
    <link rel="stylesheet" href="{{ asset('assets/client/css/partials/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/shared/css/fonts.css') }}">

    {{-- Font Awesome để dùng icon mũi tên dropdown --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @stack('styles')
</head>
<body>

    @include('partials.client.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.client.footer')

    <script src="{{ asset('assets/client/js/client/main.js') }}"></script>
    <script src="{{ asset('assets/client/js/hooks/ajax-hooks.js') }}"></script>
    <script src="{{ asset('assets/client/js/partials/navbar.js') }}"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            var navigation = performance.getEntriesByType('navigation')[0];

            if (event.persisted || (navigation && navigation.type === 'back_forward')) {
                window.location.reload();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
