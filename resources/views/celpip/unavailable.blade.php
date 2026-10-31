@extends('layouts.app')

@php $info = \App\Models\CelpipTest::PUBLIC_MODULES[$module]; @endphp

@section('title', $test->name . ' – ' . $info['label'] . ' – ' . config('app.name'))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="gate">
        <div class="gate__card">
            <div class="gate__icon gate__icon--soon">@include('admin.partials.icon', ['name' => $info['icon']])</div>
            <span class="pill pill--soon">Coming soon</span>
            <h1>{{ $test->name }}: {{ $info['label'] }}</h1>
            <p>The {{ strtolower($info['label']) }} questions for this test are being prepared. Please check back soon, or try another test in the meantime.</p>
            <div class="gate__actions">
                <a href="{{ $format === 'ls' ? route('celpip.ls') : route('celpip.practice.module', [$format, 'writing']) }}" class="btn btn-primary">{{ $format === 'ls' ? 'See all tests' : 'Try a writing test' }}</a>
                <a href="{{ route('celpip.practice.module', [$format, $module]) }}" class="btn btn-ghost">All {{ strtolower($info['label']) }} tests</a>
            </div>
        </div>
    </section>
@endsection
