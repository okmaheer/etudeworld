@extends('layouts.app')
@php use App\Support\Toefl; $order = array_keys(Toefl::PUBLIC); @endphp
@php $seo = \App\Support\SeoPages::resolve('toefl', 'hub'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])
@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush
@section('content')
    <section class="celpip-hero ielts-hero">
        <div class="container">
            <x-breadcrumbs :items="[['TOEFL', route('toefl')], ['TOEFL iBT 2026']]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
            <div class="celpip-jump">
                <a href="#mock">Full mock test</a>
                @foreach ($order as $m)<a href="#{{ $m }}">{{ Toefl::PUBLIC[$m]['label'] }}</a>@endforeach
            </div>
        </div>
    </section>
    @php
        $sections = [
            'mock' => ['title' => 'Full TOEFL iBT Mock Test', 'icon' => 'layers', 'url' => route('toefl.mocks'), 'btn' => 'All mock tests',
                'about' => 'Sit a complete TOEFL iBT from start to finish: Reading → Listening → Writing → Speaking. When you finish one section the next starts, and you cannot go back, just like test day.',
                'tags' => ['4 sections', 'Real exam order', 'About 90 minutes', 'Timed sections', 'No going back', 'Review your answers']],
            'reading' => ['about' => 'Complete the words in a paragraph, read everyday texts such as notices, emails and posts, and read short academic passages. Two modules, 30 minutes in total.',
                'tags' => ['2 modules', '35–48 questions', '30 minutes', 'Complete the Words', 'Daily life texts', 'Academic passages']],
            'listening' => ['about' => 'Choose the best response to short statements, then listen to conversations, announcements and academic talks. Recordings play once and the questions appear when the audio ends.',
                'tags' => ['2 modules', '35–45 questions', '30 minutes', 'Choose a Response', 'Conversations', 'Academic talks']],
            'writing' => ['about' => 'Build a sentence from words and phrases, write an email in 7 minutes and add your opinion to an academic discussion in 10 minutes. Your emails and discussion posts can be sent to a teacher for feedback.',
                'tags' => ['3 task types', '23 minutes', 'Build a Sentence', 'Write an Email', 'Academic Discussion', 'Teacher feedback']],
            'speaking' => ['about' => 'Listen and repeat seven sentences, then answer four interview questions. Your recordings are saved and can be sent to a teacher for a score and feedback.',
                'tags' => ['2 task types', '8 minutes', 'Listen and Repeat', 'Take an Interview', 'Recorded in your browser', 'Teacher feedback']],
        ];
    @endphp
    <section class="hub-wrap">
        <div class="container">
            <div class="hub-panel">
                @foreach (array_merge(['mock'], $order) as $key)
                    @php
                        $sec = $sections[$key]; $info = $key === 'mock' ? null : Toefl::PUBLIC[$key];
                        $readyCount = $key === 'mock' ? 0 : $tests->filter(fn ($t) => $t->moduleRow($key)?->slug && $t->moduleFilled($key))->count();
                    @endphp
                    <div class="hub-item hub-item--{{ $key }}" id="{{ $key }}">
                        <div class="hub-item__head"><span class="hub-item__icon">@include('admin.partials.icon', ['name' => $sec['icon'] ?? $info['icon']])</span><h2>{{ $sec['title'] ?? 'Practice ' . $info['label'] }}</h2></div>
                        <p class="hub-item__text">{{ $sec['about'] }}</p>
                        <ul class="feature-tags" aria-label="Features">@foreach ($sec['tags'] as $t => $tag)<li class="feature-tag feature-tag--{{ $t % 6 }}">{{ $tag }}</li>@endforeach</ul>
                        <div class="hub-item__foot">
                            <a href="{{ $sec['url'] ?? route('toefl.module', $key) }}" class="btn btn-primary">{{ $sec['btn'] ?? 'All ' . strtolower($info['label']) . ' tests' }} @include('admin.partials.icon', ['name' => 'arrow'])</a>
                            <small>{{ $readyCount ? $readyCount . ' ' . \Illuminate\Support\Str::plural('test', $readyCount) . ' ready to take' : ($key === 'mock' ? '' : 'Coming soon') }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if (! empty($seo['content']))
        <section class="section section--compact seo-extra">
            <div class="container"><div class="article-body">{!! $seo['content'] !!}</div></div>
        </section>
    @endif
@endsection
