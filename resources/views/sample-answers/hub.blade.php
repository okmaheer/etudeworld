@extends('layouts.app')

@php
    $partWord = \Illuminate\Support\Str::contains($h['items'][0]['label'], 'Part') ? 'part' : 'task';
    $base = url($h['path']);
    $link = fn (array $q) => $base . ($q ? '?' . http_build_query($q) : '');
    $filtered = $part || $topic;
@endphp

@section('title', $h['title'] . ' – ' . config('app.name'))
@section('description', 'Read ' . $h['exam'] . ' ' . $h['skill'] . ' sample answers for ' . implode(', ', array_column($h['items'], 'label')) . ', by topic, with model answers you can learn from.')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="canonical" href="{{ $base }}">
    @if ($filtered)<meta name="robots" content="noindex, follow">@endif
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Sample Answers'], [$h['exam'] . ' ' . $h['skill']]]" />
            <h1>{{ $h['title'] }}</h1>
            <p class="lead">Model answers for every {{ $h['exam'] }} {{ $h['skill'] }} {{ $partWord }}: see how they are structured, the vocabulary they use, and why they score well.</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container sa-layout">

            {{-- Section 1: topics (30%) --}}
            <aside class="sa-side" aria-label="Topics">
                <div class="sa-side__box">
                    <h2 class="sa-side__title">Topics</h2>
                    <ul class="sa-topics">
                        <li><a href="{{ $link(array_filter(['part' => $part])) }}" class="{{ $topic ? '' : 'is-active' }}"><span>All topics</span><b>{{ array_sum(array_column($topics, 'count')) }}</b></a></li>
                        @foreach ($topics as $t)
                            <li><a href="{{ $link(array_filter(['topic' => $t['slug'], 'part' => $part])) }}" class="{{ $topic === $t['slug'] ? 'is-active' : '' }}"><span>{{ $t['label'] }}</span><b>{{ $t['count'] }}</b></a></li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            {{-- Section 2: Part / Task buttons and the sample answers (70%) --}}
            <div class="sa-main">
                <nav class="sa-parts" aria-label="{{ ucfirst($partWord) }}s">
                    <a href="{{ $link(array_filter(['topic' => $topic])) }}" class="{{ $part ? '' : 'is-active' }}">All {{ $partWord }}s</a>
                    @foreach ($h['items'] as $item)
                        @php $slug = basename($item['path']); @endphp
                        <a href="{{ $link(array_filter(['topic' => $topic, 'part' => $slug])) }}" class="{{ $part === $slug ? 'is-active' : '' }}">{{ $item['label'] }}<small>{{ $partCounts[$item['key']] ?? 0 }}</small></a>
                    @endforeach
                </nav>

                @if ($filtered)
                    <p class="sa-summary">
                        Showing <strong>{{ $posts->total() }}</strong> {{ \Illuminate\Support\Str::plural('answer', $posts->total()) }}
                        @if ($topicLabel) on <strong>{{ $topicLabel }}</strong>@endif
                        @if ($part) in <strong>{{ collect($h['items'])->first(fn ($i) => basename($i['path']) === $part)['label'] }}</strong>@endif
                        · <a href="{{ $base }}">Clear filters</a>
                    </p>
                @endif

                @if ($posts->isEmpty())
                    <p class="celpip-empty">{{ $filtered ? 'No sample answers match these filters yet. Try another topic or ' . $partWord . '.' : 'Sample answers are on their way. Check back soon!' }}</p>
                @else
                    <div class="post-grid sa-grid">
                        @foreach ($posts as $post)
                            @php $cat = collect($h['items'])->firstWhere('key', $post->category); @endphp
                            <a href="{{ url($post->public_path) }}" class="post-card">
                                <div class="post-card__img" style="{{ $post->image ? 'background-image:url(' . asset($post->image) . ')' : '' }}"></div>
                                <div class="post-card__body">
                                    <span class="post-card__cat">{{ $cat['label'] ?? '' }}@if ($post->topic) · {{ $post->topic }}@endif</span>
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
        </div>
    </section>
@endsection
