{{-- Title, description, robots and link-preview tags of a test page. Needs $seo = TestSeo::resolve(...). --}}
<meta name="robots" content="{{ $seo['index'] ? 'index, follow' : 'noindex' }}">
<title>{{ $seo['title'] }}</title>
@if ($seo['description'])
    <meta name="description" content="{{ $seo['description'] }}">
@endif
@if ($seo['index'])
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seo['title'] }}">
    @if ($seo['description'])<meta property="og:description" content="{{ $seo['description'] }}">@endif
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:image" content="{{ asset('images/og-default.png') }}">
    <meta name="twitter:card" content="summary_large_image">
@endif
