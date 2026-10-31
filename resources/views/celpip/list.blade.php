@extends('layouts.app')

@php
    $info  = $module ? \App\Models\CelpipTest::PUBLIC_MODULES[$module] : null;
    $title = $module ? 'Practice ' . $info['label'] : 'Full Mock Tests';
    // Tests that can be taken now come first.
    $sorted = $module ? $tests->sortByDesc(fn ($t) => $t->moduleReady($module))->values() : $tests;
@endphp

@php $seo = \App\Support\SeoPages::resolve($format === 'ls' ? 'celpip-ls' : 'celpip-general', $module ?: 'mock'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[['CELPIP', route('celpip')], [$label, route('celpip.' . $format)], [$title]]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container">
            @include('celpip.partials.tabs', ['active' => $module ?? 'mock'])
            @include('celpip.partials.grid', ['items' => $sorted, 'module' => $module, 'empty' => 'No tests here yet. Check back soon!'])
        </div>
    </section>

    @if (! empty($seo['content']))
        <section class="section section--compact seo-extra">
            <div class="container"><div class="article-body">{!! $seo['content'] !!}</div></div>
        </section>
    @endif
@endsection
