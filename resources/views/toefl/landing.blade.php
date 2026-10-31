@extends('layouts.app')

@php $seo = \App\Support\SeoPages::landing('toefl'); @endphp
@section('title', $seo['title'])
@section('description', $seo['description'])

@push('head')
    <link rel="stylesheet" href="{{ asset('css/celpip.css') }}?v=44">
    <link rel="canonical" href="{{ url()->current() }}">
@endpush

@php
    use App\Support\Toefl;
    $faq = [
        ['What is the TOEFL iBT?', 'The TOEFL iBT (Test of English as a Foreign Language, internet-based test) is an English test run by ETS. Universities, colleges and some employers and immigration programmes use the score to check that you can study or work in English.'],
        ['What changed in the 2026 format?', 'From January 2026 the TOEFL iBT uses new task types: Complete the Words, Read in Daily Life and Read an Academic Passage in Reading; Listen and Choose a Response, Conversation, Announcement and Academic Talk in Listening; Build a Sentence, Write an Email and Write for an Academic Discussion in Writing; and Listen and Repeat and Take an Interview in Speaking. The test is also shorter than before.'],
        ['Is this practice the same as the real test?', 'Our tests follow the updated 2026 task types and timing. They are practice material written by Etude World, so the questions are not copied from the real exam, and the real exam may change its details. Always check ets.org for the latest official information.'],
        ['How are Writing and Speaking marked?', 'Your Writing answers and Speaking recordings are saved to your account. When you are ready, click Request feedback and a teacher will give you a score and written feedback, which appears in your student portal. You need to log in for these two sections.'],
        ['How are Reading and Listening scored?', 'They are marked instantly. You see your raw score, your answer and the correct answer for every question. Only the questions that count towards the score are included in the total.'],
        ['Is it free?', 'Many tests are free. Some tests are marked Premium and will be available to paid members.'],
    ];
@endphp

@section('content')
    <section class="hero cp-hero ip-hero">
        <div class="container hero-grid">
            <div class="hero-text">
                <span class="eyebrow">TOEFL iBT · 2026 format</span>
                <h1>Practise the <span class="hl">updated TOEFL iBT</span> – the 2026 format</h1>
                <p class="lead">Etude World offers practice for the new TOEFL iBT task types, with real timing, instant Reading and Listening marking, and teacher feedback on your Writing and Speaking.</p>
                <div class="hero-actions">
                    <a href="{{ route('toefl.hub') }}" class="btn btn-primary">Start practising</a>
                    <a href="{{ route('toefl.mocks') }}" class="btn btn-ghost">Full mock tests</a>
                </div>
                <ul class="hero-stats">
                    <li><strong>2026 format</strong><span>new task types</span></li>
                    <li><strong>About 90 min</strong><span>the same timers as the test</span></li>
                    <li><strong>Teacher feedback</strong><span>Writing &amp; Speaking</span></li>
                </ul>
            </div>
            <div class="cp-hero-art" aria-hidden="true">
                <div class="cp-mock">
                    <div class="cp-mock__bar"><span></span><span></span><span></span><em>TOEFL iBT Practice Test</em></div>
                    <div class="cp-mock__body">
                        @foreach (['reading' => ['R', 'r', 'Complete the Words'], 'listening' => ['L', 'l', 'Choose a Response'], 'writing' => ['W', 'w', 'Build a Sentence'], 'speaking' => ['S', 's', 'Take an Interview']] as $k => $x)
                            <div class="cp-mock__row"><b class="cp-ic cp-ic--{{ $x[1] }}">{{ $x[0] }}</b><div><strong>{{ Toefl::PUBLIC[$k]['label'] }}</strong><small>{{ $x[2] }} · {{ Toefl::PUBLIC[$k]['time'] }}</small></div><i class="{{ in_array($k, ['writing', 'speaking']) ? 'is-wait' : '' }}">{{ in_array($k, ['writing', 'speaking']) ? 'Teacher' : 'Instant' }}</i></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section cp-exam ip-exam">
        <div class="container cp-exam__grid">
            <div class="cp-exam__text">
                <span class="eyebrow">About the exam</span>
                <h2>What is the TOEFL iBT?</h2>
                <p>The TOEFL iBT is one of the most widely accepted English tests in the world. Thousands of universities and colleges, mainly in the United States, Canada, the UK and Australia, use it to check that international students can follow classes and take part in discussions in English.</p>
                <p>You take it on a computer. You read texts, listen to recordings, write and speak into a microphone, and all four skills (Reading, Listening, Writing and Speaking) are part of your result.</p>
                <p><strong>Our website offers the updated 2026 format.</strong> The test is shorter and uses new task types that look more like real life on campus: reading a notice, listening to a short announcement, building a sentence, writing an email or answering interview questions.</p>
                <a href="{{ route('toefl.hub') }}" class="btn btn-primary btn-lg">Go to the practice tests <span aria-hidden="true">→</span></a>
            </div>
            <div class="cp-skills">
                @foreach (Toefl::PUBLIC as $sk)
                    <div class="cp-skill">
                        <span class="cp-skill__icon">@include('admin.partials.icon', ['name' => $sk['icon']])</span>
                        <strong>{{ $sk['label'] }}</strong>
                        <small>{{ $sk['time'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section section--tint">
        <div class="container tf-intro">
            <h2>The 2026 format, section by section</h2>
            <div class="tf-intro__grid">
                <article class="tf-intro__card">
                    <h3>@include('admin.partials.icon', ['name' => 'pen']) Reading · 30 minutes</h3>
                    <p>Two modules, about 35–48 questions. Only some questions count towards your score; the rest are new questions ETS is trying out.</p>
                    <ul>
                        <li><strong>Complete the Words</strong> – a short paragraph where some words are missing letters. Type the missing letters.</li>
                        <li><strong>Read in Daily Life</strong> – notices, emails, social media posts and similar everyday texts, followed by multiple-choice questions.</li>
                        <li><strong>Read an Academic Passage</strong> – a short university-style text with multiple-choice questions, including vocabulary in context.</li>
                    </ul>
                </article>
                <article class="tf-intro__card">
                    <h3>@include('admin.partials.icon', ['name' => 'headphones']) Listening · 30 minutes</h3>
                    <p>Two modules, about 35–45 multiple-choice questions. You hear each recording once, and the questions appear after the audio ends.</p>
                    <ul>
                        <li><strong>Listen and Choose a Response</strong> – hear a short statement or question and pick the best reply.</li>
                        <li><strong>Conversation</strong> – two people talk about everyday or campus life.</li>
                        <li><strong>Announcement</strong> – a short notice such as one you might hear on campus.</li>
                        <li><strong>Academic Talk</strong> – a short talk by one speaker, like part of a lecture.</li>
                    </ul>
                </article>
                <article class="tf-intro__card">
                    <h3>@include('admin.partials.icon', ['name' => 'page']) Writing · 23 minutes</h3>
                    <ul>
                        <li><strong>Build a Sentence</strong> – put the words and phrases in the correct order. You score only if the whole sentence is right.</li>
                        <li><strong>Write an Email</strong> – 7 minutes to reply to a situation in a clear, polite email.</li>
                        <li><strong>Write for an Academic Discussion</strong> – 10 minutes to add your opinion to an online class discussion with a professor and two students.</li>
                    </ul>
                </article>
                <article class="tf-intro__card">
                    <h3>@include('admin.partials.icon', ['name' => 'mic']) Speaking · 8 minutes</h3>
                    <ul>
                        <li><strong>Listen and Repeat</strong> – hear a sentence and repeat it exactly. 7 questions.</li>
                        <li><strong>Take an Interview</strong> – answer 4 interview questions in your own words.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container tf-intro">
            <h2>How practice works on Etude World</h2>
            <div class="tf-intro__grid tf-intro__grid--3">
                <article class="tf-intro__card"><h3>Real timing</h3><p>Every module and task has the same countdown as the test. When time runs out, the screen moves on, just like on test day.</p></article>
                <article class="tf-intro__card"><h3>Full mock tests</h3><p>Take all four sections one after another. In a full mock test you cannot go back to an earlier section, like the real exam.</p></article>
                <article class="tf-intro__card"><h3>Individual sections</h3><p>Practise one section at a time. In Reading practice you can go back to earlier questions in the same module.</p></article>
                <article class="tf-intro__card"><h3>Instant results</h3><p>Reading and Listening are marked at once. You see your score, your answers and the correct answers.</p></article>
                <article class="tf-intro__card"><h3>Teacher feedback</h3><p>Writing answers and Speaking recordings are saved. Click Request feedback and a teacher will send you a score and comments in your portal.</p></article>
                <article class="tf-intro__card"><h3>Free to start</h3><p>Many tests are free. Log in to save your results and track your progress.</p></article>
            </div>
            <p class="tf-intro__cta"><a href="{{ route('toefl.hub') }}" class="btn btn-primary btn-lg">Choose a test <span aria-hidden="true">→</span></a></p>
        </div>
    </section>

    <section class="section section--tint">
        <div class="container tf-intro tf-intro--narrow">
            <h2>TOEFL iBT questions</h2>
            @foreach ($faq as [$q, $a])
                <details class="tf-faq"><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
            @endforeach
            <p class="tf-note">Practice tests on Etude World are independent practice material and are not affiliated with or endorsed by ETS. TOEFL and TOEFL iBT are registered trademarks of ETS.</p>
        </div>
    </section>
@endsection
