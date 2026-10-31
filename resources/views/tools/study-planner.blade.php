<form class="tool-form sp-form" id="sp-form" onsubmit="return false">
    <label class="tool-field"><span>Exam</span>
        <select id="sp-exam">
            <option value="ielts-academic">IELTS Academic</option>
            <option value="ielts-gt">IELTS General Training</option>
            <option value="celpip">CELPIP</option>
            <option value="toefl">TOEFL iBT</option>
        </select></label>
    <label class="tool-field"><span>Target score</span><input id="sp-target" type="text" maxlength="40" placeholder="e.g. Band 7 or CLB 9" required></label>
    <label class="tool-field"><span>Your level now (optional)</span><input id="sp-current" type="text" maxlength="60" placeholder="e.g. Band 6, or last test result"></label>
    <label class="tool-field"><span>Exam date</span><input id="sp-date" type="date" required></label>
    <label class="tool-field"><span>Study hours per day</span><input id="sp-hours" type="number" min="0.5" max="12" step="0.5" value="2" required></label>
    <fieldset class="tool-field sp-weak"><span>Skills you find hardest</span>
        @foreach (['Listening', 'Reading', 'Writing', 'Speaking'] as $sk)
            <label class="sp-check"><input type="checkbox" name="weak" value="{{ $sk }}"> {{ $sk }}</label>
        @endforeach
    </fieldset>
    <div class="sp-submit"><button type="submit" class="vb-btn is-primary" id="sp-go">Make my study plan</button></div>
</form>
<div class="tool-result" id="out" aria-live="polite"></div>
<div class="tool-note">Plans are made by AI from the details you enter and are a guide, not a guarantee. To keep the tool free for everyone, each visitor can make only one plan, so please check your details before you press the button.</div>
<link rel="stylesheet" href="{{ asset('css/vocab.css') }}?v=2">
<script>window.SP = {url: @json(route('tools.study-planner.plan')), mine: @json(route('tools.study-planner.mine')), pdf: @json(asset('js/vocab-pdf.js') . '?v=3')};</script>
<script src="{{ asset('js/study-planner.js') }}?v=2" defer></script>
