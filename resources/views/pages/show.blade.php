@extends('layouts.app')

@section('title', $page->meta_title ?: $page->title . ' – ' . config('app.name'))
@section('description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($page->body))), 155))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
@endpush

@section('content')
    <section class="celpip-hero celpip-hero--sm">
        <div class="container">
            <x-breadcrumbs :items="[[$page->title]]" />
            <h1>{{ $page->title }}</h1>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container-narrow">
            <div class="article-body" style="margin-top:0">
                {!! $page->body !!}
            </div>
        </div>
    </section>
@endsection
