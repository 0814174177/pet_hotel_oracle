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

    <link rel="stylesheet" href="{{ asset('assets/auth/css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/shared/css/fonts.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @stack('styles')
    <link rel="stylesheet" href="{{ asset('assets/shared/css/no-icons.css') }}?v={{ time() }}">
</head>
<body class="app-no-icons">

    @yield('content')

    <script src="{{ asset('assets/client/js/hooks/ajax-hooks.js') }}"></script>
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
