@extends('layouts.exam')

{{--
    The test-taking screen. Used for:
      • a full mock test  ($mode = 'mock',     $modules = all sections in exam order)
      • one-skill practice ($mode = 'practice', $modules = ['writing'])
    Each [data-step] is one screen; public/js/celpip-exam.js shows them one at a time.
--}}

@php
    use App\Models\CelpipTest;
    $isMock  = $mode === 'mock';
    $writing = $test->moduleContent('writing');
    $total   = count($modules);
    $title   = $isMock ? $test->name . ' – Full Mock Test' : $test->name . ' – ' . CelpipTest::PUBLIC_MODULES[$modules[0]]['label'];
@endphp

@php $seo = \App\Models\TestSeo::resolve('celpip', $test, $isMock ? 'mock' : $modules[0], $title . ' – ' . config('app.name')); @endphp
@section('title', $title)
@section('seo') @include('partials.test-seo-head', ['seo' => $seo]) @endsection

@section('content')
<div class="exam" data-exam data-mode="{{ $mode }}" data-test-id="{{ $test->id }}" data-submit-url="{{ route('celpip.submissions.store') }}" data-results-url="{{ route('student.results.store') }}" data-signed-in="{{ auth('student')->check() ? 1 : 0 }}" data-storage-key="ew-celpip-{{ $test->id }}-{{ $mode }}">

    {{-- ───────── Top bar ───────── --}}
    <header class="exam-bar">
        <a href="{{ $backUrl }}" class="exam-bar__brand" data-exit>
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="30" height="30">
            <span>Etude <strong>World</strong></span>
        </a>
        <div class="exam-bar__title">
            <strong>{{ $test->name }}</strong>
            <span data-step-label>{{ $isMock ? 'Full mock test' : 'Practice' }}</span>
        </div>
        @if ($total > 1)
            <ol class="exam-progress" aria-label="Sections">
                @foreach ($modules as $m)
                    <li data-progress="{{ $m }}">{{ CelpipTest::PUBLIC_MODULES[$m]['label'] }}</li>
                @endforeach
            </ol>
        @endif
        <div class="exam-timer" data-timer hidden aria-live="polite">
            @include('admin.partials.icon', ['name' => 'clock'])
            <span data-timer-text>00:00</span>
        </div>
        <a href="{{ $backUrl }}" class="exam-bar__exit" data-exit>Exit</a>
    </header>

    <div class="exam-toast" data-toast role="status" aria-live="assertive" hidden></div>

    <main class="exam-main">

        {{-- ───────── Welcome screen ───────── --}}
        <section class="exam-step exam-step--center" data-step data-label="Welcome">
            <div class="exam-intro">
                <span class="pill {{ $test->type === 'paid' ? 'pill--premium' : 'pill--free' }}">{{ $test->type === 'paid' ? 'Premium' : 'Free' }} test</span>
                <h1>{{ $title }}</h1>
                @if ($isMock)
                    <p>This mock test has {{ $total }} sections, in the same order as the real {{ CelpipTest::FORMATS[$format] }} test.
                        When you finish one section, the next one begins.</p>
                @else
                    <p>Practise the {{ strtolower(CelpipTest::PUBLIC_MODULES[$modules[0]]['label']) }} section of this test with real exam timing.</p>
                @endif
                <ol class="exam-plan">
                    @foreach ($modules as $i => $m)
                        @php $info = CelpipTest::PUBLIC_MODULES[$m]; @endphp
                        <li class="{{ $test->moduleReady($m) ? '' : 'is-soon' }}">
                            <span class="exam-plan__icon">@include('admin.partials.icon', ['name' => $info['icon']])</span>
                            <div>
                                <strong>{{ $info['label'] }}</strong>
                                <small>{{ $info['parts'] }} · {{ $info['time'] }}</small>
                            </div>
                            @unless ($test->moduleReady($m))<span class="pill pill--soon">Coming soon</span>@endunless
                        </li>
                    @endforeach
                </ol>
                @if (! $isMock && $modules[0] === 'reading')
                <ul class="exam-tips">
                    <li>The reading text is on the left and the questions are on the right. Choose your answers from the drop-down menus.</li>
                    <li>Each part has its own timer (Part 1: 10 min, Part 2: 9 min, Part 3: 10 min, Part 4: 13 min). Unused time does not carry over.</li>
                    <li>When a part's timer reaches 00:00, its answers are locked and the next part opens automatically.</li>
                </ul>
                @elseif (! $isMock && $modules[0] === 'speaking')
                <ul class="exam-tips">
                    <li>You need a microphone. Your browser will ask for permission to use it – please click <strong>Allow</strong>.</li>
                    <li>Each task shows the question and a preparation countdown. Then recording starts by itself.</li>
                    <li>When the recording time ends, click <strong>Next</strong> for the next task.</li>
                    <li>Your recordings are sent to your teacher for evaluation.</li>
                </ul>
                @elseif (! $isMock && $modules[0] === 'listening')
                <ul class="exam-tips">
                    <li>Use headphones or speakers. Recordings start playing on their own.</li>
                    <li>Each recording plays once. The <strong>Next</strong> button appears when it ends, then you have 30 seconds to answer.</li>
                    <li>When the 30 seconds are up, the next question opens automatically.</li>
                    <li>All six parts run one after another in the same box.</li>
                </ul>
                @else
                <ul class="exam-tips">
                    <li>Each task has its own countdown timer (Task 1: 27 minutes, Task 2: 26 minutes). Unused time does not carry over.</li>
                    <li>When a timer reaches 00:00, your answer is locked and saved and the next screen opens automatically.</li>
                    @if ($isMock)
                    <li>Once you click <strong>Next</strong>, you can't go back to the previous task.</li>
                    @else
                    <li>Use <strong>Next</strong> and <strong>Back</strong> to move between Task 1 and Task 2. Click <strong>Submit</strong> on Task 2 when you're done.</li>
                    @endif
                    <li>Spelling mistakes are underlined in red, but nothing is corrected for you – fix them yourself. Cut, Copy, Paste, Undo and Redo work as usual.</li>
                </ul>
                @endif
                @php $student = auth('student')->user(); @endphp
                @if ($student && collect($modules)->contains(fn ($m) => in_array($m, ['writing', 'speaking'], true) && $test->moduleReady($m)))
                    <p class="exam-student">Logged in as <strong>{{ $student->name }}</strong> ({{ $student->email }}).
                        Your Writing and Speaking answers will be sent to your teacher, and your results will appear in <a href="{{ route('student.portal') }}" target="_blank">your portal</a>.</p>
                @endif
                <button type="button" class="btn btn-primary btn-lg" data-next>Start {{ $isMock ? 'the test' : 'practice' }} @include('admin.partials.icon', ['name' => 'arrow'])</button>
                <p class="exam-restored" data-restored hidden>We found answers you started earlier and restored them.
                    <button type="button" class="link-btn" data-reset>Start fresh instead</button></p>
            </div>
        </section>

        {{-- ───────── One block of screens per section ───────── --}}
        @foreach ($modules as $i => $m)
            @php $info = CelpipTest::PUBLIC_MODULES[$m]; $ready = $test->moduleReady($m); @endphp

            @if ($isMock)
                <section class="exam-step exam-step--center" data-step data-module="{{ $m }}" data-label="{{ $info['label'] }}">
                    <div class="exam-intro exam-intro--section">
                        <span class="exam-section-num">Section {{ $i + 1 }} of {{ $total }}</span>
                        <div class="exam-intro__icon">@include('admin.partials.icon', ['name' => $info['icon']])</div>
                        <h2>{{ $info['label'] }}</h2>
                        @if ($ready)
                            <p>{{ $info['desc'] }}</p>
                            <p class="muted-inline">{{ $info['parts'] }} · {{ $info['time'] }}</p>
                            <button type="button" class="btn btn-primary btn-lg" data-next>Begin {{ $info['label'] }}</button>
                        @else
                            <p>The {{ strtolower($info['label']) }} section of this mock test is still being prepared.
                                {{ $i + 1 < $total ? 'You can continue to the next section.' : 'You can finish the test now.' }}</p>
                            <button type="button" class="btn btn-primary btn-lg" data-next>{{ $i + 1 < $total ? 'Continue to ' . CelpipTest::PUBLIC_MODULES[$modules[$i + 1]]['label'] : 'Finish the test' }}</button>
                        @endif
                    </div>
                </section>
            @endif

            @if ($ready && $m === 'listening')
                @include('celpip.partials.listening', [
                    'isLastSection' => $i + 1 >= $total,
                    'afterLabel' => $i + 1 < $total ? 'Continue to ' . CelpipTest::PUBLIC_MODULES[$modules[$i + 1]]['label'] : 'See my results',
                ])
            @endif

            @if ($ready && $m === 'speaking')
                @include('celpip.partials.speaking', [
                    'isLastSection' => $i + 1 >= $total,
                    'afterLabel' => $i + 1 < $total ? 'Continue to ' . CelpipTest::PUBLIC_MODULES[$modules[$i + 1]]['label'] : 'See my results',
                ])
            @endif

            @if ($ready && $m === 'reading')
                @include('celpip.partials.reading', [
                    'isLastSection' => $i + 1 >= $total,
                    'afterLabel' => $i + 1 < $total ? 'Continue to ' . CelpipTest::PUBLIC_MODULES[$modules[$i + 1]]['label'] : 'See my results',
                ])
            @endif

            @if ($ready && $m === 'writing')
                @foreach (CelpipTest::WRITING_TASKS as $key => $task)
                    @php
                        $hasOptions = $key === 'task2' && (filled(strip_tags($writing['task2_option1'] ?? '')) || filled(strip_tags($writing['task2_option2'] ?? '')));
                        $isTask2 = $key === 'task2';
                    @endphp
                    {{-- One writing task in one box: Text Editor 1 on the left; Text Editor 2 (+ options) and the response box on the right --}}
                    <section class="exam-step" data-step data-module="writing" data-label="Writing · {{ $task['title'] }}"
                             data-minutes="{{ CelpipTest::WRITING_MINUTES[$key] }}" data-task="{{ $key }}" data-task-title="{{ $task['title'] }}: {{ $task['subtitle'] }}">
                        <div class="lsx-box wrx-box">
                            <header class="lsx-head">
                                <h2>Writing {{ $task['title'] }}: {{ $task['subtitle'] }}</h2>
                                <div class="lsx-nav">
                                    <div class="lsx-timer" data-step-timer>Time remaining: <strong data-step-timer-text>{{ CelpipTest::WRITING_MINUTES[$key] }}:00</strong></div>
                                    <button type="button" class="btn btn-primary" @if ($isTask2) disabled title="This is the last task – click Submit when you're done" @else data-next @endif>Next</button>
                                    @if ($isTask2)
                                        <button type="button" class="btn btn-submit" data-writing-submit>Submit</button>
                                    @endif
                                </div>
                            </header>

                            <div class="wrx-split">
                                <div class="wrx-left">
                                    <div class="task-text">{!! $writing[$key] ?? '' !!}</div>
                                </div>

                                <div class="wrx-right">
                                    <div class="time-up" data-time-up hidden>
                                        @include('admin.partials.icon', ['name' => 'clock']) Time is up for this task. Your answer has been saved.
                                    </div>
                                    @if (filled(trim(strip_tags($writing[$key . '_note'] ?? '', '<img>'))))
                                        <div class="answer-note">{!! $writing[$key . '_note'] !!}</div>
                                    @endif

                                    @if ($hasOptions)
                                        <fieldset class="task-options" data-options>
                                            <legend>Choose <strong>one</strong> option, then write your response:</legend>
                                            @foreach ([1, 2] as $n)
                                                @if (filled(strip_tags($writing['task2_option' . $n] ?? '')))
                                                    <label class="task-option">
                                                        <input type="radio" name="{{ $key }}_choice" value="{{ $n }}" data-choice>
                                                        <span class="task-option__box">
                                                            <span class="task-option__head"><span class="task-option__radio"></span> Option {{ $n === 1 ? 'A' : 'B' }}</span>
                                                            <span class="task-option__text">{!! $writing['task2_option' . $n] !!}</span>
                                                        </span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </fieldset>
                                    @endif

                                    <div class="answer-head">
                                        <label for="answer-{{ $key }}" class="answer-label">Your response</label>
                                        <div class="edit-tools" role="toolbar" aria-label="Text tools" data-tools>
                                            <button type="button" data-tool="cut" title="Cut (Ctrl+X)">Cut</button>
                                            <button type="button" data-tool="copy" title="Copy (Ctrl+C)">Copy</button>
                                            <button type="button" data-tool="paste" title="Paste (Ctrl+V)">Paste</button>
                                            <span class="edit-tools__sep" aria-hidden="true"></span>
                                            <button type="button" data-tool="undo" title="Undo (Ctrl+Z)">Undo</button>
                                            <button type="button" data-tool="redo" title="Redo (Ctrl+Y)">Redo</button>
                                        </div>
                                    </div>
                                    {{-- Spell check: our own checker underlines mistakes (works in every browser). No auto-correct. --}}
                                    <div class="spell-wrap" data-spell-wrap>
                                        <div class="spell-backdrop" data-spell-backdrop aria-hidden="true"></div>
                                        <textarea id="answer-{{ $key }}" class="answer-box" data-answer="{{ $key }}"
                                                  spellcheck="false" lang="en" autocomplete="off" autocorrect="off" autocapitalize="off"
                                                  @if ($hasOptions) disabled placeholder="First choose Option A or Option B above…" @else placeholder="Start typing your response here…" @endif></textarea>
                                    </div>
                                    <div class="answer-foot">
                                        <span>Words: <strong data-word-count>0</strong></span>
                                        <span class="word-target" data-word-hint>Aim for 150–200 words</span>
                                    </div>
                                </div>
                            </div>

                            <footer class="lsx-foot">
                                <div class="lsx-nav">
                                    {{-- Back: only when practising Writing on its own (never in the full mock test) --}}
                                    @if (! $isMock && $isTask2)
                                        <button type="button" class="btn btn-ghost" data-back>Back</button>
                                    @endif
                                </div>
                            </footer>
                        </div>
                    </section>
                @endforeach
            @endif
        @endforeach

        {{-- ───────── Results ───────── --}}
        <section class="exam-step" data-step data-label="Your answers" data-finish>
            <div class="exam-done">
                <div class="exam-done__head">
                    <div class="exam-intro__icon exam-intro__icon--done">@include('admin.partials.icon', ['name' => 'trophy'])</div>
                    <h2>Well done – you've finished!</h2>
                    <p>Here are your answers. Read them again and check the word counts. Then compare them with the task:
                        did you cover every point and keep a clear structure?</p>
                </div>
                @if (collect($modules)->contains(fn ($m) => in_array($m, ['writing', 'speaking'], true) && $test->moduleReady($m)))
                    {{-- Writing and Speaking are evaluated by the teacher, so their results come later --}}
                    <div class="exam-portal-note">
                        <p><strong>Your {{ collect($modules)->filter(fn ($m) => in_array($m, ['writing', 'speaking'], true) && $test->moduleReady($m))->map(fn ($m) => ucfirst($m))->implode(' and ') }} answers have been sent to your teacher for evaluation.</strong></p>
                        <p>Please check your results in your portal after <strong>24 hours</strong>.</p>
                        <a href="{{ route('student.portal') }}" class="btn btn-primary">Go to my portal</a>
                    </div>
                @endif
                <div class="review" data-review></div>
                <div class="exam-done__actions">
                    <button type="button" class="btn btn-ghost" data-copy>Copy my answers</button>
                    <button type="button" class="btn btn-ghost" data-download>Download as a text file</button>
                    <button type="button" class="btn btn-ghost" data-reset>Try this test again</button>
                    <a href="{{ $backUrl }}" class="btn btn-primary">More tests</a>
                </div>
            </div>
        </section>
    </main>
</div>
@endsection

@push('scripts')
    <script>window.EW_SPELL_WORKER = @json(asset('js/spellcheck-worker.js'));</script>
    <script src="{{ asset('js/celpip-exam.js') }}?v=13"></script>
    <script src="{{ asset('js/celpip-listening.js') }}?v=14"></script>
    <script src="{{ asset('js/celpip-reading.js') }}?v=10"></script>
    <script src="{{ asset('js/celpip-speaking.js') }}?v=6"></script>
    <script>window.EW_HTML2PDF = @json(asset('vendor/html2pdf/html2pdf.bundle.min.js'));</script>
    <script src="{{ asset('js/celpip-results-pdf.js') }}?v=2"></script>
@endpush
