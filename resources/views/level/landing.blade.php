@extends('layouts.app')

@section('title', 'Free English Level Tests – Find Your CEFR Level (A1 to C2) – ' . config('app.name'))
@section('description', 'Free English level tests. Answer a short test and find your CEFR level from A1 to C2, with the correct answers shown after you finish.')

@push('head')<link rel="stylesheet" href="{{ asset('css/tools.css') }}?v=4">@endpush

@section('content')
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">English Level Tests</span>
            <h1>Find your English level</h1>
            <p>Choose a test, answer the questions and see your level from A1 to C2 in a few minutes. You get the correct answers at the end.</p>
        </div>

        <div class="tool-grid">
            @forelse ($tests as $t)
                <a class="tool-card" href="{{ route('level-test.show', $t->slug) }}">
                    <h3>{{ $t->title }}</h3>
                    <p>{{ $t->meta_description ? \Illuminate\Support\Str::limit($t->meta_description, 140) : 'Check your English level from A1 to C2.' }}</p>
                    <p><strong>{{ $t->count }} questions</strong> · 3 sections · A1 to C2</p>
                    <span class="tool-card__go">Take the test <span aria-hidden="true">→</span></span>
                </a>
            @empty
                <p style="grid-column:1/-1;text-align:center">No tests are available yet. Please check back soon.</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
