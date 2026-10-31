@extends('layouts.app')

@php $seo = \App\Support\SeoPages::landing('ielts'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@php
    $skills = [
        ['icon' => 'headphones', 'label' => 'Listening', 'info' => '4 parts · 32 minutes',     'same' => true],
        ['icon' => 'pen',        'label' => 'Reading',   'info' => '3 sections · 60 minutes',  'same' => false],
        ['icon' => 'page',       'label' => 'Writing',   'info' => '2 tasks · 60 minutes',     'same' => false],
        ['icon' => 'mic',        'label' => 'Speaking',  'info' => '3 parts · 11–14 minutes',  'same' => true],
    ];
@endphp

@section('content')

    {{-- ═════════════ HERO ═════════════ --}}
    <section class="hero cp-hero ip-hero">
        <div class="container hero-grid">
            <div class="hero-text">
                <span class="eyebrow">IELTS</span>
                <h1>Reach your target <span class="hl">IELTS band</span> with realistic practice</h1>
                <p class="lead">
                    Practise Listening, Reading, Writing and Speaking for IELTS Academic and IELTS General Training,
                    with real timing, instant Listening and Reading scores, and Writing and Speaking evaluated by a teacher.
                </p>
                <div class="hero-actions">
                    <a href="#ielts-academic" class="btn btn-primary">IELTS Academic</a>
                    <a href="#ielts-general" class="btn btn-ghost">IELTS General Training</a>
                </div>
                <ul class="hero-stats">
                    <li><strong>4 skills</strong><span>Listening · Reading · Writing · Speaking</span></li>
                    <li><strong>Real timing</strong><span>same timers as the test</span></li>
                    <li><strong>Band scores</strong><span>see where you stand</span></li>
                </ul>
            </div>
            <div class="cp-hero-art" aria-hidden="true">
                <div class="cp-mock">
                    <div class="cp-mock__bar"><span></span><span></span><span></span><em>IELTS Practice Test</em></div>
                    <div class="cp-mock__body">
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--l">L</b><div><strong>Listening</strong><small>4 parts · 32 min</small></div><i>Band 7.5</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--r">R</b><div><strong>Reading</strong><small>3 sections · 60 min</small></div><i>Band 7.0</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--w">W</b><div><strong>Writing</strong><small>2 tasks · 60 min</small></div><i class="is-wait">Teacher</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--s">S</b><div><strong>Speaking</strong><small>3 parts · 11–14 min</small></div><i class="is-wait">Teacher</i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═════════════ IELTS ACADEMIC ═════════════ --}}
    <section class="section cp-exam ip-exam" id="ielts-academic">
        <div class="container cp-exam__grid">
            <div class="cp-exam__text">
                <span class="eyebrow">For university &amp; professional registration</span>
                <h2>IELTS Academic</h2>
                <p>IELTS Academic is for people who want to study at university or college, or register with a professional body
                    (for example in medicine or nursing) in an English-speaking country.</p>
                <ul class="cp-points">
                    <li>Academic Reading with long texts from books, journals and newspapers</li>
                    <li>Writing Task 1: describe a graph, table, chart or diagram</li>
                    <li>Writing Task 2: an essay giving your opinion or discussing a topic</li>
                    <li>Listening &amp; Speaking – the same as General Training</li>
                </ul>
                <a href="{{ route('ielts.academic') }}" class="btn btn-primary btn-lg">Practise IELTS Academic <span aria-hidden="true">→</span></a>
            </div>
            <div class="cp-skills">
                @foreach ($skills as $sk)
                    <div class="cp-skill">
                        <span class="cp-skill__icon">@include('admin.partials.icon', ['name' => $sk['icon']])</span>
                        <strong>{{ $sk['label'] }}</strong>
                        <small>{{ $sk['info'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═════════════ IELTS GENERAL TRAINING ═════════════ --}}
    <section class="section section--tint cp-exam cp-exam--ls ip-exam" id="ielts-general">
        <div class="container cp-exam__grid">
            <div class="cp-skills">
                @foreach ($skills as $sk)
                    <div class="cp-skill">
                        <span class="cp-skill__icon">@include('admin.partials.icon', ['name' => $sk['icon']])</span>
                        <strong>{{ $sk['label'] }}</strong>
                        <small>{{ $sk['info'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="cp-exam__text">
                <span class="eyebrow">For migration, work &amp; training</span>
                <h2>IELTS General Training</h2>
                <p>IELTS General Training is for people moving to an English-speaking country such as Canada, Australia, New Zealand
                    or the UK for work, training or permanent residence.</p>
                <ul class="cp-points">
                    <li>General Reading with everyday texts: notices, adverts, work documents</li>
                    <li>Writing Task 1: a letter (formal, semi-formal or informal)</li>
                    <li>Writing Task 2: an essay giving your opinion or discussing a topic</li>
                    <li>Listening &amp; Speaking – the same as Academic</li>
                </ul>
                <a href="{{ route('ielts.general') }}" class="btn btn-primary btn-lg">Practise IELTS General Training <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

@endsection
