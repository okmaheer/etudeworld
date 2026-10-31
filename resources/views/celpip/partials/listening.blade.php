{{--
    The whole Listening test in ONE box. Every part (1–6) plays inside this box, one screen after another;
    public/js/celpip-listening.js builds the screens from the list below.
    $afterLabel – text of the button on the last screen (e.g. "Continue to Reading" or "See my results").
--}}
<section class="exam-step" data-step data-module="listening" data-label="Listening" data-listening
         data-mode="{{ $isMock ? 'mock' : 'practice' }}" data-test-id="{{ $test->id }}"
         @unless ($isMock) @php $vc = \App\Http\Controllers\VocabularyController::celpipSummary($test, 'listening'); @endphp @if ($vc) data-vocab="{{ json_encode($vc) }}" @endif @endunless>
    <script type="application/json" data-ls-data>@json(\App\Support\CelpipListening::screens($test->moduleContent('listening')))</script>
    <div class="lsx-box">
        <header class="lsx-head">
            <h2 data-ls-title>CELPIP Listening Test</h2>
            <div class="lsx-timer" data-ls-timer hidden>Time remaining: <strong data-ls-time>0:30</strong></div>
            <div class="lsx-nav">
                <button type="button" class="btn btn-primary" data-ls-next hidden>Next</button>
            </div>
        </header>
        <div class="lsx-stage" data-ls-stage aria-live="polite"></div>
        <footer class="lsx-foot">
            <div class="lsx-nav">
                <button type="button" class="btn btn-ghost" data-ls-back hidden>Back</button>
                @if (! empty($isLastSection))
                    {{-- Last section of the test: no next screen, so offer the results as a PDF instead --}}
                    <button type="button" class="btn btn-primary" data-results-download="Listening" data-ls-continue hidden>Download the results</button>
                @else
                    <button type="button" class="btn btn-primary" data-next data-ls-continue hidden>{{ $afterLabel }}</button>
                @endif
            </div>
        </footer>
    </div>
</section>
