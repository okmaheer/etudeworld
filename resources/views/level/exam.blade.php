@extends('layouts.exam')

@section('title', $test->meta_title ?: $test->title)

@push('head')
    <link rel="stylesheet" href="{{ asset('css/level-test.css') }}?v=8">
    @if ($test->meta_description)<meta name="description" content="{{ $test->meta_description }}">@endif
@endpush

@push('scripts')
    <script>window.LEVEL_TEST = {scoreUrl: @json(route('level-test.score', $test->slug)), total: {{ $total }}};</script>
    <script src="{{ asset('js/level-test.js') }}?v=3" defer></script>
@endpush

@section('content')
@php
    $fill = function ($q) {
        $input = '<input type="text" class="lt-blank" name="q' . $q->id . '" data-qid="' . $q->id . '" autocomplete="off" autocapitalize="off" spellcheck="false" aria-label="Your answer">';
        $text = e($q->prompt);
        return str_contains($text, '___') ? preg_replace('/___+/', $input, $text, 1) : $text . '<br>' . $input;
    };
    $ranges = [];
    $first = 1;
    foreach ([1, 2, 3] as $i) {
        $ids = ($sections->get($i)?->questions ?? collect())->pluck('id');
        $ranges[$i] = $ids;
    }
@endphp
<section class="lt-intro" id="lt-intro">
    <div class="lt-intro__card">
        <a href="{{ route('home') }}" class="lt-brand" aria-label="Back to Etude World home">Etude <b>World</b></a>
        <h1>{{ $test->title }}</h1>
        <p class="lt-intro__facts"><span>{{ $total }} questions</span><span>3 sections</span><span>Result with correct answers</span></p>
        <h2>Instructions</h2>
        @if ($test->instructions)
            <div class="lt-intro__text">{!! nl2br(e($test->instructions)) !!}</div>
        @endif
        <ul class="lt-intro__list">
            <li>The test has three sections. Use the <strong>Section 1, 2 and 3</strong> buttons at the bottom right to move between them.</li>
            <li>The numbered boxes at the bottom left turn <strong>blue</strong> when you answer a question. Click a box to jump to that question.</li>
            <li>Each question has one correct answer. You can change an answer at any time before you finish.</li>
            <li>When you are ready, press <strong>Finish Test</strong> at the top right to see your level and the correct answers.</li>
            <li>If you reload the page, the test starts again.</li>
        </ul>
        <button type="button" class="lt-start" id="lt-start">Start Test</button>
    </div>
</section>

<div class="lt" id="lt" hidden>
    <header class="lt-top">
        <div class="lt-top__row">
            <a href="{{ route('home') }}" class="lt-brand" aria-label="Back to Etude World home">Etude <b>World</b></a>
            <h1>{{ $test->title }}</h1>
            <div class="lt-finish-wrap">
                <span class="lt-warn" id="lt-warn" hidden></span>
                <button type="button" class="lt-finish" id="lt-finish">Finish Test</button>
        </div>
        </div>
    </header>

    <main class="lt-main" id="lt-questions">
        @foreach ([1, 2, 3] as $i)
            @php $sec = $sections->get($i); $qs = $sec?->questions ?? collect(); @endphp
            <section class="lt-section" data-section-panel="{{ $i }}" @if ($i !== 1) hidden @endif>
                @if ($sec?->instructions)
                    <div class="lt-instructions"><strong>Section {{ $i }}</strong> {!! nl2br(e($sec->instructions)) !!}</div>
                @endif

                @foreach ($i === 3 ? [1, 2, null] : [null] as $p)
                    @php $group = $qs->filter(fn ($q) => $q->passage == $p); $ps = $p ? ($sec->passages[$p] ?? null) : null; @endphp
                    @if ($ps && ($ps['body'] ?? '') !== '')
                        <article class="lt-passage">
                            @if (($ps['title'] ?? '') !== '')<h2>{{ $ps['title'] }}</h2>@endif
                            @foreach (preg_split('/\R{2,}/', $ps['body']) as $para)<p>{!! nl2br(e($para)) !!}</p>@endforeach
                        </article>
                    @endif
                    @foreach ($group as $q)
                        <div class="lt-q" id="q-{{ $q->id }}">
                            @if ($q->type === 'mcq')
                                <p class="lt-q__p"><b>{{ $numbers[$q->id] }}.</b> {!! nl2br(e($q->prompt)) !!}</p>
                                <div class="lt-opts">
                                    @foreach ($q->options as $oi => $opt)
                                        <label class="lt-opt"><input type="radio" name="q{{ $q->id }}" value="{{ $oi }}" data-qid="{{ $q->id }}"><span><i>{{ chr(97 + $oi) }}</i> {{ $opt }}</span></label>
                                    @endforeach
                                </div>
                            @else
                                <p class="lt-q__p"><b>{{ $numbers[$q->id] }}.</b> {!! $fill($q) !!}</p>
                            @endif
                        </div>
                    @endforeach
                @endforeach
                @if ($qs->isEmpty())<p class="lt-empty">No questions in this section yet.</p>@endif
            </section>
        @endforeach
    </main>

    <section class="lt-result" id="lt-result" hidden aria-live="polite"></section>

    <footer class="lt-bottom" id="lt-bottom">
        <nav class="lt-boxes" aria-label="Question progress">
            @foreach ([1, 2, 3] as $i)
                <div class="lt-boxes__group">
                    <span class="lt-boxes__label">Section {{ $i }}</span>
                    <div class="lt-boxes__row">
                        @foreach ($ranges[$i] as $id)
                            <button type="button" class="lt-box" data-box="{{ $id }}" data-section="{{ $i }}" aria-label="Question {{ $numbers[$id] }}">{{ $numbers[$id] }}</button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>
        <div class="lt-tabs">
            @foreach ([1, 2, 3] as $i)
                <button type="button" class="lt-tab {{ $i === 1 ? 'is-active' : '' }}" data-tab="{{ $i }}">Section {{ $i }}</button>
            @endforeach
        </div>
    </footer>
</div>
@endsection
