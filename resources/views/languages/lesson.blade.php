@extends('layouts.app')

@section('title', $lesson->meta_title ?: ($lesson->title . ' – ' . $module->title . ' – ' . config('app.name')))
@section('description', $lesson->meta_description ?: ($lesson->summary ?: $module->description ?: $info['intro']))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/languages.css') }}?v=1">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Learn ' . $info['name'], route('languages.show', $language)], [$module->title, route('languages.module', [$language, $module->slug])], [$lesson->title]]" />
            <h1>{{ $lesson->title }}</h1>
            @if ($lesson->level) <p><span class="lesson-level">{{ \App\Models\LanguageLesson::LEVELS[$lesson->level] ?? $lesson->level }}</span></p> @endif
        </div>
    </section>

    <section class="section section--compact">
        <div class="container-narrow">
            <div class="article-body lesson-body" style="margin-top:0">
                {!! $lesson->body !!}
            </div>

            <nav class="lesson-nav" aria-label="Lesson navigation">
                @if ($prev) <a href="{{ route('languages.lesson', [$language, $module->slug, $prev->slug]) }}" class="lesson-nav__prev">← {{ $prev->title }}</a> @else <span></span> @endif
                @if ($next) <a href="{{ route('languages.lesson', [$language, $module->slug, $next->slug]) }}" class="lesson-nav__next">{{ $next->title }} →</a> @endif
            </nav>
        </div>
    </section>
@endsection
