@extends('layouts.app')
@php
    use App\Support\Toefl;
    $info  = $module ? Toefl::PUBLIC[$module] : null;
    $title = $module ? 'Practice ' . $info['label'] : 'Full Mock Tests';
    $ok = fn ($t) => $module ? ($t->moduleRow($module)?->slug && $t->moduleFilled($module)) : true;
    $sorted = $module ? $tests->sortByDesc($ok)->values() : $tests;
@endphp
@php $seo = \App\Support\SeoPages::resolve('toefl', $module ?: 'mock'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])
@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush
@section('content')
    <section class="celpip-hero celpip-hero--sm ielts-hero">
        <div class="container">
            <x-breadcrumbs :items="[['TOEFL', route('toefl')], ['TOEFL iBT 2026', route('toefl.hub')], [$title]]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
        </div>
    </section>
    <section class="section section--compact">
        <div class="container">
            @include('toefl.partials.tabs', ['active' => $module ?? 'mock'])
            @if ($sorted->isEmpty())
                <p class="celpip-empty">No tests here yet. Check back soon!</p>
            @else
                <div class="test-grid">@foreach ($sorted as $t) @include('toefl.partials.card', ['test' => $t, 'module' => $module]) @endforeach</div>
            @endif
        </div>
    </section>

    @if (! empty($seo['content']))
        <section class="section section--compact seo-extra">
            <div class="container"><div class="article-body">{!! $seo['content'] !!}</div></div>
        </section>
    @endif
@endsection
