{{--
    The whole Reading test in ONE box: the instructions screen, then one screen per part.
    Passage on the left, questions on the right; each part has its own timer.
    public/js/celpip-reading.js builds the screens from the data below.
    $afterLabel – text of the button on the last screen.
--}}
<section class="exam-step" data-step data-module="reading" data-label="Reading" data-reading
         data-mode="{{ $isMock ? 'mock' : 'practice' }}" data-test-id="{{ $test->id }}"
         @unless ($isMock) @php $vc = \App\Http\Controllers\VocabularyController::celpipSummary($test, 'reading'); @endphp @if ($vc) data-vocab="{{ json_encode($vc) }}" @endif @endunless>
    <script type="application/json" data-rd-data>@json(\App\Support\CelpipReading::screens($test->moduleContent('reading')))</script>
    <div class="lsx-box rdx-box">
        <header class="lsx-head">
            <h2 data-rd-title>CELPIP Reading Test</h2>
            <div class="lsx-nav">
                <div class="lsx-timer" data-rd-timer hidden>Time remaining: <strong data-rd-time>10:00</strong></div>
                <button type="button" class="btn btn-primary" data-rd-next>Next</button>
                <button type="button" class="btn btn-submit" data-rd-submit hidden>Submit</button>
            </div>
        </header>
        <div class="rdx-stage" data-rd-stage></div>
        <footer class="lsx-foot">
            <div class="lsx-nav">
                <button type="button" class="btn btn-ghost" data-rd-back hidden>Back</button>
                @if (! empty($isLastSection))
                    {{-- Last section of the test: no next screen, so offer the results as a PDF instead --}}
                    <button type="button" class="btn btn-primary" data-results-download="Reading" data-rd-continue hidden>Download the results</button>
                @else
                    <button type="button" class="btn btn-primary" data-next data-rd-continue hidden>{{ $afterLabel }}</button>
                @endif
            </div>
        </footer>
    </div>
</section>
