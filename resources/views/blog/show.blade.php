@extends('layouts.app')

@php
    $metaTitle = $blog->meta_title ?: $blog->title;
    $imageUrl  = $blog->image ? asset($blog->image) : null;
    $published = $blog->published_at ?? $blog->created_at;
    $structured = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $blog->title,
        'description' => $description,
        'mainEntityOfPage' => $canonical,
        'datePublished' => $published->toAtomString(),
        'dateModified' => $blog->updated_at->toAtomString(),
        'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => \App\Models\SiteSetting::logoUrl()]],
    ];
    if ($imageUrl) $structured['image'] = $imageUrl;
    if ($blog->author) {
        $structured['author'] = array_filter([
            '@type' => 'Person',
            'name' => $blog->author->name,
            'url' => route('authors.show', $blog->author),
            'sameAs' => array_values(array_column($blog->author->social_links, 'url')) ?: null,
        ]);
    }
    $breadcrumbs = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $blog->isSampleAnswer() ? $blog->category_title : $blog->category_label . ' Blog', 'item' => $blog->category_url],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $blog->title],
        ],
    ];
@endphp

@section('title', $metaTitle)
@section('description', $description)

@push('head')
    <link rel="canonical" href="{{ $canonical }}">
    @if ($blog->status !== 'published')<meta name="robots" content="noindex">@endif
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta property="og:image:alt" content="{{ $blog->image_alt ?: $blog->title }}">
    @endif
    <meta property="article:published_time" content="{{ $published->toAtomString() }}">
    <meta property="article:modified_time" content="{{ $blog->updated_at->toAtomString() }}">
    <meta name="twitter:card" content="{{ $imageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if ($imageUrl)<meta name="twitter:image" content="{{ $imageUrl }}">@endif
    <link rel="stylesheet" href="{{ asset('css/content-blocks.css') }}?v=3">
    <script type="application/ld+json">{!! json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @if ($faqs)
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faqs),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
@endpush

@section('content')
    @if ($blog->status !== 'published')
        <div class="preview-bar">Preview: this blog is a <strong>draft</strong>, so visitors can't see it yet. <a href="{{ route('admin.blogs.edit', $blog) }}">Back to editing</a></div>
    @endif

    <article class="article">
        <header class="article-head container">
            @php
                $crumbs = $blog->isSampleAnswer() ? [['Sample Answers']] : [['Blog']];
                $crumbs[] = [$blog->isSampleAnswer() ? $blog->category_title : $blog->category_label, $blog->category_url];
                $crumbs[] = [$blog->title];
            @endphp
            <x-breadcrumbs :items="$crumbs" align="center" :schema="false" />
            <span class="eyebrow">{{ $blog->isSampleAnswer() ? $blog->category_title : $blog->category_label }}</span>
            <h1>{{ $blog->title }}</h1>
            <div class="article-meta">
                @if ($blog->author)
                    <a href="{{ route('authors.show', $blog->author) }}" class="meta-author">
                        @if ($blog->author->image)
                            <img src="{{ asset($blog->author->image) }}" alt="" width="36" height="36">
                        @else
                            <span class="meta-initials">{{ $blog->author->initials }}</span>
                        @endif
                        <span>{{ $blog->isSampleAnswer() ? 'Evaluated by' : 'By' }} <strong>{{ $blog->author->name }}</strong></span>
                    </a>
                    <span class="dot" aria-hidden="true">·</span>
                @endif
                <time datetime="{{ $published->toDateString() }}">{{ $published->format('j F Y') }}</time>
                <span class="dot" aria-hidden="true">·</span>
                <span>{{ $readingTime }} min read</span>
            </div>
        </header>

        @if ($imageUrl)
            <figure class="article-hero container">
                <img src="{{ $imageUrl }}" alt="{{ $blog->image_alt ?: $blog->title }}" width="1200" height="630">
            </figure>
        @endif

        @php $hasToc = count($toc) >= 2; @endphp
        <div class="article-main">
            @if ($hasToc)
                <aside class="toc" aria-label="Table of Contents">
                    <details class="toc__box" open>
                        <summary class="toc__title">Table of Contents</summary>
                        <ol class="toc__list">
                            @foreach ($toc as $item)
                                <li class="toc__item toc__item--h{{ $item['level'] }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </details>
                </aside>
            @endif
            <div class="article-body container-narrow" id="article-body">
                {!! $content !!}
                @include('partials.share-box', ['url' => $canonical, 'title' => $blog->title])
            </div>

            {{-- Right side: the newest posts of the same category (titles only) --}}
            @if ($recent->count() > 0)
                <aside class="recent" aria-label="Recent in {{ $recentLabel }}">
                    <div class="recent__box">
                        <h2 class="recent__title">Recent in {{ $recentLabel }}</h2>
                        <ul class="recent__list">
                            @foreach ($recent as $r)
                                <li>
                                    @if ($r->id === $blog->id)
                                        <span class="recent__item is-current" aria-current="page">{{ $r->title }}</span>
                                    @else
                                        <a class="recent__item" href="{{ url($r->public_path) }}">{{ $r->title }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>
            @endif
        </div>

        @if ($blog->author)
            <div class="container-narrow">
                @include('partials.author-box', ['author' => $blog->author, 'label' => $blog->isSampleAnswer() ? 'Evaluated by' : 'Written by'])
            </div>
        @endif
    </article>

    <script>
    (function () {
        var body = document.getElementById('article-body');
        var header = document.querySelector('.site-header');
        if (!body || !header) return;
        // reading progress bar, right under the menu bar
        var bar = document.createElement('div');
        bar.className = 'read-progress';
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-label', 'Reading progress');
        bar.setAttribute('aria-valuemin', '0'); bar.setAttribute('aria-valuemax', '100');
        var fill = document.createElement('div');
        fill.className = 'read-progress__fill';
        bar.appendChild(fill); header.appendChild(bar);

        var tocBox = document.querySelector('.toc__box');
        if (tocBox && window.innerWidth < 1320) tocBox.removeAttribute('open');   // folded on phones
        var links = [].slice.call(document.querySelectorAll('.toc__list a'));
        // on narrower screens the list is a drop-down: close it once a section is chosen
        links.forEach(function (a) { a.addEventListener('click', function () { if (tocBox && window.innerWidth < 1320) tocBox.removeAttribute('open'); }); });
        var heads = links.map(function (a) { return document.getElementById(decodeURIComponent(a.getAttribute('href').slice(1))); });
        var ticking = false;
        function update() {
            ticking = false;
            var r = body.getBoundingClientRect();
            var total = r.height - window.innerHeight * 0.5;
            var done = -r.top + window.innerHeight * 0.3;
            var pct = total <= 0 ? 100 : Math.max(0, Math.min(100, done / total * 100));
            fill.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', Math.round(pct));
            var cur = -1;
            heads.forEach(function (h, i) { if (h && h.getBoundingClientRect().top <= 140) cur = i; });
            links.forEach(function (a, i) { a.classList.toggle('is-current', i === cur); });
        }
        function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(update); } }
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        update();
    })();
    </script>
@endsection
