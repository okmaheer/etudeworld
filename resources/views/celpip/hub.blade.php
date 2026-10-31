@extends('layouts.app')

@php
    // Section order on this page (as requested): mock test, then Reading, Listening, Writing, Speaking.
    $order = array_values(array_filter(['reading', 'listening', 'writing', 'speaking'], fn ($m) => in_array($m, $modules, true)));
    $preview = 3;
    $isLs = $format === 'ls';
@endphp

@php $seo = \App\Support\SeoPages::resolve($isLs ? 'celpip-ls' : 'celpip-general', 'hub'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@section('content')
    <section class="celpip-hero">
        <div class="container">
            <x-breadcrumbs :items="[['CELPIP', route('celpip')], [$label]]" />
            <h1>{{ $seo['h1'] }}</h1>
            <p class="lead">{{ $seo['intro'] }}</p>
            <div class="celpip-jump">
                <a href="#mock">Full mock test</a>
                @foreach ($order as $m)
                    <a href="#{{ $m }}">{{ \App\Models\CelpipTest::PUBLIC_MODULES[$m]['label'] }}</a>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $sections = [
            'mock' => [
                'title' => 'Full ' . $label . ' Mock Test',
                'icon'  => 'layers',
                'url'   => route('celpip.practice.mocks', $format),
                'btn'   => 'All mock tests',
                'about' => 'Sit a complete ' . $label . ' exam from start to finish, with every section in the real order ('
                    . implode(' → ', array_map(fn ($m) => \App\Models\CelpipTest::PUBLIC_MODULES[$m]['label'], array_values(array_filter(array_keys(\App\Models\CelpipTest::PUBLIC_MODULES), fn ($m) => in_array($m, $modules, true))))) . ').'
                    . ' When you finish one section, the next one starts, just like test day. It\'s the best way to build stamina and find out where you stand.',
                'tags'  => [count($modules) . ' sections', 'Real exam order', $isLs ? 'About 1 hour 10 min' : 'About 3 hours', 'Timed tasks', 'Section-by-section progress', 'Review your answers'],
            ],
            'reading' => [
                'about' => 'Read correspondence, diagrams, information texts and viewpoints, the four kinds of passages in the real test, then answer questions about each one. Learn to find key details quickly and manage your time.',
                'tags'  => ['4 parts', '38 questions', 'About 55 minutes', 'Multiple choice', 'Diagrams & emails', 'Opinion texts'],
            ],
            'listening' => [
                'about' => 'Listen to everyday conversations, a news item, a discussion and viewpoints, then answer questions about what you heard. Practise catching main ideas, details and the speaker\'s attitude.',
                'tags'  => ['6 parts', '38 questions', 'About 47 minutes', 'Audio & video clips', 'Multiple choice', 'Played once only'],
            ],
            'writing' => [
                'about' => 'Write an email (Task 1), then respond to a survey by choosing one of two options and explaining your choice (Task 2). Each task has its own timer and a live word counter, so you can practise reaching 150–200 words in time.',
                'tags'  => ['2 tasks', '53 minutes', 'Email writing', 'Survey response', 'Choose Option A or B', 'Live word counter'],
            ],
            'speaking' => [
                'about' => 'Answer eight short speaking tasks: give advice, talk about a personal experience, describe scenes, make predictions, compare options and express opinions. Each task gives you preparation time, then a set time to speak.',
                'tags'  => ['8 tasks', 'About 20 minutes', 'Preparation time', 'Picture description', 'Opinions & advice', 'Timed responses'],
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
                        $info  = $key === 'mock' ? null : \App\Models\CelpipTest::PUBLIC_MODULES[$key];
                        $title = $sec['title'] ?? 'Practice ' . $info['label'];
                        $icon  = $sec['icon'] ?? $info['icon'];
                        $url   = $sec['url'] ?? route('celpip.practice.module', [$format, $key]);
                        $btn   = $sec['btn'] ?? 'All ' . strtolower($info['label']) . ' tests';
                        $readyCount = $key === 'mock'
                            ? $tests->filter(fn ($t) => collect($t->examModules())->contains(fn ($m) => $t->moduleReady($m)))->count()
                            : $tests->filter(fn ($t) => $t->moduleReady($key))->count();
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
