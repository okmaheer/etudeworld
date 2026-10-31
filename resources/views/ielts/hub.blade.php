@extends('layouts.app')

{{-- /ielts/academic and /ielts/general-training: the five sections, like the CELPIP practice page. --}}
@php
    use App\Models\IeltsTest;
    $isGt  = $format === 'general-training';
    $order = array_keys(IeltsTest::PUBLIC_MODULES);
@endphp

@php $seo = \App\Support\SeoPages::resolve($isGt ? 'ielts-gt' : 'ielts-academic', 'hub'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@section('content')
    <section class="celpip-hero ielts-hero">
        <div class="container">
            <x-breadcrumbs :items="[['IELTS', route('ielts')], [$label]]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
            <div class="celpip-jump">
                <a href="#mock">Full mock test</a>
                @foreach ($order as $m)
                    <a href="#{{ $m }}">{{ IeltsTest::PUBLIC_MODULES[$m]['label'] }}</a>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $sections = [
            'mock' => [
                'title' => 'Full ' . $label . ' Mock Test',
                'icon'  => 'layers',
                'url'   => route('ielts.practice.mocks', $format),
                'btn'   => 'All mock tests',
                'about' => 'Sit a complete ' . $label . ' exam from start to finish, in the real order (Listening → Reading → Writing → Speaking). '
                    . 'When you finish one section, the next one starts, just like test day. It\'s the best way to build stamina and find out where you stand.',
                'tags'  => ['4 sections', 'Real exam order', 'About 2 hours 45 min', 'Timed sections', 'Band scores', 'Review your answers'],
            ],
            'listening' => [
                'about' => 'Listen to four recordings: an everyday conversation, a talk about local facilities, a discussion between students and an academic lecture. Answer 40 questions while you listen.',
                'tags'  => ['4 parts', '40 questions', '32 minutes', 'Form & note completion', 'Multiple choice', 'Played once only'],
            ],
            'reading' => [
                'about' => $isGt
                    ? 'Read everyday texts such as notices and advertisements, work-related texts, and one longer general-interest passage. Answer 40 questions in 60 minutes.'
                    : 'Read three long passages taken from books, journals, magazines and newspapers. Answer 40 questions in 60 minutes and learn to skim and scan quickly.',
                'tags'  => $isGt
                    ? ['3 sections', '40 questions', '60 minutes', 'Everyday texts', 'Workplace texts', 'True / False / Not Given']
                    : ['3 passages', '40 questions', '60 minutes', 'Academic texts', 'Matching headings', 'True / False / Not Given'],
            ],
            'writing' => [
                'about' => $isGt
                    ? 'Task 1: write a letter (formal, semi-formal or informal) of 100–200 words. Task 2: write an essay of 180–350 words, giving your opinion on a topic. You have 1 hour for both tasks. Your answers are evaluated by a teacher.'
                    : 'Task 1: write a report describing a graph, chart, table, map or process in 100–200 words. Task 2: write an essay of 180–350 words, giving your opinion on a topic. You have 1 hour for both tasks. Your answers are evaluated by a teacher.',
                'tags'  => $isGt
                    ? ['2 tasks', '1 hour', 'Letter writing', 'Essay writing', 'Live word counter', 'Teacher evaluation']
                    : ['2 tasks', '1 hour', 'Report writing', 'Essay writing', 'Live word counter', 'Teacher evaluation'],
            ],
            'speaking' => [
                'about' => 'Part 1: answer questions about yourself. Part 2: speak about a topic card for up to 2 minutes after 1 minute of preparation. Part 3: discuss deeper questions on the same topic. Your answers are recorded and evaluated by a teacher.',
                'tags'  => ['3 parts', '11–14 minutes', 'Questions played as audio', 'Topic card', '1 minute preparation', 'Teacher evaluation'],
            ],
        ];
        $keys = array_merge(['mock'], $order);
    @endphp

    <section class="hub-wrap">
        <div class="container">
            <div class="hub-panel">
                @foreach ($keys as $key)
                    @php
                        $sec   = $sections[$key];
                        $info  = $key === 'mock' ? null : IeltsTest::PUBLIC_MODULES[$key];
                        $title = $sec['title'] ?? 'Practice ' . $info['label'];
                        $icon  = $sec['icon'] ?? $info['icon'];
                        $url   = $sec['url'] ?? route('ielts.practice.module', [$format, $key]);
                        $btn   = $sec['btn'] ?? 'All ' . strtolower($info['label']) . ' tests';
                        $readyCount = $key === 'mock' ? 0 : $tests->filter(fn ($t) => $t->moduleReady($key))->count();
                    @endphp
                    <div class="hub-item hub-item--{{ $key }}" id="{{ $key }}">
                        <div class="hub-item__head">
                            <span class="hub-item__icon">@include('admin.partials.icon', ['name' => $icon])</span>
                            <h2>{{ $title }}</h2>
                        </div>
                        <p class="hub-item__text">{{ $sec['about'] }}</p>
                        <ul class="feature-tags" aria-label="Features">
                            @foreach ($sec['tags'] as $t => $tag)
                                <li class="feature-tag feature-tag--{{ $t % 6 }}">{{ $tag }}</li>
                            @endforeach
                        </ul>
                        <div class="hub-item__foot">
                            <a href="{{ $url }}" class="btn btn-primary">{{ $btn }} @include('admin.partials.icon', ['name' => 'arrow'])</a>
                            <small>{{ $readyCount ? $readyCount . ' ' . \Illuminate\Support\Str::plural('test', $readyCount) . ' ready to take' : 'Coming soon' }}</small>
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
