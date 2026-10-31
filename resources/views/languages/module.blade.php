@extends('layouts.app')

@section('title', $module->meta_title ?: ($module->title . ' – Learn ' . $info['name'] . ' – ' . config('app.name')))
@section('description', $module->meta_description ?: ($module->description ?: $info['intro']))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/languages.css') }}?v=1">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Learn ' . $info['name'], route('languages.show', $language)], [$module->title]]" />
            <h1>{{ $module->icon }} {{ $module->title }}</h1>
            @if ($module->description) <p class="lead">{{ $module->description }}</p> @endif
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            @if ($lessons->isEmpty())
                <p class="celpip-empty">Lessons for this module are on their way. Check back soon!</p>
            @else
                <ol class="lesson-list">
                    @foreach ($lessons as $l)
                        <li>
                            <a href="{{ route('languages.lesson', [$language, $module->slug, $l->slug]) }}" class="lesson-item">
                                <span class="lesson-num">{{ $loop->iteration }}</span>
                                <span class="lesson-text">
                                    <strong>{{ $l->title }}</strong>
                                    @if ($l->summary) <small>{{ $l->summary }}</small> @endif
                                </span>
                                @if ($l->level) <span class="lesson-level">{{ $l->level }}</span> @endif
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>
@endsection
