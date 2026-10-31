@extends('layouts.exam')

{{--
    IELTS Speaking test – everything happens in ONE box on one page:
      Main Section → Part 1 (instructions → spoken questions) → "Part 2 >" → Part 2 (topic card,
      1 min preparation, 2 min recording) → "Part 3 >" → Part 3 (like Part 1) → "results in your portal".
    public/js/ielts-speaking.js runs it. Recordings go to Admin → Evaluation Requests → IELTS Evaluation.
--}}

@php $seo = \App\Models\TestSeo::resolve('ielts', $test, 'speaking', $test->name . ' – IELTS Speaking – ' . config('app.name')); @endphp
@section('title', $test->name . ' – IELTS Speaking')
@section('seo') @include('partials.test-seo-head', ['seo' => $seo]) @endsection

@section('content')
<div class="exam ipx" data-ielts-speaking data-test-id="{{ $test->id }}"
     data-submit-url="{{ route('ielts.submissions.store') }}" data-portal-url="{{ route('student.portal') }}">
    <script type="application/json" data-ip-data>@json(\App\Support\IeltsSpeaking::screens($test->moduleContent('speaking')))</script>

    {{-- ───────── Top bar ───────── --}}
    <header class="exam-bar">
        <a href="{{ $backUrl }}" class="exam-bar__brand" data-exit>
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="30" height="30">
            <span>Etude <strong>World</strong></span>
        </a>
        <div class="exam-bar__title">
            <strong>{{ $test->name }}</strong>
            <span>{{ $test->format === 'gt' ? 'IELTS General Training' : 'IELTS Academic' }} · Speaking</span>
        </div>
        <a href="{{ $backUrl }}" class="exam-bar__exit ipx-exit" data-exit>Exit</a>
        <button type="button" class="btn btn-primary ipx-partnext" data-ip-partnext hidden>Part 2 &rsaquo;</button>
    </header>

    <main class="exam-main">
        <div class="lsx-box spx-box ipx-box">
            <header class="lsx-head">
                <h2 data-ip-title>IELTS Speaking Test</h2>
                <ol class="ipx-parts" aria-label="Parts">
                    <li data-ip-progress="1">Part 1</li>
                    <li data-ip-progress="2">Part 2</li>
                    <li data-ip-progress="3">Part 3</li>
                </ol>
                <div class="lsx-nav">
                    <button type="button" class="btn btn-primary" data-ip-next>Next</button>
                </div>
            </header>
            <div class="lsx-stage" data-ip-stage aria-live="polite"></div>
        </div>
        <p class="ipx-student">Logged in as <strong>{{ auth('student')->user()->name }}</strong> ({{ auth('student')->user()->email }}).
            Your recordings will be sent to your teacher.</p>
    </main>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ielts-speaking.js') }}?v=7"></script>
@endpush
