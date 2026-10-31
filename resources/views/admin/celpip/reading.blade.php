@extends('admin.layout')

@php use App\Support\CelpipReading as R; @endphp

@section('title', 'Reading Questions')

@section('actions')
    <a href="{{ route('admin.celpip.vocab', [$test, 'reading']) }}" class="btn btn-sm btn-ghost">Vocabulary</a>
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

    {{-- Everything the page needs to draw the question sections (see public/js/admin-reading.js) --}}
    <script type="application/json" id="reading-data">@json(['content' => $content, 'types' => R::TYPES, 'defaults' => R::DEFAULT_INSTRUCTIONS])</script>

    <form method="POST" action="{{ route('admin.celpip.module.save', [$test, 'reading']) }}" id="reading-form"
          data-upload-url="{{ route('admin.celpip.media', $test) }}" data-upload-folder="reading">
        @csrf @method('PUT')
        <input type="hidden" name="reading_json" data-reading-json>

        {{-- ═════════ Main Section ═════════ --}}
        <section class="panel ls-card" id="id-reading-34">
            <div class="ls-card__head">
                <span class="ls-tag">Main Section</span>
                <h2>Reading Test Instructions</h2>
                <p class="muted">The first screen students see when they open the Reading test. Filled in by default for every test; you can change it here.</p>
            </div>
            <div class="field">
                <label for="reading-intro">Reading test instructions</label>
                <textarea id="reading-intro" class="ls-editor" rows="8">{{ $content['intro']['text'] }}</textarea>
            </div>
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => 'Reading instructions audio (optional)', 'id' => 'reading-intro-audio',
                'name' => 'ignored_intro_audio', 'value' => $content['intro']['audio'],
            ])
        </section>

        {{-- ═════════ Parts 1–4 ═════════ --}}
        <h2 class="ls-parts-title">Parts</h2>

        @foreach (R::PARTS as $p => $part)
            @php $progress = R::partProgress($content, $p); @endphp
            <details class="part-bar" id="id-reading-part-{{ $p }}" data-rd-part="{{ $p }}">
                <summary>
                    <span class="part-bar__num">{{ $p }}</span>
                    <span class="part-bar__title">Add Part {{ $p }} <small>{{ $part['name'] }} · {{ $part['minutes'] }} minutes</small></span>
                    <span class="part-bar__progress {{ $progress['total'] && $progress['done'] === $progress['total'] ? 'is-done' : '' }}" data-rd-progress>
                        {{ $progress['done'] }} / {{ $progress['total'] }} questions complete
                    </span>
                    @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
                </summary>

                <div class="part-bar__body">
                    <section class="ls-card ls-card--inner">
                        <div class="ls-card__head">
                            <span class="ls-tag">Passage</span>
                            <h3>Reading Part {{ $p }}: {{ $part['name'] }}</h3>
                            <p class="muted">Shown on the <strong>left</strong> side of the screen. You can add pictures and tables with the editor buttons
                                @if ($p === 3) – start each paragraph with its letter (A, B, C, D) @endif.</p>
                        </div>
                        <div class="field">
                            <label for="reading-part-{{ $p }}-passage">Passage</label>
                            <textarea id="reading-part-{{ $p }}-passage" class="rd-editor rd-editor--passage" rows="10">{{ $content['parts'][$p]['passage'] }}</textarea>
                        </div>
                    </section>

                    <div class="rd-sections" data-rd-sections></div>

                    <div class="rd-add">
                        <span class="muted-inline">Add a question section (shown on the <strong>right</strong>):</span>
                        <button type="button" class="btn btn-sm btn-ghost" data-rd-add="mcq">+ Multiple Choice</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-rd-add="blanks">+ Fill in the Blanks</button>
                        <button type="button" class="btn btn-sm btn-ghost" data-rd-add="matching">+ Matching Statements</button>
                    </div>
                </div>
            </details>
        @endforeach

        <div class="save-bar">
            <span class="muted" data-save-status>Pictures and audio are uploaded straight away; click Save to keep everything else.</span>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/admin-listening.js') }}?v=5"></script>
    <script src="{{ asset('js/admin-reading.js') }}?v=1"></script>
@endpush
