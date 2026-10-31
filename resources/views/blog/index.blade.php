@php
    $intros = [
        'ielts'             => 'Tips, strategies and guides to help you reach the IELTS band score you need.',
        'celpip'            => 'Practical advice and guides for every part of the CELPIP test.',
        'toefl'             => 'Practical advice and guides for every part of the TOEFL iBT test.',
        'study-methods'     => 'Smart ways to study, remember more and stay motivated.',
        'language-learning' => 'Ideas and techniques for learning a new language faster.',
    ];
    $pageTitle = $current ? \App\Models\Blog::CATEGORIES[$current] . ' Blog' : 'Blog';
    $lead = $current ? $intros[$current] : 'Study tips, exam guides and language-learning advice from the Etude World team.';
    $path = $current ? 'blog/' . $current : 'blog';
@endphp
@extends('layouts.app')

@section('title', $pageTitle . ' – ' . config('app.name'))
@section('description', $lead)

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="canonical" href="{{ url($path) }}">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Blog'], [$info]]" />
            <h1>{{ $pageTitle }}</h1>
            <p class="lead">{{ $lead }}</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            <nav class="blog-tabs" aria-label="Blog categories">
                @foreach (['language-learning' => 'blog.language', 'ielts' => 'blog.ielts', 'celpip' => 'blog.celpip', 'toefl' => 'blog.toefl', 'study-methods' => 'blog.study'] as $key => $route)
                    <a href="{{ route($route) }}" class="{{ $current === $key ? 'is-active' : '' }}">{{ \App\Models\Blog::CATEGORIES[$key] }}</a>
                @endforeach
            </nav>

            @if ($posts->isEmpty())
                <p class="celpip-empty">No articles here yet. New posts are on their way, so check back soon!</p>
            @else
                <div class="post-grid">
                    @foreach ($posts as $post)
                        @php
                            $date = $post->published_at ?? $post->created_at;
                            $excerpt = $post->meta_description ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($post->content ?? ''))), 130);
                        @endphp
                        <a href="{{ url($post->public_path) }}" class="post-card">
                            <div class="post-card__img" style="{{ $post->image ? 'background-image:url(' . asset($post->image) . ')' : '' }}" role="img" aria-label="{{ $post->image_alt ?: $post->title }}"></div>
                            <div class="post-card__body">
                                <span class="post-card__cat">{{ $post->category_label }}</span>
                                <h3>{{ $post->title }}</h3>
                                @if ($excerpt)<p class="post-card__excerpt">{{ $excerpt }}</p>@endif
                                <time datetime="{{ $date->toDateString() }}">{{ $date->format('j M Y') }}@if ($post->author) · {{ $post->author->name }}@endif</time>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if ($posts->hasPages())
                    <nav class="simple-pager">
                        @if (! $posts->onFirstPage())<a href="{{ $posts->previousPageUrl() }}">‹ Newer</a>@endif
                        @if ($posts->hasMorePages())<a href="{{ $posts->nextPageUrl() }}">Older ›</a>@endif
                    </nav>
                @endif
            @endif
        </div>
    </section>
@endsection
