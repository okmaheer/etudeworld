@extends('admin.layout')

{{--
    One form for every question type (FITB, flow chart, table, paragraph, synonyms, TRUE/FALSE, MCQ, headings, matching, MCQ 5 options).
    public/js/admin-ielts-reading.js builds the questions, the answers panel and the question numbers,
    then sends everything as one JSON value ("group_json").
--}}
@php
    $isL = $module === 'listening';
    $rdaData = ['group' => $group, 'starts' => $starts, 'letters' => $letters, 'mcqOptions' => $R::MCQ_OPTIONS, 'imageUrl' => $isL ? route('admin.ielts.listening.image', $test) : null];
    $blankTypes = $R::BLANK_TYPES;
    $itemWord = match ($type) { 'fitb_flow' => 'statement', 'headings' => 'heading', 'matching' => 'statement', 'matching_items' => 'statement', 'matching_names' => 'statement', 'drag' => 'question', 'map' => 'question', 'tfng' => 'statement', default => 'question' };
@endphp

@section('title', ($isNew ? '' : 'Edit: ') . preg_replace('/^Add /', 'Add ', $info['button']))

@section('actions')
    <a href="{{ route('admin.ielts.' . $module, $test) }}" class="btn btn-sm btn-ghost">← {{ $isL ? 'Listening' : 'Reading' }} questions</a>
@endsection

@section('content')
    @include('admin.ielts.reading._head')

    <form method="POST" action="{{ route('admin.ielts.' . $module . '.save', $test) }}" id="rda-form" data-type="{{ $type }}">
        @csrf
        <input type="hidden" name="group_json" data-group-json>
        <script type="application/json" data-rda-data>@json($rdaData)</script>

        {{-- [1] Passage --}}
        <section class="panel rda-step">
            <h2><span class="option-badge">1</span> {{ $isL ? 'Which part are these questions for?' : 'Which passage / section are these questions for?' }}</h2>
            <div class="rda-passages">
                @foreach ($R::PARTS as $p)
                    <label class="rda-radio">
                        <input type="radio" name="passage" value="{{ $p }}" @checked($group['passage'] === $p) data-passage>
                        <span>{{ $isL ? 'Part' : 'Passage / Section' }} {{ $p }} <small class="muted">{{ $isL || $hasText[$p] ? 'questions start at ' . $starts[$p] : '(no text yet)' }}</small></span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- [2] Instructions --}}
        <section class="panel rda-step">
            <h2><span class="option-badge">2</span> Question instructions</h2>
            <p class="muted">Shown above the questions, under “Questions <span data-range>…</span>”. Filled in by default – change them as needed.</p>
            <textarea id="rda-instructions" class="rda-editor" rows="4">{{ $group['instructions'] }}</textarea>
            @if ($type === 'tfng')
                <div class="field rda-inline">
                    <label for="rda-set">Answer choices</label>
                    <select id="rda-set" data-set>
                        <option value="tf" @selected(($group['set'] ?? 'tf') === 'tf')>TRUE / FALSE / NOT GIVEN</option>
                        <option value="yn" @selected(($group['set'] ?? 'tf') === 'yn')>YES / NO / NOT GIVEN</option>
                    </select>
                </div>
            @endif
            @if (in_array($type, ['matching', 'map'], true))
                <div class="field rda-inline">
                    <label for="rda-letters">Answer letters (shown along the top of the table)</label>
                    <select id="rda-letters" data-letters-count>
                        @for ($n = 2; $n <= 12; $n++)
                            <option value="{{ $n }}" @selected(($group['letters'] ?? 5) === $n)>A – {{ chr(64 + $n) }}</option>
                        @endfor
                    </select>
                </div>
            @endif
        </section>

        @if ($type === 'map')
            {{-- [3] Image (shown on the left, under the instructions) --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">3</span> Image</h2>
                <p class="muted">The map or picture. On the website it is shown on the left, under the instructions.</p>
                <div class="rda-image" data-map-box>
                    <img data-map-img alt="" @if (empty($group['image'])) hidden @else src="{{ asset($group['image']) }}" @endif>
                    <input type="file" accept="image/*" data-map-file>
                    <button type="button" class="btn btn-sm btn-ghost" data-map-remove @if (empty($group['image'])) hidden @endif>Remove image</button>
                    <span class="muted" data-map-status></span>
                </div>
            </section>
        @endif

        @if ($type === 'matching_items')
            {{-- [3] Items (the answer options along the top) --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">3</span> Items / options</h2>
                <p class="muted">Each item becomes a column of the table (from the second cell of the first row). Each one is an answer option – they are lettered A, B, C… for you here.</p>
                <div class="rda-items" data-options></div>
                <button type="button" class="btn btn-ghost iq-add" data-add-option><span class="iq-add__plus" aria-hidden="true">+</span> Add an item</button>
            </section>
            {{-- [4] First cell --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">4</span> Text for the first cell</h2>
                <p class="muted">Shown in the top-left cell of the table (for example “Statements”).</p>
                <textarea id="rda-corner" class="rda-editor" rows="2">{{ $group['corner'] ?? '' }}</textarea>
            </section>
        @endif

        @if ($type === 'fitb_syn')
            {{-- [3] Text with blanks --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">3</span> Text</h2>
                <p class="muted">Write the paragraph and put <strong>#</strong> wherever a blank should be. Each # is one question worth one mark. On the website it is shown as one paragraph.</p>
                <textarea id="rda-syn-text" class="rda-editor" rows="8">{{ $group['text'] ?? '' }}</textarea>
            </section>
            {{-- [4] Answers (one row per #) --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">4</span> Answers</h2>
                <p class="muted">One row appears for each # above. Choose the correct word from your list of options (step 5).</p>
                <div class="rda-answers" data-answers></div>
            </section>
            {{-- [5] Options (the lettered list shown in one box) --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">5</span> Options</h2>
                <p class="muted">The list of words the students choose from. They are lettered A, B, C… and shown together in one box. You can add <strong>more options than questions</strong> – the extra ones just stay unused.</p>
                <div class="rda-items" data-options data-word="Option"></div>
                <button type="button" class="btn btn-ghost iq-add" data-add-option><span class="iq-add__plus" aria-hidden="true">+</span> Add an option</button>
            </section>
        @else
        {{-- [3] Questions --}}
        <section class="panel rda-step">
            @if ($type === 'fitb_table')
                <h2><span class="option-badge">3</span> Table</h2>
                <p class="muted">Choose the number of rows and columns and click <strong>Generate table</strong>. Then type in the cells (bold, bullet points…) and put
                    <strong>#</strong> wherever a blank should be. Each # is one question.</p>
                <div class="rda-gen">
                    <label>Rows <input type="number" min="1" max="20" value="4" data-rows></label>
                    <label>Columns <input type="number" min="1" max="8" value="3" data-cols></label>
                    <button type="button" class="btn btn-ghost" data-generate>Generate table</button>
                </div>
                <textarea id="rda-table" class="rda-editor rda-editor--table" rows="10">{{ $group['table'] ?? '' }}</textarea>
            @else
                <h2><span class="option-badge">{{ $type === 'matching_items' ? 5 : ($type === 'map' ? 4 : 3) }}</span> {{ match ($type) {
                    'fitb_flow' => 'Statements', 'headings' => 'Headings', 'matching' => 'Statements', 'matching_items' => 'Statements', 'matching_names' => 'Statements', 'map' => 'Questions', 'tfng' => 'Statements', default => 'Questions' } }}</h2>
                <p class="muted">
                    @switch($type)
                        @case('fitb') Write each question and put <strong>#</strong> where the blank goes. Each question is one mark – if it has two blanks, both must be right. Questions are shown one under the other, left-aligned. @break
                        @case('fitb_syn') @break
                        @case('fitb_para') Write each sentence and put <strong>#</strong> where the blank goes. On the website the sentences are joined into one paragraph. Each sentence with blanks is one question. @break
                        @case('fitb_flow') Write each box of the flow chart; put <strong>#</strong> where a blank goes. A box without # is shown but has no question. Tick <em>Add ↓ arrow</em> to show an arrow below a box. @break
                        @case('tfng') Write each statement. Choose the correct answer for each one in the answers panel below. @break
                        @case('mcq') Write each question, its {{ $R::MCQ_OPTIONS === 3 ? 'three' : 'four' }} options, and click the circle next to the correct option. @break
                        @case('mcq5') Write each question and its five options, and tick the <strong>two</strong> correct options. Each question is worth two marks (two question numbers). @break
                        @case('headings') Write <strong>all</strong> the headings – you can add more headings than paragraphs. For each heading, choose the paragraph it belongs to,
                            or <em>Not used</em> for the extra headings. Each lettered paragraph ([A], [B]… in the passage) gets a numbered box above it – those boxes are the questions. @break
                        @case('map') Write each question statement and choose its correct letter. On the website the statements and letters form a table on the right and the student clicks a cell. @break
                        @case('drag') Write each question. For each one, choose which option is correct in the list below. On the website the questions appear on the left with an empty box, and the student drags an option into it. @break
                        @case('matching_names') Write each statement (one mark each) with the <strong>+</strong> button. On the website they are shown one under the other, each with a small box where the student types a letter. Choose the correct letters in step 4 and write the list of names in step 5. @break
                        @case('matching_items') Write each statement (one mark each) and choose its correct item. They appear down the first column; the student clicks a cell. @break
                        @case('matching') Write each statement and choose its correct letter. On the website the statements and letters form a table and the student clicks a cell. @break
                    @endswitch
                </p>
                <div class="rda-items" data-items></div>
                <button type="button" class="btn btn-ghost iq-add" data-add><span class="iq-add__plus" aria-hidden="true">+</span> Add a {{ $itemWord }}</button>
                <div class="rda-hsum" data-heading-summary hidden></div>
                <p class="rda-warn" data-letters-warning hidden>This passage has no paragraph letters yet. Add [A], [B]… at the start of its paragraphs (Edit passage) – each lettered paragraph becomes one question.</p>
            @endif
        </section>

        @endif

        @if ($type === 'drag')
            <section class="panel rda-step">
                <h2><span class="option-badge">3b</span> Headings (optional)</h2>
                <p class="muted">A short title shown above the questions (left) and above the options (right).</p>
                <label class="rda-small">Heading above the questions <input type="text" class="input" data-qhead maxlength="150" value="{{ $group['qhead'] ?? '' }}"></label>
                <label class="rda-small">Heading above the options <input type="text" class="input" data-ohead maxlength="150" value="{{ $group['ohead'] ?? '' }}"></label>
            </section>
            {{-- [4] Options (the draggable list on the right) --}}
            <section class="panel rda-step">
                <h2><span class="option-badge">4</span> Options</h2>
                <p class="muted">The list students drag from (shown on the right). Add <strong>more options than questions</strong> – the extra ones simply stay unused. They are lettered A, B, C… for you here; choose each question's correct option above.</p>
                <div class="rda-items" data-options data-word="Option"></div>
                <button type="button" class="btn btn-ghost iq-add" data-add-option><span class="iq-add__plus" aria-hidden="true">+</span> Add an option</button>
            </section>
        @endif

        {{-- [4] Answers --}}
        @if ($type === 'matching_names')
            <section class="panel rda-step">
                <h2><span class="option-badge">4</span> Answers</h2>
                <p class="muted">One row appears for each statement above. Choose the correct letter from your list (step 5). The same letter can be used more than once.</p>
                <div class="rda-answers" data-answers></div>
            </section>
            <section class="panel rda-step">
                <h2><span class="option-badge">5</span> Options</h2>
                <p class="muted">The list of names the students choose from. They are lettered A, B, C… and shown together in one box, one under the other. The number of options does not have to match the number of statements.</p>
                <label class="rda-small">Heading of the list (optional) <input type="text" class="input" data-ohead maxlength="150" value="{{ $isNew ? 'List of people' : ($group['ohead'] ?? '') }}"></label>
                <div class="rda-items" data-options data-word="Option"></div>
                <button type="button" class="btn btn-ghost iq-add" data-add-option><span class="iq-add__plus" aria-hidden="true">+</span> Add an option</button>
            </section>
        @endif

        @if (in_array($type, $blankTypes, true) || $type === 'tfng')
            <section class="panel rda-step">
                <h2><span class="option-badge">4</span> Answers</h2>
                <p class="muted">
                    @if ($type === 'tfng') Click the correct answer for each statement.
                    @else One box for each # above. If more than one answer is accepted, separate them with <strong>/</strong> – e.g. <code>colour / color</code>. Capital letters don't matter.
                    @endif
                </p>
                <div class="rda-answers" data-answers></div>
            </section>
        @endif

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
    <script>window.EW_EDITOR_IMAGE_URL = @json(route('admin.blogs.editor-image'));</script>
    <script src="{{ asset('js/admin-ielts-reading.js') }}?v=11"></script>
@endpush
