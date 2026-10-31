@extends('layouts.app')

@section('title', 'IELTS & CELPIP Sample Answers – ' . config('app.name'))
@section('description', 'Model answers for IELTS Speaking and Writing and CELPIP Speaking and Writing, task by task, with explanations of what makes them score well.')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=6">
    <link rel="canonical" href="{{ route('sample') }}">
@endpush

@section('content')
    <section class="celpip-hero">
        <div class="container">
            <nav class="breadcrumbs breadcrumbs--left" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a><span aria-hidden="true">›</span><span>Sample Answers</span>
            </nav>
            <h1>Sample Answers</h1>
            <p class="lead">See what a high-scoring answer looks like. Pick an exam, a skill and a task to read model answers you can learn from.</p>
        </div>
    </section>

    <section class="section section--compact">
        <div class="container sa-groups">
            @foreach ($tree['children'] as $exam)
                <div class="sa-exam">
                    <h2>{{ $exam['label'] }}</h2>
                    @foreach ($exam['children'] as $skill)
                        <div class="sa-skill">
                            <h3>{{ $skill['label'] }}</h3>
                            <ul class="sa-links">
                                @foreach ($skill['children'] as $item)
                                    @php
                                        $key = 'sa-' . $exam['slug'] . '-' . $skill['slug'] . '-' . $item['slug'];
                                        $n = $counts[$key] ?? 0;
                                    @endphp
                                    <li>
                                        <a href="{{ url($categories[$key]['path']) }}">
                                            <span>{{ $item['label'] }}</span>
                                            <small>{{ $n ? $n . ' ' . \Illuminate\Support\Str::plural('answer', $n) : 'Coming soon' }}</small>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
@endsection
