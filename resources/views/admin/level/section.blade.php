@extends('admin.layout')

@section('title', 'Section ' . $n . ' – ' . $test->title)

@section('actions')
    <a href="{{ route('admin.level.edit', $test) }}" class="btn btn-sm btn-ghost">← Test details</a>
@endsection

@section('content')
    <div class="tabs">
        @foreach ([1, 2, 3] as $i)
            <a href="{{ route('admin.level.section', [$test, $i]) }}" class="tab {{ $i === $n ? 'is-active' : '' }}">Section {{ $i }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.level.section.save', [$test, $n]) }}" class="panel">
        @csrf @method('PUT')
        <div class="panel-head"><h2>Section {{ $n }} instructions{{ $n === 3 ? ' and reading passages' : '' }}</h2></div>
        <div class="field">
            <label for="instructions">Instructions (shown above the questions)</label>
            <textarea id="instructions" name="instructions" rows="3" placeholder="e.g. Choose the word or phrase that best completes the sentence.">{{ old('instructions', $section->instructions) }}</textarea>
        </div>
        @if ($n === 3)
            @foreach ([1, 2] as $i)
                <div class="field">
                    <label for="p{{ $i }}t">Reading passage {{ $i }} – title</label>
                    <input id="p{{ $i }}t" name="passages[{{ $i }}][title]" type="text" maxlength="255" value="{{ old('passages.' . $i . '.title', $section->passages[$i]['title'] ?? '') }}" placeholder="e.g. My Family">
                </div>
                <div class="field">
                    <label for="p{{ $i }}b">Reading passage {{ $i }} – text</label>
                    <textarea id="p{{ $i }}b" name="passages[{{ $i }}][body]" rows="9" placeholder="Leave a blank line between paragraphs.">{{ old('passages.' . $i . '.body', $section->passages[$i]['body'] ?? '') }}</textarea>
                </div>
            @endforeach
        @endif
        <div class="form-actions"><button type="submit" class="btn btn-primary">Save section</button></div>
    </form>

    <div class="panel">
        <div class="panel-head"><h2>Questions in this section ({{ $questions->count() }})</h2></div>
        @forelse ($questions as $qq)
            <div class="rda-item__head" style="padding:10px 0;border-bottom:1px solid var(--line);display:flex;gap:12px;align-items:center">
                <div style="flex:1">
                    <strong>{{ $loop->iteration }}.</strong> {{ \Illuminate\Support\Str::limit($qq->prompt, 110) }}
                    <div class="meta-line">
                        <span class="badge badge-blue">{{ $qq->type === 'mcq' ? 'Multiple choice' : 'Fill in the blank' }}</span>
                        @if ($qq->passage) <span class="badge badge-grey">Passage {{ $qq->passage }}</span> @endif
                        @if ($qq->level) <span class="badge badge-teal">{{ $qq->level }}</span> @endif
                        <span class="muted-inline">Answer: {{ $qq->answerText() }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.level.section', [$test, $n, 'q' => $qq->id]) }}#question-form" class="btn btn-sm btn-ghost">Edit</a>
                <form method="POST" action="{{ route('admin.level.question.destroy', [$test, $qq]) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this question?">Delete</button>
                </form>
            </div>
        @empty
            <p class="muted">No questions yet. Add the first one below.</p>
        @endforelse
    </div>

    <form method="POST" id="question-form" action="{{ route('admin.level.question.save', [$test, $n]) }}" class="panel">
        @csrf
        <input type="hidden" name="id" value="{{ $q->id }}">
        <div class="panel-head"><h2>{{ $q->exists ? 'Edit question' : 'Add a question' }}</h2>
            @if ($q->exists)<a href="{{ route('admin.level.section', [$test, $n]) }}" class="btn btn-sm btn-ghost">Cancel editing</a>@endif
        </div>

        <div class="field">
            <label>Question type</label>
            <div class="segmented">
                <label><input type="radio" name="type" value="mcq" @checked(old('type', $q->type) === 'mcq') data-qtype><span>Multiple choice</span></label>
                <label><input type="radio" name="type" value="fitb" @checked(old('type', $q->type) === 'fitb') data-qtype><span>Fill in the blank</span></label>
            </div>
        </div>

        @if ($n === 3)
            <div class="field">
                <label for="passage">Belongs to</label>
                <select id="passage" name="passage">
                    <option value="">After the passages (no passage)</option>
                    <option value="1" @selected(old('passage', $q->passage) == 1)>Reading passage 1</option>
                    <option value="2" @selected(old('passage', $q->passage) == 2)>Reading passage 2</option>
                </select>
            </div>
        @endif

        <div class="field">
            <label for="prompt">Question <span class="req">*</span></label>
            <textarea id="prompt" name="prompt" rows="3" required placeholder="Type the question. For fill in the blank, write ___ where the answer goes.">{{ old('prompt', $q->prompt) }}</textarea>
            @error('prompt') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div data-for="mcq">
            <div class="field">
                <label>Four options – tick the correct one</label>
                @foreach (range(0, 3) as $i)
                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
                        <input type="radio" name="correct" value="{{ $i }}" aria-label="Correct answer {{ chr(97 + $i) }}" @checked((string) old('correct', $q->type === 'mcq' ? $q->answer : '') === (string) $i)>
                        <strong>{{ chr(97 + $i) }})</strong>
                        <input type="text" name="options[]" maxlength="500" style="flex:1" value="{{ old('options.' . $i, $q->options[$i] ?? '') }}">
                    </div>
                @endforeach
                @error('options') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div data-for="fitb">
            <div class="field">
                <label for="answer">Correct answer</label>
                <input id="answer" name="answer" type="text" maxlength="500" value="{{ old('answer', $q->type === 'fitb' ? $q->answer : '') }}" placeholder="e.g. goes   (several accepted answers: goes | go)">
                <p class="hint">Capital letters and full stops are ignored when marking.</p>
                @error('answer') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="level">Level of this question (note for you, not shown)</label>
                <select id="level" name="level"><option value="">–</option>
                    @foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $lv)<option @selected(old('level', $q->level) === $lv)>{{ $lv }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="position">Order number</label>
                <input id="position" name="position" type="number" min="0" max="999" value="{{ old('position', $q->exists ? $q->position : '') }}" placeholder="Empty = add at the end">
            </div>
        </div>

        <div class="form-actions"><button type="submit" class="btn btn-primary">{{ $q->exists ? 'Save question' : 'Add question' }}</button></div>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const sync = () => {
                const t = document.querySelector('[data-qtype]:checked')?.value || 'mcq';
                document.querySelectorAll('[data-for]').forEach(el => el.hidden = el.dataset.for !== t);
            };
            document.querySelectorAll('[data-qtype]').forEach(r => r.addEventListener('change', sync));
            sync();
        })();
    </script>
@endpush
