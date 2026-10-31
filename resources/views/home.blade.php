@extends('layouts.app')

@push('head')
    <style>
        /* Hero picture: a little smaller and pushed to the right (on phones it stays centred) */
        @media (min-width: 901px) { .hero-art img { width: 84%; margin-left: auto; display: block; } }
    </style>
@endpush

@push('head')
    {!! \App\Support\SchemaMarkup::home() !!}
@endpush

@section('content')

    {{-- ═════════════ HERO ═════════════ --}}
    <section class="hero hero--home">
        <div class="container hero-grid">
            <div class="hero-text">
                @if ($hero['eyebrow'] !== '') <span class="eyebrow">{{ $hero['eyebrow'] }}</span> @endif
                <h1>{!! \App\Models\SiteSetting::highlight($hero['heading']) !!}</h1>
                @if ($hero['text'] !== '') <p class="lead">{{ $hero['text'] }}</p> @endif
                <div class="hero-actions">
                    <a href="#exams" class="btn btn-primary">{{ $hero['button1'] }}</a>
                    <a href="#methods" class="btn btn-ghost">{{ $hero['button2'] }}</a>
                </div>
                @if (count($hero['stats']))
                    <ul class="hero-stats">
                        @foreach ($hero['stats'] as $st)
                            <li><strong>{{ $st['big'] }}</strong><span>{{ $st['small'] }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="hero-art">
                <img src="{{ asset('images/hero-art.svg') }}" alt="A student studying at a computer, with language speech bubbles and IELTS, TOEFL and CELPIP books" width="672" height="436">
            </div>
        </div>
    </section>

    {{-- ═════════════ SECTION 1: Learn a New Language ═════════════ --}}
    <section class="section home-section" id="languages">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Languages</span>
                <h2>Learn a New Language</h2>
                <p>Start from the basics or polish what you already know, with lessons built around real-life conversations.</p>
            </div>

            <div class="card-grid card-grid--4">
                @foreach ($languages as $lang)
                    @if (! empty($lang['url']))
                        <a href="{{ $lang['url'] }}" class="card lang-card" style="text-decoration:none;color:inherit">
                            <span class="lang-badge">{{ $lang['flag'] }}</span>
                            <h3>{{ $lang['name'] }}</h3>
                            <p>{{ $lang['text'] }}</p>
                            <span class="card-link">Start learning →</span>
                        </a>
                    @else
                        <article class="card lang-card">
                            <span class="lang-badge">{{ $lang['flag'] }}</span>
                            <h3>{{ $lang['name'] }}</h3>
                            <p>{{ $lang['text'] }}</p>
                        </article>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═════════════ SECTION 2: Practice a Language Exam ═════════════ --}}
    <section class="section section--tint home-section" id="exams">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Exam preparation</span>
                <h2>Practice a Language Exam</h2>
                <p>Realistic practice for every part of the test: Listening, Reading, Writing and Speaking.</p>
            </div>

            <div class="exam-grid">
                @foreach ($exams as $ex)
                    <div class="exam-card exam-card--{{ $ex['key'] }}">
                        <h3>{{ $ex['title'] }}</h3>
                        <p>{{ $ex['text'] }}</p>
                        <a href="{{ route($ex['key']) }}" class="btn btn-exam">{{ $ex['button'] }} <span aria-hidden="true">→</span></a>
                    </div>
                @endforeach
            </div>

            <div class="level-banner">
                <div>
                    <h3>{{ $level['title'] }}</h3>
                    <p>{{ $level['text'] }}</p>
                </div>
                <a href="{{ route('level-test.index') }}" class="btn btn-primary">{{ $level['button'] }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    {{-- ═════════════ Courses (paid) ═════════════ --}}
    <section class="section home-section" id="courses">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Courses</span>
                <h2>Learn with an Expert Tutor</h2>
                <p>Live classes, computer based mock tests and recorded lessons for IELTS, CELPIP and TOEFL.</p>
            </div>
            <div class="home-courses">
                @foreach ([['ielts', 'IELTS Courses', 'Listening, Reading, Writing, Speaking and a complete preparation course.'], ['celpip', 'CELPIP Courses', 'Courses for every CELPIP task, plus computer based mock tests.'], ['toefl', 'TOEFL Courses', 'Prepare for the TOEFL iBT with live classes and mock tests.']] as [$k, $t, $d])
                    <div class="home-course">
                        <h3>{{ $t }}</h3>
                        <p>{{ $d }}</p>
                        <a href="{{ route('courses.index') }}#{{ $k }}">View {{ strtoupper($k) }} courses <span aria-hidden="true">→</span></a>
                    </div>
                @endforeach
            </div>
            <div class="home-courses-cta"><a href="{{ route('courses.index') }}" class="btn btn-primary">Book a course <span aria-hidden="true">→</span></a></div>
        </div>
    </section>

    {{-- ═════════════ SECTION 3: Effective Study Methods ═════════════ --}}
    <section class="section home-section" id="methods">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Study smarter</span>
                <h2>Effective Study Methods for Best Results</h2>
                <p>Studying longer isn't the secret. Studying smarter is. These are the techniques we build every lesson around.</p>
            </div>

            <div class="card-grid card-grid--3">
                @foreach ($methods as $m)
                    <article class="card method-card">
                        <span class="method-icon" aria-hidden="true">
                            @include('partials.method-icon', ['icon' => $m['icon']])
                        </span>
                        <h3>{{ $m['title'] }}</h3>
                        <p>{{ $m['text'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="center">
                <a href="{{ route('blog.study') }}" class="btn btn-primary">Read more about study methods</a>
            </div>
        </div>
    </section>

    {{-- ═════════════ SECTION 4: Our Achievements (slideshow) ═════════════ --}}
    @if (count($achievements))
    <section class="section home-section home-achievements" id="achievements">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Success stories</span>
                <h2>Our Achievements</h2>
                <p>Real progress from students who practised with us.</p>
            </div>

            <div class="slider" data-slider data-interval="5000" aria-roledescription="carousel" aria-label="Student achievements">
                <div class="slider-track">
                    @foreach ($achievements as $i => $a)
                        <figure class="slide {{ $i === 0 ? 'is-active' : '' }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($achievements) }}">
                            <div class="slide-photo">
                                <img src="{{ asset($a['photo']) }}" alt="Photo of {{ $a['name'] }}" width="180" height="180" loading="lazy">
                                <span class="score-badge">{{ $a['score'] }}</span>
                            </div>
                            <div class="slide-body">
                                <blockquote>“{{ $a['text'] }}”</blockquote>
                                <figcaption>
                                    <strong>{{ $a['name'] }}</strong>
                                    <span>{{ $a['exam'] }} · {{ $a['score'] }}</span>
                                </figcaption>
                            </div>
                        </figure>
                    @endforeach
                </div>

                <div class="slider-controls">
                    <button class="slider-btn" type="button" data-prev aria-label="Previous slide">
                        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="slider-dots" role="tablist">
                        @foreach ($achievements as $i => $a)
                            <button type="button" class="dot {{ $i === 0 ? 'is-active' : '' }}" data-dot="{{ $i }}" aria-label="Go to slide {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                    <button class="slider-btn" type="button" data-next aria-label="Next slide">
                        <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>
    @endif

@endsection
