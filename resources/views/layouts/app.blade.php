<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $siteSeo = \App\Support\SitePages::override(request()->path()); @endphp
    <title>{{ $siteSeo?->meta_title }}@unless ($siteSeo?->meta_title)@yield('title', config('app.name') . ' – Learn Languages & Practise IELTS and CELPIP')@endunless</title>
    <meta name="description" content="{{ $siteSeo?->meta_description }}@unless ($siteSeo?->meta_description)@yield('description', 'Free resources, proven study methods and practice tests for IELTS, CELPIP and learning a new language.')@endunless">

    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ \App\Models\SiteSetting::logoUrl() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=55">
    @php
        // Link-preview (WhatsApp, Facebook, LinkedIn, X) and canonical tags. A page that sets its own (e.g. a blog) keeps them.
        $headTags  = $__env->yieldPushContent('head');
        $pageTitle = $siteSeo?->meta_title ?: html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES) ?: config('app.name');
        $pageDesc  = $siteSeo?->meta_description ?: html_entity_decode(trim($__env->yieldContent('description')), ENT_QUOTES) ?: 'Free resources, proven study methods and practice tests for IELTS, CELPIP and learning a new language.';
        $pageUrl   = request()->url();
        $noindex   = str_contains($headTags, 'noindex');
        $ownOg     = str_contains($headTags, 'og:title');
    @endphp
    {!! $headTags !!}
    @php
        // Text written in the admin editor (FAQ blocks, buttons, files): load the block styles and add FAQ search-engine data by itself.
        $bodyHtml = \App\Support\SitePages::decorate($__env->yieldContent('content'), $siteSeo);
        $hasBlocks = str_contains($bodyHtml, 'ew-faq') || str_contains($bodyHtml, 'ew-btn') || str_contains($bodyHtml, 'ew-file');
    @endphp
    @if ($hasBlocks && ! str_contains($headTags, 'content-blocks.css'))
        <link rel="stylesheet" href="{{ asset('css/content-blocks.css') }}?v=3">
    @endif
    @unless (str_contains($headTags, 'FAQPage'))
        {!! \App\Support\Faq::jsonLd($bodyHtml) !!}
    @endunless
    @if (! $noindex && ! str_contains($headTags, 'rel="canonical"'))
        <link rel="canonical" href="{{ $pageUrl }}">
    @endif
    @unless ($ownOg)
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDesc }}">
        <meta property="og:url" content="{{ $pageUrl }}">
    @endunless
    @unless (str_contains($headTags, 'og:image'))
        <meta property="og:image" content="{{ asset('images/og-default.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    @unless (str_contains($headTags, 'twitter:card'))
        <meta name="twitter:card" content="summary_large_image">
    @endunless
</head>
<body>
    @include('partials.navbar')

    <main id="main">
        {!! $bodyHtml !!}
    </main>

    @include('partials.footer')

    {{-- WhatsApp button (all pages except the test screens, which use their own layout). Change the number here. --}}
    <a href="https://wa.me/923154315382?text={{ rawurlencode('Hi, I have a question about Etude World.') }}" class="wa-float" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
        <svg viewBox="0 0 32 32" width="30" height="30" fill="#fff" aria-hidden="true"><path d="M16 3C8.8 3 3 8.8 3 16c0 2.3.6 4.5 1.7 6.4L3 29l6.8-1.8A13 13 0 0 0 16 29c7.2 0 13-5.8 13-13S23.2 3 16 3zm0 23.700c-2 0-3.9-.6-5.600-1.600l-.4-.2-4 1 1.100-3.900-.3-.4A10.700 10.700 0 1 1 16 26.700zm5.900-8c-.3-.2-1.900-.9-2.200-1-.3-.1-.5-.2-.7.200-.2.300-.8 1-1 1.200-.2.200-.4.200-.7.100-.3-.2-1.400-.5-2.600-1.600-1-.9-1.600-1.900-1.800-2.200-.2-.3 0-.5.100-.7l.5-.6c.1-.2.200-.3.300-.5.100-.2 0-.4 0-.6l-1-2.300c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.100-.9.400-.3.300-1.200 1.100-1.200 2.800s1.200 3.200 1.400 3.500c.2.200 2.400 3.700 5.800 5.100.8.400 1.500.6 2 .7.800.3 1.600.2 2.200.1.700-.1 1.900-.8 2.200-1.500.3-.7.300-1.400.2-1.500l-.6-.3z"/></svg>
    </a>

    <script src="{{ asset('js/site.js') }}?v=13" defer></script>
</body>
</html>
