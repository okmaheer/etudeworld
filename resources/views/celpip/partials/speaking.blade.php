{{--
    The whole Speaking test in ONE box: instructions, then one screen per task
    (question → preparation countdown → recording with the microphone), then the recordings
    are sent to the teacher (Admin → CELPIP Evaluation).
    public/js/celpip-speaking.js runs it.
--}}
<section class="exam-step" data-step data-module="speaking" data-label="Speaking" data-speaking
         data-mode="{{ $isMock ? 'mock' : 'practice' }}" data-portal-url="{{ route('student.portal') }}">
    <script type="application/json" data-sp-data>@json(\App\Support\CelpipSpeaking::screens($test->moduleContent('speaking')))</script>
    <div class="lsx-box spx-box">
        <header class="lsx-head">
            <h2 data-sp-title>CELPIP Speaking Test</h2>
            <div class="lsx-nav">
                <div class="spx-times" data-sp-times hidden></div>
                <button type="button" class="btn btn-primary" data-sp-next>Next</button>
            </div>
        </header>
        <div class="lsx-stage" data-sp-stage></div>
        <footer class="lsx-foot">
            <div class="lsx-nav">
                <button type="button" class="btn btn-ghost" data-sp-back hidden>Back</button>
                @if (! empty($isLastSection) && ! $isMock)
                    <a href="{{ route('student.portal') }}" class="btn btn-primary" data-sp-continue hidden>Go to my portal</a>
                @else
                    <button type="button" class="btn btn-primary" data-next data-sp-continue hidden>{{ $afterLabel }}</button>
                @endif
            </div>
        </footer>
    </div>
</section>
