@extends('layouts.app')

@section('title', 'Vocabulary Builder – IELTS and CELPIP Words with Meanings and Translations – ' . config('app.name'))
@section('description', 'Learn the difficult words from real IELTS Listening and Reading practice tests, with simple meanings, examples, translations in 10 languages, flashcards and exercises.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=1">@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Vocabulary Builder</span>
            <h1>Learn the words that matter in your test</h1>
            <p>Words and phrases from our practice tests, with simple meanings, examples and translations in 10 languages. Study them as a list, flashcards or exercises.</p>
        </div>
        <div class="tool-grid">
            <a class="tool-card" href="{{ route('vocab.ielts') }}">
                <h3>IELTS Vocabulary</h3>
                <p>Words from IELTS Listening and Reading practice tests, plus Writing and Speaking topics, with flashcards, exercises and a PDF to download.</p>
                <span class="tool-card__go">Open <span aria-hidden="true">→</span></span>
            </a>
            <a class="tool-card" href="{{ route('vocab.celpip') }}">
                <h3>CELPIP Vocabulary</h3>
                <p>Words from CELPIP Listening and Reading practice tests, plus Writing and Speaking topics.</p>
                <span class="tool-card__go">Open <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </div>
</section>
@endsection
