@extends('layouts.app')

@php $name = \App\Support\VocabLists::EXAMS[$exam]; @endphp
@section('title', $name . ' Vocabulary Builder – ' . config('app.name'))
@section('description', $name . ' vocabulary from real practice tests and topics: Listening, Reading, Writing and Speaking words with meanings, examples, translations, flashcards and exercises.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=7">@endpush

@section('content')
<section class="section">
    <div class="container">
        <x-breadcrumbs :items="[['Vocabulary Builder'], [$name]]" />
        <div class="section-head">
            <span class="eyebrow">{{ $name }} Vocabulary</span>
            <h1>Choose a skill</h1>
            <p>Pick the part of the test you want to build vocabulary for.</p>
        </div>
        <div class="tool-grid">
            <a class="tool-card" href="{{ route("vocab.$exam.listening") }}"><h3>Listening vocabulary</h3><p>Words and phrases from {{ $name }} Listening tests.</p><span class="tool-card__go">Open <span aria-hidden="true">→</span></span></a>
            <a class="tool-card" href="{{ route("vocab.$exam.reading") }}"><h3>Reading vocabulary</h3><p>Words and phrases from {{ $name }} Reading tests.</p><span class="tool-card__go">Open <span aria-hidden="true">→</span></span></a>
            <a class="tool-card" href="{{ route("vocab.$exam.writing") }}"><h3>Writing vocabulary</h3><p>Useful words, phrases and collocations by topic.</p><span class="tool-card__go">Open <span aria-hidden="true">→</span></span></a>
            <a class="tool-card" href="{{ route("vocab.$exam.speaking") }}"><h3>Speaking vocabulary</h3><p>Natural spoken phrases and idioms by topic.</p><span class="tool-card__go">Open <span aria-hidden="true">→</span></span></a>
            @if ($exam === 'ielts')
                <a class="tool-card" href="{{ route('vocab.ielts.spellings') }}"><h3>Important spellings for Listening</h3><p>The words you must spell correctly in IELTS Listening, with spelling practice.</p><span class="tool-card__go">Open <span aria-hidden="true">→</span></span></a>
            @endif
        </div>
    </div>
</section>
@endsection
