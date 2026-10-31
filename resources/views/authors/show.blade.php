@extends('layouts.app')

@php
    $bioText = trim(strip_tags($author->bio ?? ''));
    $description = $bioText ? \Illuminate\Support\Str::limit($bioText, 155) : $author->name . ' writes about IELTS, CELPIP and language learning on ' . config('app.name') . '.';
    $person = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $author->name,
        'url' => route('authors.show', $author),
        'image' => $author->image ? asset($author->image) : null,
        'description' => $bioText ?: null,
        'sameAs' => array_values(array_column($author->social_links, 'url')) ?: null,
    ]);
@endphp

@section('title', $author->name . ' – Author at ' . config('app.name'))
@section('description', $description)

@push('head')
    <link rel="canonical" href="{{ route('authors.show', $author) }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $author->name }}">
    <meta property="og:description" content="{{ $description }}">
    @if ($author->image)<meta property="og:image" content="{{ asset($author->image) }}">@endif
    <script type="application/ld+json">{!! json_encode($person, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <section class="author-hero">
        <div class="container author-hero__inner">
            <div class="author-hero__photo">
                @if ($author->image)
                    <img src="{{ asset($author->image) }}" alt="Photo of {{ $author->name }}" width="160" height="160">
                @else
                    <span>{{ $author->initials }}</span>
                @endif
            </div>
            <div class="author-hero__text">
                <span class="eyebrow">Author</span>
                <h1>{{ $author->name }}</h1>
                @if ($author->bio)
                    <div class="author-hero__bio">{!! $author->bio !!}</div>
                @endif
                @if ($author->social_links)
                    <div class="social-links social-links--lg">
                        @foreach ($author->social_links as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener me" class="social-link social-link--{{ $link['key'] }}" aria-label="{{ $author->name }} on {{ $link['label'] }}">
                                @include('partials.social-icon', ['name' => $link['key']])
                                <span>{{ $link['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="list-title">Articles by {{ $author->name }}</h2>
            @if ($blogs->isEmpty())
                <p class="muted-text">No published articles yet. Check back soon!</p>
            @else
                <div class="post-grid">
                    @foreach ($blogs as $post)
                        <a href="{{ url($post->public_path) }}" class="post-card">
                            <div class="post-card__img" style="{{ $post->image ? 'background-image:url(' . asset($post->image) . ')' : '' }}"></div>
                            <div class="post-card__body">
                                <span class="post-card__cat">{{ $post->category_label }}</span>
                                <h3>{{ $post->title }}</h3>
                                <time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">{{ ($post->published_at ?? $post->created_at)->format('j M Y') }}</time>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if ($blogs->hasPages())
                    <nav class="simple-pager">
                        @if (! $blogs->onFirstPage())<a href="{{ $blogs->previousPageUrl() }}">‹ Newer</a>@endif
                        @if ($blogs->hasMorePages())<a href="{{ $blogs->nextPageUrl() }}">Older ›</a>@endif
                    </nav>
                @endif
            @endif
        </div>
    </section>
@endsection
