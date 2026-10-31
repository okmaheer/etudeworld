@extends('layouts.app')

@section('title', ($page?->meta_title) ?: 'About Us – ' . config('app.name'))
@section('description', ($page?->meta_description) ?: 'Etude World offers language preparation resources and practice tests for IELTS, CELPIP and TOEFL, with vocabulary tools, sample answers and live courses.')

@push('head')
    {!! \App\Support\SchemaMarkup::page('AboutPage', 'About ' . config('app.name'), 'Language preparation resources and practice tests for IELTS, CELPIP and TOEFL.') !!}
@endpush

@section('content')
    {{-- The heading and the text under it can be changed in Admin → SEO Pages → About & contact --}}
    <section class="info-hero">
        <div class="container">
            <x-breadcrumbs :items="[['About Us']]" align="center" />
            <span class="eyebrow">About us</span>
            <h1>About {{ config('app.name') }}</h1>
            <p>We help students prepare for English language exams and learn new languages, with realistic practice tests, clear guidance and a study method that works.</p>
            <div class="info-hero__links">
                <a href="#what-we-offer" class="btn btn-ghost">What we offer</a>
                <a href="#why-choose-us" class="btn btn-ghost">Why choose us</a>
                <a href="#location" class="btn btn-ghost">Where we are</a>
            </div>
        </div>
    </section>

    {{-- ═════════ What we offer ═════════ --}}
    <section class="section about-section" id="what-we-offer">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">What we offer</span>
                <h2>Everything you need to prepare, in one place</h2>
                <p>{{ config('app.name') }} offers language preparation resources and practice tests for IELTS, CELPIP and TOEFL. Our tests look and feel like the real computer-based exams, so you walk into test day knowing exactly what to expect. Alongside the tests, you get vocabulary tools, model answers, study guides and live courses.</p>
            </div>
            <div class="card-grid card-grid--3">
                @foreach ([
                    ['badge', 'IELTS', 'IELTS practice tests', 'Academic and General Training tests for Listening, Reading, Writing and Speaking, in a computer-based interface that matches the real exam.', route('ielts')],
                    ['badge', 'CELPIP', 'CELPIP practice tests', 'CELPIP General and LS practice built around the real test format, with timed sections for your Canadian immigration goals.', route('celpip')],
                    ['badge', 'TOEFL', 'TOEFL iBT practice tests', 'Reading, Listening, Speaking and Writing practice for the latest TOEFL iBT format, for study abroad.', route('toefl')],
                    ['icon', 'pencil', 'Writing and speaking feedback', 'Submit your writing and speaking answers and get them reviewed, so you know exactly what to improve.', null],
                    ['icon', 'brain', 'Vocabulary and sample answers', 'Topic-based vocabulary lists with exercises, plus sample answers for IELTS and CELPIP writing and speaking tasks.', route('vocab.ielts')],
                    ['icon', 'timer', 'Courses and study tools', 'Live classes with an expert tutor, mock tests, score calculators, test converters and a study planner.', route('courses.index')],
                ] as [$kind, $mark, $title, $text, $url])
                    <article class="card about-card">
                        @if ($kind === 'badge')
                            <span class="about-badge about-badge--{{ strtolower($mark) }}">{{ $mark }}</span>
                        @else
                            <span class="method-icon" aria-hidden="true">@include('partials.method-icon', ['icon' => $mark])</span>
                        @endif
                        <h3>{{ $title }}</h3>
                        <p>{{ $text }}</p>
                        @if ($url) <a href="{{ $url }}" class="about-card__link">Explore <span aria-hidden="true">→</span></a> @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═════════ Why choose us (with student results) ═════════ --}}
    <section class="section section--grey about-section" id="why-choose-us">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Why choose us</span>
                <h2>Practice that gets results</h2>
                <p>We built {{ config('app.name') }} around what students actually need on exam day.</p>
            </div>
            <div class="why-grid">
                @foreach ([
                    ['Real exam experience', 'Computer-based test screens, timers and question types that match the real IELTS, CELPIP and TOEFL exams.'],
                    ['Guidance from experienced tutors', 'Our tests and courses are developed by an expert English language tutor, not copied from elsewhere.'],
                    ['Feedback on your answers', 'Writing and speaking are the hardest to self-check. Get your answers reviewed and learn how to improve.'],
                    ['Free practice, paid depth', 'Start with free practice tests, then unlock premium tests and courses when you are ready for more.'],
                ] as $i => [$t, $d])
                    <div class="why-item">
                        <span class="why-num">{{ $i + 1 }}</span>
                        <div><h3>{{ $t }}</h3><p>{{ $d }}</p></div>
                    </div>
                @endforeach
            </div>

            @if ($achievements->isNotEmpty())
                <h3 class="results-title">Our students’ results</h3>
                <div class="results-grid">
                    @foreach ($achievements as $a)
                        <figure class="result-card">
                            <div class="result-card__top">
                                <img src="{{ $a->photo_url }}" alt="Photo of {{ $a->name }}" width="64" height="64" loading="lazy">
                                @if ($a->score) <span class="result-score">{{ $a->score }}</span> @endif
                            </div>
                            @if ($a->quote) <blockquote>“{{ $a->quote }}”</blockquote> @endif
                            <figcaption><strong>{{ $a->name }}</strong>@if ($a->exam)<span>{{ $a->exam }}</span>@endif</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ═════════ Where we are ═════════ --}}
    <section class="section about-section" id="location">
        <div class="container">
            <div class="section-head">
                <span class="eyebrow">Location</span>
                <h2>Where are we located?</h2>
                <p>Come and visit us, or get in touch from anywhere in the world.</p>
            </div>
            <div class="location-box">
                <div class="location-info">
                    <span class="ci-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
                    <h3>{{ config('app.name') }}</h3>
                    <address>{{ $contact['address_line'] }}<br>{{ $contact['city'] }}, {{ $contact['country'] }}</address>
                    <a href="{{ $contact['map_link'] }}" class="btn btn-primary" target="_blank" rel="noopener">Open in Google Maps</a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost">Contact us</a>
                </div>
                <div class="location-map">
                    <iframe src="{{ $contact['map_src'] }}" title="Map showing the location of {{ config('app.name') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>

    <section class="section section--compact about-follow">
        <div class="container" style="text-align:center">
            <h2 class="about-follow__title">Follow us</h2>
            <p class="about-follow__text">Get IELTS, CELPIP and TOEFL tips, updates and new practice tests.</p>
            <div class="social-links" style="justify-content:center">
                @foreach (['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok'] as $net => $netName)
                    <a href="{{ \App\Models\SiteSetting::href($social[$net] ?? '') }}" class="social-link social-link--{{ $net }}" aria-label="{{ $netName }}" target="_blank" rel="noopener">@include('partials.social-icon', ['name' => $net])</a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($extra)
        <section class="section section--compact"><div class="container-narrow"><div class="article-body" style="margin-top:0">{!! $extra !!}</div></div></section>
    @endif
@endsection
