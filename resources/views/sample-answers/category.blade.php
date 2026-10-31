@extends('layouts.app')

@section('title', $cat['title'] . ' – ' . config('app.name'))
@section('description', 'Read ' . $cat['title'] . ': model answers with clear structure, useful vocabulary and tips you can use in your own test.')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="canonical" href="{{ url($cat['path']) }}">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Sample Answers'], [$cat['label']]]" />
            <h1>{{ $cat['title'] }}</h1>
            <p class="lead">Model answers to learn from: see how they're structured, the vocabulary they use, and why they score well.</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            @if ($posts->isEmpty())
                <p class="celpip-empty">Sample answers for this section are on their way. Check back soon!</p>
            @else
                <div class="post-grid">
                    @foreach ($posts as $post)
                        <a href="{{ url($post->public_path) }}" class="post-card">
                            <div class="post-card__img" style="{{ $post->image ? 'background-image:url(' . asset($post->image) . ')' : '' }}"></div>
                            <div class="post-card__body">
                                <span class="post-card__cat">{{ $cat['label'] }}</span>
                                <h3>{{ $post->title }}</h3>
                                <time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">{{ ($post->published_at ?? $post->created_at)->format('j M Y') }}</time>
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
