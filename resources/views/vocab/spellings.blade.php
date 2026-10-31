@extends('layouts.app')

@section('title', 'Important Spellings for IELTS Listening – ' . config('app.name'))
@section('description', 'The words you must spell correctly in IELTS Listening (names, places and common answers), grouped and searchable, with a listen-and-type spelling practice.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=7">@endpush

@section('content')
<section class="section">
    <div class="container vb-wrap" id="sp">
        <x-breadcrumbs :items="[['Vocabulary Builder'], ['IELTS', route('vocab.ielts')], ['Important spellings']]" />
        <div class="section-head">
            <span class="eyebrow">IELTS Listening</span>
            <h1>Important spellings for IELTS Listening</h1>
            <p>In IELTS Listening a wrong spelling is a wrong answer. These words appear in answers of real practice tests. <strong>Words are shown by themselves, never the questions.</strong></p>
        </div>

        @if ($words->isEmpty())
            <p style="text-align:center">The spelling list is being prepared. Please check back soon.</p>
        @else
            <div class="vb-bar">
                <input type="search" id="sp-search" placeholder="Search words…" aria-label="Search words">
                <div class="vb-actions"><button type="button" class="vb-btn is-primary" id="sp-practise">Practise spelling</button></div>
            </div>
            <div class="sp-az" id="sp-az" aria-label="Filter by first letter"></div>
            <p class="vb-count" id="sp-count"></p>
            <div id="sp-list"></div>
            <div id="sp-quiz" hidden></div>
        @endif
    </div>
</section>
<script>window.SPELL = {words: @json($words), order: @json(\App\Support\SpellingCollector::CATEGORIES)};</script>
<script src="{{ asset('js/vocab-spellings.js') }}?v=4" defer></script>
@endsection
