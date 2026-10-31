{{--
    IELTS (GT) Reading – full-screen test, like the computer-delivered IELTS:
      header  : student name · timer (1 hour) · Settings · Submit
      part bar: "Part 1 – Read the text below and answer questions 1–13."
      split   : passage on the left | questions on the right (drag the divider to resize)
      footer  : question numbers 1–40 (blue = answered) · ← → to change part
    public/js/ielts-reading.js runs it; answers are marked on the server and the score is shown afterwards.
--}}
@php
    use App\Support\IeltsReading as R;
    $content = R::normalize($test->moduleContent('reading'));
    $ordered = R::ordered($content);
    $ranges  = R::ranges($content);
    // The parts students see: every passage that has text or questions.
    $parts = array_values(array_filter([1, 2, 3], fn ($p) => \App\Support\IeltsSpeaking::filled($content['passages'][$p]) || $ranges[$p]));
    $student = auth('student')->user();
@endphp
<!DOCTYPE html>
<html lang="en" class="rd-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.test-seo-head', ['seo' => \App\Models\TestSeo::resolve('ielts', $test, 'reading', $test->name . ' – IELTS Reading – ' . config('app.name'))])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ \App\Models\SiteSetting::logoUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/ielts-reading.css') }}?v=27">
</head>
<body class="rd-body">
<div class="rd-app rd-scope" data-reading data-minutes="{{ R::MINUTES }}"
     data-score-url="{{ route('ielts.reading.score', $test) }}" data-back-url="{{ $backUrl }}" data-portal-url="{{ route('student.portal') }}"
     data-storage-key="ew-ielts-reading-{{ $test->id }}" data-signed-in="{{ $student ? 1 : 0 }}">

    {{-- ───────── Instructions page (shown before the test starts) ───────── --}}
    @php $formatLabel = $test->format === 'gt' ? 'IELTS General Training' : 'IELTS Academic'; @endphp
    <div class="rd-intro" data-intro>
        <div class="rd-intro__card">
            <a href="{{ $backUrl }}" class="rd-intro__back"><span aria-hidden="true">←</span> Back</a>
            <span class="rd-intro__pill {{ $test->type === 'paid' ? 'is-premium' : '' }}">{{ $test->type === 'paid' ? 'Premium' : 'Free' }} test</span>
            <h1>{{ $test->name }} – Reading</h1>
            <p class="rd-intro__lead">Practise the {{ $formatLabel }} Reading test with real exam timing.</p>
            <ol class="rd-intro__plan">
                @foreach ($parts as $i => $p)
                    <li><b>Part {{ $i + 1 }}</b>
                        <span>@if ($ranges[$p]) Questions {{ $ranges[$p][0] }}{{ $ranges[$p][1] > $ranges[$p][0] ? ' – ' . $ranges[$p][1] : '' }} @else Reading text @endif</span></li>
                @endforeach
            </ol>
            <ul class="rd-intro__tips">
                <li>You have <strong>1 hour</strong> to read the passages and answer all <strong>{{ R::total($content) }} questions</strong>. The timer starts when you click <strong>Start test</strong> and turns red in the last minute.</li>
                <li>Read the text on the <strong>left</strong> and answer on the <strong>right</strong>. Drag the divider between them to change the size of each side.</li>
                <li>Use the numbers or the <strong>← →</strong> buttons at the bottom to move between parts and questions at any time. A question turns blue once you have answered it.</li>
                <li>To highlight text in a passage, select it and choose <strong>Highlight</strong>. Use <strong>Settings</strong> to change the text size or background colour.</li>
                <li>Click <strong>Submit</strong> when you are done. When the hour is up, your answers are submitted automatically and you see your score.</li>
            </ul>
            <p class="rd-intro__student">
                @if ($student) Logged in as <strong>{{ $student->name }}</strong>. Your score will be saved in <a href="{{ route('student.portal') }}" target="_blank">your portal</a>.
                @else Your score is shown at the end. <a href="{{ route('student.login') }}" target="_blank">Log in</a> first if you want it saved in your portal.
                @endif
            </p>
            <button type="button" class="rd-intro__btn" data-start>Start test →</button>
        </div>
    </div>

    {{-- ───────── Header ───────── --}}
    <header class="rd-top">
        <div class="rd-top__name">
            @if ($student)
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                {{ $student->name }}
            @endif
        </div>
        <div class="rd-top__timer" data-timer role="timer" aria-live="off">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 2h6"/></svg>
            <span data-timer-text>60</span> <small>minutes left</small>
        </div>
        <div class="rd-top__actions">
            <button type="button" class="rd-btn" data-settings-btn aria-expanded="false">Settings</button>
            <button type="button" class="rd-btn rd-btn--submit" data-submit>Submit</button>
            <div class="rd-settings" data-settings hidden>
                <h4>Text size</h4>
                <div class="rd-settings__row">
                    @foreach ([12, 14, 16] as $fs)
                        <button type="button" class="rd-chip {{ $fs === 14 ? 'is-on' : '' }}" data-font="{{ $fs }}">{{ $fs }}</button>
                    @endforeach
                </div>
                <h4>Background</h4>
                <div class="rd-settings__row">
                    <button type="button" class="rd-chip is-on" data-bg="white">White</button>
                    <button type="button" class="rd-chip rd-chip--yellow" data-bg="yellow">Yellow</button>
                </div>
            </div>
        </div>
    </header>

    {{-- ───────── Part bar ───────── --}}
    @foreach ($parts as $i => $p)
        <div class="rd-part" data-part-bar="{{ $p }}" @if ($i > 0) hidden @endif>
            <h1>Part {{ $i + 1 }}</h1>
            <p>@if ($ranges[$p]) Read the text below and answer questions <strong>{{ $ranges[$p][0] }}{{ $ranges[$p][1] > $ranges[$p][0] ? ' – ' . $ranges[$p][1] : '' }}</strong>. @else Read the text below. @endif</p>
        </div>
    @endforeach

    {{-- ───────── Split screen ───────── --}}
    <main class="rd-split" data-split>
        <section class="rd-pane rd-pane--left" data-left aria-label="Reading passage">
            @foreach ($parts as $i => $p)
                @php $groups = array_filter($ordered, fn ($g) => $g['passage'] === $p); @endphp
                <article class="rd-passage" data-passage="{{ $p }}" @if ($i > 0) hidden @endif>
                    {!! R::passageHtml($content['passages'][$p], R::headingBoxes($content, $p)) !!}
                </article>
            @endforeach
        </section>

        <div class="rd-divider" data-divider role="separator" aria-orientation="vertical" aria-label="Drag to resize the passage and questions" tabindex="0"></div>

        <section class="rd-pane rd-pane--right" data-right aria-label="Questions">
            @foreach ($parts as $i => $p)
                @php $groups = array_filter($ordered, fn ($g) => $g['passage'] === $p); @endphp
                <div class="rd-questions" data-questions="{{ $p }}" @if ($i > 0) hidden @endif>
                    <div class="rd-qbox">
                        @foreach ($groups as $g)
                            {!! R::groupHtml($g) !!}
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    </main>

    {{-- ───────── Footer: question numbers + part arrows ───────── --}}
    <footer class="rd-foot">
        <nav class="rd-nav" aria-label="Questions">
            @foreach ($parts as $i => $p)
                <div class="rd-nav__part {{ $i === 0 ? 'is-current' : '' }}" data-nav-part="{{ $p }}">
                    <span class="rd-nav__label" data-go-part="{{ $p }}">Part {{ $i + 1 }}</span>
                    @if ($ranges[$p])
                        @for ($q = $ranges[$p][0]; $q <= $ranges[$p][1]; $q++)
                            <button type="button" class="rd-q" data-nav-q="{{ $q }}" data-part="{{ $p }}" aria-label="Question {{ $q }}">{{ $q }}</button>
                        @endfor
                    @endif
                </div>
            @endforeach
        </nav>
        <div class="rd-arrows">
            <button type="button" class="rd-arrow" data-prev aria-label="Previous part"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M20 12H5M11 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            <button type="button" class="rd-arrow" data-next aria-label="Next part"><svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path d="M4 12h15M13 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        </div>
    </footer>

    {{-- Highlight menu (passages only) --}}
    <div class="rd-menu" data-menu hidden role="menu">
        <button type="button" data-hl="add" role="menuitem">Highlight</button>
        <button type="button" data-hl="remove" role="menuitem">Remove</button>
        <button type="button" data-hl="clear" role="menuitem">Remove all</button>
    </div>
</div>

<script src="{{ asset('js/results-url.js') }}?v=3"></script>
<script src="{{ asset('js/ielts-reading.js') }}?v=28"></script>
</body>
</html>
