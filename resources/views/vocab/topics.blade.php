@extends('layouts.app')

@php $label = ucfirst($skill); $name = \App\Support\VocabLists::EXAMS[$exam]; @endphp
@section('title', $name . ' ' . $label . ' Vocabulary by Topic – ' . config('app.name'))
@section('description', 'Useful ' . $name . ' ' . $label . ' words, collocations and phrases by topic, with meanings, examples, translations, flashcards and exercises.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=7">@endpush

@section('content')
<section class="section">
    <div class="container">
        <x-breadcrumbs :items="[['Vocabulary Builder'], [$name, route('vocab.' . $exam)], [$label]]" />
        <div class="section-head">
            <span class="eyebrow">{{ $name }} {{ $label }}</span>
            <h1>{{ $label }} vocabulary by topic</h1>
            <p>Choose a topic to learn its words and phrases.</p>
        </div>
        @if ($items)
            <div class="tool-grid vb-grid">
                @foreach ($items as $row)
                    <a class="tool-card" href="{{ $row['url'] }}">
                        <h3>{{ $row['label'] }}</h3>
                        <p><strong>{{ $row['count'] }}</strong> words and phrases</p>
                        <span class="tool-card__go">Study these words <span aria-hidden="true">→</span></span>
                    </a>
                @endforeach
            </div>
        @else
            <p style="text-align:center">Topics for this skill are being prepared. Please check back soon.</p>
        @endif
    </div>
</section>
@endsection
