@extends('layouts.app')

@php $label = ucfirst($module); $name = \App\Support\VocabLists::EXAMS[$exam]; @endphp
@section('title', $name . ' ' . $label . ' Vocabulary – ' . config('app.name'))
@section('description', 'Choose a ' . $name . ' ' . $label . ' practice test and learn its difficult words with meanings, examples and translations.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4"><link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=7">@endpush

@section('content')
<section class="section">
    <div class="container">
        <x-breadcrumbs :items="[['Vocabulary Builder'], [$name, route('vocab.' . $exam)], [$label]]" />
        <div class="section-head">
            <span class="eyebrow">{{ $name }} {{ $label }}</span>
            <h1>{{ $label }} vocabulary by test</h1>
            <p>Choose a test to see its words and phrases.</p>
        </div>
        @forelse ($groups as $title => $rows)
            <h2 class="vb-h2">{{ $title }}</h2>
            <div class="tool-grid vb-grid">
                @foreach ($rows as $row)
                    <a class="tool-card" href="{{ $row['url'] }}">
                        <h3>{{ $row['label'] }}</h3>
                        <p><strong>{{ $row['count'] }}</strong> words and phrases</p>
                        <span class="tool-card__go">Study these words <span aria-hidden="true">→</span></span>
                    </a>
                @endforeach
            </div>
        @empty
            <p style="text-align:center">Words for this skill are being prepared. Please check back soon.</p>
        @endforelse
    </div>
</section>
@endsection
