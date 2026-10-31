@extends('admin.layout')

@php use App\Support\IeltsSpeaking as S; @endphp

@section('title', 'IELTS Speaking Questions')

@section('actions')
    @if ($test->moduleReady('speaking'))
        <a href="{{ route('ielts.practice.test', [$test->formatPath(), 'speaking', $test->slug]) }}" class="btn btn-sm btn-ghost" target="_blank" rel="noopener">View on website ↗</a>
    @endif
    <a href="{{ route('admin.ielts.index') }}" class="btn btn-sm btn-ghost">← All IELTS Tests</a>
@endsection

@section('content')
    <div class="module-tabs">
        @foreach ($test->builtModules() as $key)
            <a href="{{ $test->adminModuleUrl($key) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">
                @include('admin.partials.icon', ['name' => \App\Models\IeltsTest::MODULES[$key]['icon']]) {{ \App\Models\IeltsTest::MODULES[$key]['label'] }}
            </a>
        @endforeach
    </div>

    <p class="module-context">
        <strong>{{ $test->name }}</strong>
        <span class="badge badge-blue">{{ $test->formatShort() }}</span>
        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
    </p>

    {{-- Uses the same page script as CELPIP Listening (text editors, unsaved-changes warning) --}}
    <form method="POST" action="{{ route('admin.ielts.module.save', [$test, 'speaking']) }}" id="listening-form"
          data-upload-url="{{ route('admin.ielts.media', $test) }}">
        @csrf @method('PUT')

        {{-- ═════════ Main Section ═════════ --}}
        <section class="panel ls-card">
            <div class="ls-card__head">
                <span class="ls-tag">Main Section</span>
                <h2>Speaking Test Instructions</h2>
                <p class="muted">The first screen students see. Filled in by default for every test; you can change it here.</p>
            </div>
            <div class="field">
                <label for="ielts-intro">Instructions</label>
                <textarea id="ielts-intro" name="speaking[intro]" class="ls-editor" rows="8">{{ $content['intro'] }}</textarea>
            </div>
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => '"Start speaking now" audio (optional)', 'id' => 'ielts-cue-audio',
                'name' => 'speaking[cue]', 'value' => $content['cue'],
            ])
            <p class="hint">Plays after every question, followed by a beep; then recording starts.
                If you don't upload one, the student's browser says "Start speaking now" in its own voice.</p>
        </section>

        <h2 class="ls-parts-title">Questions</h2>

        @foreach (S::PARTS as $p => $part)
            @php $pc = $content['parts'][$p]; $count = count(S::questions($content, $p)); $missing = S::missingAudio($content, $p); @endphp
            <details class="part-bar" id="ielts-part-{{ $p }}" @if ($p === 1) open @endif>
                <summary>
                    <span class="part-bar__num">{{ $p }}</span>
                    <span class="part-bar__title">Part {{ $p }}: {{ $part['name'] }}
                        <small>{{ $part['many'] ? 'Each question is played as audio · up to 2 min to answer each' : 'Preparation 1 min · Speaking up to 2 min' }}</small></span>
                    <span class="part-bar__progress {{ $count && ! $missing ? 'is-done' : '' }}" data-part-progress>
                        @if ($part['many'])
                            {{ $count }} {{ \Illuminate\Support\Str::plural('question', $count) }}{{ $missing ? ' · ' . $missing . ' need audio' : '' }}
                        @else
                            {{ $count ? 'Ready' : 'No question yet' }}
                        @endif
                    </span>
                    @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
                </summary>
                <div class="part-bar__body">
                    <section class="ls-card ls-card--inner">
                        <div class="ls-card__head">
                            <span class="ls-tag">Section 1</span>
                            <h3>Part {{ $p }} instructions</h3>
                            <p class="muted">Shown before the questions. The student clicks <strong>Start {{ $p === 1 ? 'test' : 'Part ' . $p }}</strong> to begin.</p>
                        </div>
                        <div class="field">
                            <label for="ielts-part-{{ $p }}-intro">Instructions</label>
                            <textarea id="ielts-part-{{ $p }}-intro" name="speaking[parts][{{ $p }}][intro]" class="ls-editor" rows="6">{{ $pc['intro'] }}</textarea>
                        </div>
                    </section>

                    <section class="ls-card ls-card--inner">
                        <div class="ls-card__head">
                            <span class="ls-tag">Section 2</span>
                            @if ($part['many'])
                                <h3>Part {{ $p }} questions</h3>
                                <p class="muted">Upload an <strong>audio file</strong> for each question – students only hear it; it is not shown on screen.
                                    The text box is optional: it is for your records and is shown to the teacher when evaluating.
                                    Use <strong>+ Add a question</strong> for as many questions as you need.</p>
                            @else
                                <h3>Part 2 question (topic card)</h3>
                                <p class="muted">Shown on screen during the 1-minute preparation and the 2-minute recording.</p>
                            @endif
                        </div>

                        @if ($part['many'])
                            <div class="iq-list" data-q-list="{{ $p }}">
                                @foreach ($pc['questions'] as $i => $q)
                                    @include('admin.ielts.partials.question', ['p' => $p, 'key' => $i, 'q' => $q])
                                @endforeach
                            </div>
                            <template data-q-template="{{ $p }}">
                                @include('admin.ielts.partials.question', ['p' => $p, 'key' => '__KEY__', 'q' => ['text' => '', 'audio' => '']])
                            </template>
                            <button type="button" class="btn btn-ghost iq-add" data-q-add="{{ $p }}">
                                <span class="iq-add__plus" aria-hidden="true">+</span> Add a question
                            </button>
                        @else
                            <div class="field">
                                <label for="ielts-part-2-question">Question</label>
                                <textarea id="ielts-part-2-question" name="speaking[parts][2][question]" class="ls-editor" rows="6"
                                          placeholder="e.g. Describe a place you like to visit. You should say: …">{{ $pc['question'] }}</textarea>
                            </div>
                        @endif
                    </section>
                </div>
            </details>
        @endforeach

        <div class="save-bar">
            <span class="muted" data-save-status>Click Save to keep your changes.</span>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/admin-listening.js') }}?v=7"></script>
    <script>
        // "+ Add a question" and "Remove" for Parts 1 and 3
        (function () {
            let uid = Date.now();
            const textOf = t => {
                const ed = window.tinymce && tinymce.get(t.id);
                return (ed ? ed.getContent({ format: 'text' }) : t.value.replace(/<[^>]*>/g, '')).trim();
            };
            const renumber = list => {
                const items = list.querySelectorAll('[data-q-item]');
                let ready = 0, missing = 0;
                items.forEach((item, i) => {
                    item.querySelector('[data-q-label]').textContent = 'Question ' + (i + 1);
                    item.querySelector('[data-q-remove]').hidden = items.length < 2;   // always keep one box
                    const hasAudio = item.querySelector('[data-media-path]').value !== '';
                    const hasText = textOf(item.querySelector('textarea')) !== '';
                    if (hasAudio) ready++; else if (hasText) missing++;
                    item.querySelector('[data-q-warn]').hidden = hasAudio || !hasText;
                });
                const n = list.closest('details').querySelector('[data-part-progress]');
                n.textContent = ready + (ready === 1 ? ' question' : ' questions') + (missing ? ' · ' + missing + ' need audio' : '');
                n.classList.toggle('is-done', ready > 0 && !missing);
            };
            document.querySelectorAll('[data-q-list]').forEach(renumber);

            document.addEventListener('click', e => {
                const add = e.target.closest('[data-q-add]');
                if (add) {
                    const p = add.dataset.qAdd;
                    const list = document.querySelector(`[data-q-list="${p}"]`);
                    const tpl = document.querySelector(`[data-q-template="${p}"]`).innerHTML.replaceAll('__KEY__', 'n' + (uid++));
                    const wrap = document.createElement('div');
                    wrap.innerHTML = tpl.trim();
                    const item = wrap.firstElementChild;
                    list.appendChild(item);
                    if (window.EWBindMedia) item.querySelectorAll('[data-media]').forEach(EWBindMedia);
                    if (window.EWStartEditors) EWStartEditors(item);
                    if (window.EWMarkDirty) EWMarkDirty();
                    renumber(list);
                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                const rm = e.target.closest('[data-q-remove]');
                if (rm) {
                    const item = rm.closest('[data-q-item]');
                    const list = item.closest('[data-q-list]');
                    const ta = item.querySelector('textarea');
                    const hasSomething = textOf(ta) !== '' || item.querySelector('[data-media-path]').value !== '';
                    if (hasSomething && !confirm('Remove this question?')) return;
                    const ed = window.tinymce && tinymce.get(ta.id);
                    if (ed) ed.remove();
                    item.remove();
                    renumber(list);
                    if (window.EWMarkDirty) EWMarkDirty();
                }
            });
            // keep the counts up to date
            const refresh = el => { const l = el && el.closest && el.closest('[data-q-list]'); if (l) renumber(l); };
            document.addEventListener('media-change', e => refresh(e.target));
            document.addEventListener('input', e => refresh(e.target));
            if (window.tinymce) tinymce.on('AddEditor', ({ editor }) => editor.on('input change', () => refresh(editor.getElement())));
        })();
    </script>
@endpush
