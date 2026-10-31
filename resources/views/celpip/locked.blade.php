@extends('layouts.app')

@section('title', $test->name . ' (Premium) – ' . config('app.name'))

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="gate">
        <div class="gate__card">
            <div class="gate__icon">@include('admin.partials.icon', ['name' => 'lock'])</div>
            <span class="pill pill--premium">Premium test</span>
            <h1>{{ $test->name }}</h1>
            <p>This {{ $module ? strtolower(\App\Models\CelpipTest::PUBLIC_MODULES[$module]['label']) . ' practice test' : 'full mock test' }}
                is a premium test. {{ \App\Support\PaidAccess::message('celpip') }}</p>
            <div class="gate__actions">
                @if (! auth('student')->check())
                    <a href="{{ route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']) }}" class="btn btn-primary">Log in</a>
                @else
                    <a href="{{ \Illuminate\Support\Facades\Route::has('purchase') ? route('purchase') : route('contact') }}" class="btn btn-primary">Purchase now</a>
                @endif
                <a href="{{ $module ? route('celpip.practice.module', [$format, $module]) : route('celpip.practice.mocks', $format) }}" class="btn btn-ghost">Browse free tests</a>
            </div>
        </div>
    </section>
@endsection
