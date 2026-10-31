@extends('layouts.exam')

{{-- The TOEFL test screen. public/js/toefl-exam.js builds every screen from the JSON below. --}}
@php
    use App\Support\Toefl;
    $isMock = $mode === 'mock';
    $title  = $isMock ? $test->name . ' – Full Mock Test' : $test->name . ' – ' . Toefl::PUBLIC[$modules[0]]['label'];
    $student = auth('student')->user();
@endphp

@php $seo = \App\Models\TestSeo::resolve('toefl', $test, $isMock ? 'mock' : $modules[0], $title . ' – ' . config('app.name')); @endphp
@section('title', $title)
@section('seo') @include('partials.test-seo-head', ['seo' => $seo]) @endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/toefl-exam.css') }}?v=12">
@endpush

@section('content')
<div class="exam tfx" id="tfx" data-mode="{{ $mode }}">
    <header class="exam-bar">
        <a href="{{ $backUrl }}" class="exam-bar__brand" data-exit>
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="30" height="30">
            <span>Etude <strong>World</strong></span>
        </a>
        <div class="exam-bar__title">
            <strong>{{ $test->name }}</strong>
            <span data-step-label>{{ $isMock ? 'Full mock test' : 'Practice' }}</span>
        </div>
        @if ($student)<span class="tfx-sid" data-sid hidden>Student ID: <strong>{{ $student->id }}</strong></span>@endif
        <div class="exam-timer" data-timer hidden aria-live="off">@include('admin.partials.icon', ['name' => 'clock'])<span data-timer-text>00:00</span></div>
        <a href="{{ $backUrl }}" class="exam-bar__exit" data-exit>Exit</a>
    </header>
    <main class="exam-main tfx-main" id="tfx-main"></main>
</div>
<script type="application/json" id="tfx-data">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script>window.TFX_URLS = {score: @json(route('toefl.score')), submit: @json(route('toefl.submissions.store')), portal: @json(route('student.portal')), back: @json($backUrl), labels: @json(collect(Toefl::PUBLIC)->map(fn ($x) => ['label' => $x['label'], 'time' => $x['time'], 'parts' => $x['parts']]))};</script>
<script src="{{ asset('js/toefl-exam.js') }}?v=8"></script>
@endsection
