@extends('admin.layout')

@php use App\Support\CelpipSpeaking as S; @endphp

@section('title', 'Speaking Questions')

@section('actions')
    <a href="{{ route('admin.celpip.index') }}" class="btn btn-sm btn-ghost">← All CELPIP Tests</a>
@endsection

@section('content')
    <div class="module-tabs">
        @foreach (\App\Models\CelpipTest::MODULES as $key => $mod)
            @if ($test->hasModule($key))
                <a href="{{ route('admin.celpip.module', [$test, $key]) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">
                    @include('admin.partials.icon', ['name' => $mod['icon']]) {{ $mod['label'] }}
                </a>
            @endif
        @endforeach
    </div>

    <p class="module-context">
        <strong>{{ $test->name }}</strong>
        <span class="badge badge-blue">{{ $test->format === 'ls' ? 'LS' : 'General' }}</span>
        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
    </p>

    {{-- Uses the same page script as Listening (editors, uploads, unsaved-changes warning) --}}
    <form method="POST" action="{{ route('admin.celpip.module.save', [$test, 'speaking']) }}" id="listening-form"
          data-upload-url="{{ route('admin.celpip.media', $test) }}" data-upload-folder="speaking">
        @csrf @method('PUT')

        {{-- ═════════ Main Section ═════════ --}}
        <section class="panel ls-card" id="id-speaking-34">
            <div class="ls-card__head">
                <span class="ls-tag">Main Section</span>
                <h2>Speaking Test Instructions</h2>
                <p class="muted">The first screen students see when they open the Speaking test. Filled in by default for every test; you can change it here.</p>
            </div>
            <div class="field">
                <label for="speaking-intro">Speaking test instructions</label>
                <textarea id="speaking-intro" name="speaking[intro][text]" class="ls-editor" rows="8">{{ $content['intro']['text'] }}</textarea>
            </div>
<div class="ls-media-row">
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => 'Speaking instructions audio (optional)', 'id' => 'speaking-intro-audio',
                'name' => 'speaking[intro][audio]', 'value' => $content['intro']['audio'],
            ])
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => '"Start speaking now" audio (optional)', 'id' => 'speaking-cue-audio',
                'name' => 'speaking[intro][cue]', 'value' => $content['intro']['cue'],
            ])
            </div>
            <p class="hint">"Start speaking now" plays before every recording, and recording starts when it ends.
                If you don't upload one, the student's browser says "Start speaking now" in its own voice.</p>
        </section>

        {{-- ═════════ Tasks 1–8 ═════════ --}}
        <h2 class="ls-parts-title">Questions</h2>

        @foreach (S::TASKS as $t => $task)
            @php $tc = $content['tasks'][$t]; $ready = S::taskReady($t, $tc); $f = $task['fields']; @endphp
            <details class="part-bar" id="id-speaking-task-{{ $t }}" @if ($t === 1) open @endif>
                <summary>
                    <span class="part-bar__num">{{ $t }}</span>
                    <span class="part-bar__title">Task {{ $t }}: {{ $task['name'] }}
                        <small>@isset($task['choose']) Choose an option {{ $task['choose'] }} s · @endisset Preparation {{ $task['prep'] }} s · Recording {{ $task['record'] }} s</small></span>
                    <span class="part-bar__progress {{ $ready ? 'is-done' : '' }}">{{ $ready ? 'Ready' : ($t === 5 ? 'Add both pictures' : 'No question yet') }}</span>
                    @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
                </summary>
                <div class="part-bar__body">
                    @if (in_array('intro', $f, true))
                        <section class="ls-card ls-card--inner">
                            <div class="ls-card__head">
                                <span class="ls-tag">Section 1</span>
                                <h3>Task {{ $t }} instructions</h3>
                                <p class="muted">Shown on its own screen before the task. Filled in by default; the student clicks Next to continue.</p>
                            </div>
                            <div class="field">
                                <label for="speaking-task-{{ $t }}-intro">Instructions</label>
                                <textarea id="speaking-task-{{ $t }}-intro" name="speaking[tasks][{{ $t }}][intro]" class="ls-editor" rows="6">{{ $tc['intro'] }}</textarea>
                            </div>
                        </section>
                    @endif

                    <section class="ls-card ls-card--inner">
                        <div class="ls-card__head">
                            <span class="ls-tag">{{ in_array('intro', $f, true) ? 'Section 2' : 'Question' }}</span>
                            <h3>Speaking Task {{ $t }}: {{ $task['name'] }}</h3>
                            <p class="muted">
                                @if ($t === 5)
                                    The two options are shown side by side. The student has {{ $task['choose'] }} seconds to click one (otherwise the computer picks one),
                                    then {{ $task['prep'] }} seconds to prepare and {{ $task['record'] }} seconds to speak – all on the same screen.
                                @else
                                    The student gets {{ $task['prep'] }} seconds to prepare and {{ $task['record'] }} seconds to speak, on the same screen.
                                    @if ($t === 4) If you don't add a picture here, Task 3's picture is used. @endif
                                @endif
                            </p>
                        </div>
                        <div class="field">
                            <label for="speaking-task-{{ $t }}">Task {{ $t }} question</label>
                            <textarea id="speaking-task-{{ $t }}" name="speaking[tasks][{{ $t }}][text]" class="ls-editor" rows="5">{{ $tc['text'] }}</textarea>
                        </div>
                        @if (in_array('image', $f, true))
                            @include('admin.celpip.partials.media', [
                                'kind' => 'image', 'label' => 'Picture (shown on the left)', 'id' => 'speaking-task-' . $t . '-image',
                                'name' => 'speaking[tasks][' . $t . '][image]', 'value' => $tc['image'],
                            ])
                        @endif
                        @if ($t === 5)
                            <div class="sp-options">
                                @foreach ([1, 2] as $n)
                                    <div class="sp-option">
                                        @include('admin.celpip.partials.media', [
                                            'kind' => 'image', 'label' => 'Option ' . $n . ' – picture', 'id' => 'speaking-task-5-image' . $n,
                                            'name' => 'speaking[tasks][5][image' . $n . ']', 'value' => $tc['image' . $n],
                                        ])
                                        <div class="field">
                                            <label for="speaking-task-5-caption{{ $n }}">Option {{ $n }} – details under the picture <span class="muted-inline">(optional)</span></label>
                                            <textarea id="speaking-task-5-caption{{ $n }}" name="speaking[tasks][5][caption{{ $n }}]" class="ls-editor ls-editor--small" rows="4"
                                                      placeholder="e.g. New Downtown Townhouse – $250,000 – 3 bedrooms and 1 bathroom…">{{ $tc['caption' . $n] }}</textarea>
                                        </div>
                                    </div>
                                @endforeach
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
    <script src="{{ asset('js/admin-listening.js') }}?v=5"></script>
@endpush
