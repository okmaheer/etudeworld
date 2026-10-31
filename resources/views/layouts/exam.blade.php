<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @hasSection('seo')
        @yield('seo')
    @else
        <meta name="robots" content="noindex">
        <title>@yield('title') – {{ config('app.name') }}</title>
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ \App\Models\SiteSetting::logoUrl() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=33">
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    @stack('head')
</head>
<body class="exam-body">
    @yield('content')
    <script src="{{ asset('js/results-url.js') }}?v=3"></script>
    <script src="{{ asset('js/vocab-card.js') }}?v=1"></script>
    @stack('scripts')
</body>
</html>
