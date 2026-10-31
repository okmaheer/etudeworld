@extends('layouts.app')

@php $seo = \App\Support\SeoPages::landing('celpip'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@php use App\Models\CelpipTest; @endphp

@section('content')

    {{-- ═════════════ HERO ═════════════ --}}
    <section class="hero cp-hero">
        <div class="container hero-grid">
            <div class="hero-text">
                <span class="eyebrow">CELPIP</span>
                <h1>Get ready for <span class="hl">CELPIP</span> with tests that feel like the <span class="hl">real exam</span></h1>
                <p class="lead">
                    Practise Listening, Reading, Writing and Speaking on screens built like the official computer-based test,
                    with real timers, instant Listening and Reading scores, and Writing and Speaking evaluated by a teacher.
                </p>
                <div class="hero-actions">
                    <a href="#celpip-general" class="btn btn-primary">CELPIP General</a>
                    <a href="#celpip-ls" class="btn btn-ghost">CELPIP LS</a>
                </div>
                <ul class="hero-stats">
                    <li><strong>4 skills</strong><span>Listening · Reading · Writing · Speaking</span></li>
                    <li><strong>Real timing</strong><span>same timers as the test</span></li>
                    <li><strong>CLB levels</strong><span>see where you stand</span></li>
                </ul>
            </div>
            <div class="cp-hero-art" aria-hidden="true">
                <div class="cp-mock">
                    <div class="cp-mock__bar"><span></span><span></span><span></span><em>CELPIP Practice Test</em></div>
                    <div class="cp-mock__body">
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--l">L</b><div><strong>Listening</strong><small>6 parts · about 47 min</small></div><i>CLB 9</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--r">R</b><div><strong>Reading</strong><small>4 parts · about 55 min</small></div><i>CLB 8</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--w">W</b><div><strong>Writing</strong><small>2 tasks · 53 min</small></div><i class="is-wait">Teacher</i></div>
                        <div class="cp-mock__row"><b class="cp-ic cp-ic--s">S</b><div><strong>Speaking</strong><small>8 tasks · about 20 min</small></div><i class="is-wait">Teacher</i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═════════════ CELPIP GENERAL ═════════════ --}}
    <section class="section cp-exam" id="celpip-general">
        <div class="container cp-exam__grid">
            <div class="cp-exam__text">
                <span class="eyebrow">For permanent residence &amp; work</span>
                <h2>CELPIP General Exam</h2>
                <p>CELPIP-General tests all four skills. It is accepted by Immigration, Refugees and Citizenship Canada (IRCC)
                    for permanent residence and many work and immigration programs.</p>
                <ul class="cp-points">
                    <li>Full mock tests in the real exam order</li>
                    <li>Practise one skill at a time when you want</li>
                    <li>Listening &amp; Reading marked instantly with your CELPIP and CLB level</li>
                    <li>Writing &amp; Speaking checked by a teacher – results in your portal</li>
                </ul>
                <a href="{{ route('celpip.general') }}" class="btn btn-primary btn-lg">Practise CELPIP General <span aria-hidden="true">→</span></a>
                @if ($generalCount) <p class="cp-count">{{ $generalCount }} practice {{ $generalCount === 1 ? 'test' : 'tests' }} available</p> @endif
            </div>
            <div class="cp-skills">
                @foreach (CelpipTest::PUBLIC_MODULES as $key => $m)
                    <div class="cp-skill">
                        <span class="cp-skill__icon">@include('admin.partials.icon', ['name' => $m['icon']])</span>
                        <strong>{{ $m['label'] }}</strong>
                        <small>{{ $m['parts'] }} · {{ $m['time'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═════════════ CELPIP LS ═════════════ --}}
    <section class="section section--tint cp-exam cp-exam--ls" id="celpip-ls">
        <div class="container cp-exam__grid">
            <div class="cp-skills cp-skills--two">
                @foreach (['listening', 'speaking'] as $key)
                    @php $m = CelpipTest::PUBLIC_MODULES[$key]; @endphp
                    <div class="cp-skill">
                        <span class="cp-skill__icon">@include('admin.partials.icon', ['name' => $m['icon']])</span>
                        <strong>{{ $m['label'] }}</strong>
                        <small>{{ $m['parts'] }} · {{ $m['time'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="cp-exam__text">
                <span class="eyebrow">For Canadian citizenship</span>
                <h2>CELPIP LS Exam</h2>
                <p>CELPIP-LS tests only Listening and Speaking. It is used to show English language ability for Canadian citizenship.</p>
                <ul class="cp-points">
                    <li>Listening practice with all six parts and real timers</li>
                    <li>Speaking practice with all eight tasks, recorded in your browser</li>
                    <li>Speaking evaluated by a teacher – results in your portal</li>
                </ul>
                <a href="{{ route('celpip.ls') }}" class="btn btn-primary btn-lg">Practise CELPIP LS <span aria-hidden="true">→</span></a>
                @if ($lsCount) <p class="cp-count">{{ $lsCount }} practice {{ $lsCount === 1 ? 'test' : 'tests' }} available</p> @endif
            </div>
        </div>
    </section>

@endsection
