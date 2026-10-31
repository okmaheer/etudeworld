@extends('layouts.app')

@section('title', $list['label'] . ' – ' . $list['skillLabel'] . ' Vocabulary – ' . config('app.name'))
@section('description', 'Words and phrases from ' . $list['label'] . ' (' . $list['examLabel'] . ' ' . $list['skillLabel'] . ') with meanings, examples and translations. Study them as a list, flashcards or exercises.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=8">@endpush

@push('scripts')@endpush

@section('content')
<section class="section">
    <div class="container vb-wrap" id="vb"
         data-key="{{ $list['key'] }}" data-data="{{ $list['data'] }}" data-script="{{ asset('js/vocab-pdf.js') }}?v=2">
        <x-breadcrumbs :items="[['Vocabulary Builder'], [$list['examLabel'], $list['examUrl']], [$list['skillLabel'], $list['skillUrl']], [$list['heading']]]" />
        <h1 style="margin:0 0 6px">{{ $list['heading'] }}: {{ $list['skillLabel'] }} vocabulary</h1>
        <p class="vb-count" id="vb-summary"></p>
        <div class="vb-progress" aria-hidden="true"><span id="vb-bar"></span></div>

        <div class="vb-bar">
            <label>My language
                <select id="vb-lang"><option value="">English only</option>
                    @foreach ($languages as $code => $name)<option value="{{ $code }}">{{ $name }}</option>@endforeach
                </select></label>
            <input type="search" id="vb-search" placeholder="Search words…" aria-label="Search words">
            <label><input type="checkbox" id="vb-phrases"> Phrases only</label>
        </div>

        @unless ($loggedIn)
            <p class="vb-note"><a href="{{ route('student.login') }}">Log in or sign up (free)</a> to save which words you have learned. You can study without an account.</p>
        @endunless

        {{-- The words are also written into the page itself, so search engines can read them. The script below replaces this list. --}}
        <div id="vb-list" class="vb-list">
            @foreach ($words as $w)
                <div class="vb-item"><strong>{{ $w['word'] }}</strong> @if ($w['type'])<em>({{ $w['type'] }})</em>@endif
                    <p>{{ $w['meaning'] }}</p>@if ($w['synonyms'])<p>Synonyms: {{ $w['synonyms'] }}</p>@endif @if ($w['example'])<p><em>{{ $w['example'] }}</em></p>@endif</div>
            @endforeach
        </div>

        <div id="vb-cards" hidden>
            <div class="vb-card" id="vb-card" role="button" tabindex="0" aria-label="Flashcard. Click to turn over."></div>
            <p class="vb-count" style="text-align:center">Click the card to turn it over.</p>
            <div class="vb-nav">
                <button type="button" class="vb-btn" id="vb-prev">← Back</button>
                <span id="vb-pos"></span>
                <button type="button" class="vb-btn is-primary" id="vb-next">Next →</button>
            </div>
        </div>

        <div class="vb-actions vb-actions--end">
            <button type="button" class="vb-btn" id="vb-mode">Flashcards</button>
            <a class="vb-btn" href="{{ $list['exercises'] }}">Exercises</a>
            <button type="button" class="vb-btn is-primary" id="vb-pdf">Download PDF</button>
        </div>

        @if ($prev || $next)
            <div class="vb-actions vb-actions--end vb-modnav">
                @if ($prev)<a class="vb-btn" href="{{ $prev['show'] }}">← Previous {{ $list['kind'] === 'topic' ? 'topic' : 'module' }}</a>@endif
                @if ($next)<a class="vb-btn is-primary" href="{{ $next['show'] }}">Next {{ $list['kind'] === 'topic' ? 'topic' : 'module' }} →</a>@endif
            </div>
        @endif
    </div>
</section>
<script>window.VB = {words: @json($words), known: @json($known), save: @json($loggedIn ? $list['progress'] : null)};</script>
<script src="{{ asset('js/vocab-builder.js') }}?v=3" defer></script>
@endsection
