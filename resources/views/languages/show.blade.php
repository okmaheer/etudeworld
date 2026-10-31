@extends('layouts.app')

@section('title', 'Learn ' . $info['name'] . ' Online – ' . config('app.name'))
@section('description', $info['intro'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/languages.css') }}?v=1">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['Learn ' . $info['name']]]" />
            <h1>Learn {{ $info['name'] }}</h1>
            <p class="lead">{{ $info['intro'] }}</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            @if ($modules->isEmpty())
                <p class="celpip-empty">{{ $info['name'] }} lessons are on their way. Check back soon!</p>
            @else
                <div class="card-grid card-grid--3">
                    @foreach ($modules as $m)
                        <a href="{{ route('languages.module', [$language, $m->slug]) }}" class="card mod-card">
                            <span class="mod-icon" aria-hidden="true">{{ $m->icon ?: '📘' }}</span>
                            <h3>{{ $m->title }}</h3>
                            <p>{{ $m->description }}</p>
                            <span class="card-link">{{ $m->lessons_count ? $m->lessons_count . ' ' . \Illuminate\Support\Str::plural('lesson', $m->lessons_count) : 'Coming soon' }} →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
