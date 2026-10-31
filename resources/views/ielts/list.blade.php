@extends('layouts.app')

{{-- /ielts/{format}/mock-tests and /ielts/{format}/{skill}: the test cards added in the admin panel --}}
@php
    use App\Models\IeltsTest;
    $info  = $module ? IeltsTest::PUBLIC_MODULES[$module] : null;
    $title = $module ? 'Practice ' . $info['label'] : 'Full Mock Tests';
    // Tests that can be taken now come first.
    $sorted = $module ? $tests->sortByDesc(fn ($t) => $t->moduleReady($module))->values() : $tests;
@endphp

@php $seo = \App\Support\SeoPages::resolve($format === 'general-training' ? 'ielts-gt' : 'ielts-academic', $module ?: 'mock'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm ielts-hero">
        <div class="container">
            <x-breadcrumbs :items="[['IELTS', route('ielts')], [$label, route($format === 'general-training' ? 'ielts.general' : 'ielts.academic')], [$title]]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            @include('ielts.partials.tabs', ['active' => $module ?? 'mock'])
            @if ($sorted->isEmpty())
                <p class="celpip-empty">No tests here yet. Check back soon!</p>
            @else
                <div class="test-grid">
                    @foreach ($sorted as $t)
                        @include('ielts.partials.card', ['test' => $t, 'module' => $module])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @if (! empty($seo['content']))
        <section class="section section--compact seo-extra">
            <div class="container"><div class="article-body">{!! $seo['content'] !!}</div></div>
        </section>
    @endif
@endsection
