@extends('layouts.app')

@section('title', $test->name . ' – ' . \App\Models\IeltsTest::PUBLIC_MODULES[$module]['label'] . ' – ' . config('app.name'))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="gate">
        <div class="gate__card">
            <div class="gate__icon gate__icon--soon">@include('admin.partials.icon', ['name' => \App\Models\IeltsTest::PUBLIC_MODULES[$module]['icon']])</div>
            <span class="pill pill--soon">{{ $heading }}</span>
            <h1>{{ $test->name }}: {{ \App\Models\IeltsTest::PUBLIC_MODULES[$module]['label'] }}</h1>
            <p>{{ $text }}</p>
            <div class="gate__actions">
                <a href="{{ $backUrl }}" class="btn btn-primary">See all {{ strtolower(\App\Models\IeltsTest::PUBLIC_MODULES[$module]['label']) }} tests</a>
            </div>
        </div>
    </section>
@endsection
