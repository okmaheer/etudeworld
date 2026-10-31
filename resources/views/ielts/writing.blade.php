@extends('layouts.exam')

{{--
    IELTS Writing test: welcome screen → Task 1 (20 min) → Task 2 (40 min) → "check your portal" screen.
    Each task: question on the left, instructions + answer box on the right.
    public/js/ielts-writing.js runs it. Answers go to Admin → Evaluation Requests → IELTS Evaluation.
--}}

@php
    use App\Support\IeltsWriting as W;
    $c = W::forEditing($test->moduleContent('writing'), $test->format);
    $formatLabel = $test->format === 'gt' ? 'IELTS General Training' : 'IELTS Academic';
@endphp

@php $seo = \App\Models\TestSeo::resolve('ielts', $test, 'writing', $test->name . ' – IELTS Writing – ' . config('app.name')); @endphp
@section('title', $test->name . ' – IELTS Writing')
@section('seo') @include('partials.test-seo-head', ['seo' => $seo]) @endsection

@section('content')
<div class="exam ipw" data-ielts-writing data-test-id="{{ $test->id }}"
     data-submit-url="{{ route('ielts.submissions.store') }}" data-storage-key="ew-ielts-writing-{{ $test->id }}">

    {{-- ───────── Top bar ───────── --}}
    <header class="exam-bar">
        <a href="{{ $backUrl }}" class="exam-bar__brand" data-exit>
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="30" height="30">
            <span>Etude <strong>World</strong></span>
        </a>
        <div class="exam-bar__title">
            <strong>{{ $test->name }}</strong>
            <span>{{ $formatLabel }} · Writing</span>
        </div>
        <a href="{{ $backUrl }}" class="exam-bar__exit ipx-exit" data-exit>Exit</a>
    </header>

    <div class="exam-toast" data-toast role="status" aria-live="assertive" hidden></div>

    <main class="exam-main">

        {{-- ───────── Welcome ───────── --}}
        <section class="exam-step exam-step--center" data-step>
            <div class="exam-intro">
                <span class="pill {{ $test->type === 'paid' ? 'pill--premium' : 'pill--free' }}">{{ $test->type === 'paid' ? 'Premium' : 'Free' }} test</span>
                <h1>{{ $test->name }} – Writing</h1>
                <p>Practise the {{ $formatLabel }} Writing test with real exam timing.</p>
                <ol class="exam-plan">
                    @foreach (W::TASKS as $key => $t)
                        <li>
                            <span class="exam-plan__icon">@include('admin.partials.icon', ['name' => 'page'])</span>
                            <div>
                                <strong>{{ $t['title'] }}: {{ W::subtitle($key, $test->format) }}</strong>
                                <small>{{ $t['min'] }}–{{ $t['max'] }} words</small>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <ul class="exam-tips">
                    <li>You have <strong>1 hour</strong> in total for both tasks. Spend about 20 minutes on Task 1 and 40 minutes on Task 2 – Task 2 is worth more marks.</li>
                    <li>Move between the tasks at any time with the <strong>Task 1</strong> and <strong>Task 2</strong> buttons at the bottom.</li>
                    <li>Write at least <strong>100 words</strong> for Task 1 (maximum 200) and at least <strong>180 words</strong> for Task 2 (maximum 350). You cannot type past the maximum.</li>
                    <li>Click <strong>Submit</strong> when both answers are long enough. When the hour is up, your answers are submitted automatically.</li>
                    <li>Like the real test, there is no spell check, and copy, paste, cut, undo and redo are switched off.</li>
                </ul>
                <p class="exam-student">Logged in as <strong>{{ auth('student')->user()->name }}</strong> ({{ auth('student')->user()->email }}).
                    Your answers will be sent to your teacher, and your results will appear in <a href="{{ route('student.portal') }}" target="_blank">your portal</a>.</p>
                <button type="button" class="btn btn-primary btn-lg" data-next>Start practice @include('admin.partials.icon', ['name' => 'arrow'])</button>
                <p class="exam-restored" data-restored hidden>We found answers you started earlier and restored them.
                    <button type="button" class="link-btn" data-reset>Start fresh instead</button></p>
            </div>
        </section>

        {{-- ───────── Task 1 and Task 2 (one timer for both: 1 hour) ───────── --}}
        @foreach (W::TASKS as $key => $t)
            @php $title = $t['title'] . ': ' . W::subtitle($key, $test->format); @endphp
            <section class="exam-step" data-step data-task="{{ $key }}" data-task-title="{{ $title }}"
                     data-min="{{ $t['min'] }}" data-max="{{ $t['max'] }}" hidden>
                <div class="lsx-box wrx-box">
                    <header class="lsx-head ipw-head">
                        <h2>Writing {{ $title }}</h2>
                        <div class="lsx-timer" data-timer>Time remaining: <strong data-timer-text>1:00:00</strong></div>
                        <span aria-hidden="true"></span>{{-- keeps the timer in the centre --}}
                    </header>

                    <div class="wrx-split">
                        <div class="wrx-left">
                            <div class="task-text">{!! $c[$key] !!}</div>
                        </div>

                        <div class="wrx-right">
                            <div class="time-up" data-time-up hidden>
                                @include('admin.partials.icon', ['name' => 'clock']) Time is up. Your answers have been saved and submitted.
                            </div>
                            <div class="answer-head">
                                <label for="answer-{{ $key }}" class="answer-label">Your answer</label>
                            </div>
                            {{-- Like the real IELTS test: no spell check, no copy / cut / paste / undo / redo --}}
                            <textarea id="answer-{{ $key }}" class="answer-box" data-answer="{{ $key }}"
                                      spellcheck="false" lang="en" autocomplete="off" autocorrect="off" autocapitalize="off"
                                      placeholder="Start typing your answer here…"></textarea>
                            <div class="answer-foot">
                                <span>Words: <strong data-word-count>0</strong> / {{ $t['max'] }}</span>
                                <span class="word-target" data-word-hint>Write at least {{ $t['min'] }} words</span>
                            </div>
                        </div>
                    </div>

                    <footer class="lsx-foot ipw-foot">
                        <nav class="ipw-tasks" aria-label="Tasks">
                            @foreach (W::TASKS as $k2 => $t2)
                                <button type="button" class="ipw-task {{ $k2 === $key ? 'is-current' : '' }}" data-goto="{{ $k2 }}"
                                        @if ($k2 === $key) aria-current="true" @endif>{{ $t2['title'] }}</button>
                            @endforeach
                        </nav>
                        <div class="ipw-foot__end">
                            <button type="button" class="btn btn-submit" data-writing-submit>Submit</button>
                        </div>
                    </footer>
                </div>
            </section>
        @endforeach

        {{-- ───────── Finished ───────── --}}
        <section class="exam-step" data-step data-finish hidden>
            <div class="exam-done">
                <div class="exam-done__head">
                    <div class="exam-intro__icon exam-intro__icon--done">@include('admin.partials.icon', ['name' => 'trophy'])</div>
                    <h2>Well done – you've finished!</h2>
                    <p data-send-status>Sending your answers to your teacher…</p>
                    <button type="button" class="btn btn-primary" data-retry hidden>Try again</button>
                </div>
                <div class="exam-portal-note" data-portal hidden>
                    <p><strong>Your Writing answers have been sent to your teacher for evaluation.</strong></p>
                    <p>Please check your results in your portal after <strong>24 hours</strong>.</p>
                    <a href="{{ route('student.portal') }}" class="btn btn-primary">Go to my portal</a>
                </div>
                <div class="review" data-review></div>
                <div class="exam-done__actions">
                    <button type="button" class="btn btn-ghost" data-copy>Copy my answers</button>
                    <button type="button" class="btn btn-ghost" data-download>Download as a text file</button>
                    <a href="{{ $backUrl }}" class="btn btn-primary">More tests</a>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/ielts-writing.js') }}?v=8"></script>
@endpush
