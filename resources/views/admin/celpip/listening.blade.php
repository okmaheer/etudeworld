@extends('admin.layout')

@php
    use App\Support\CelpipListening as L;
@endphp

@section('title', 'Listening Questions')

@section('actions')
    <a href="{{ route('admin.celpip.vocab', [$test, 'listening']) }}" class="btn btn-sm btn-ghost">Vocabulary</a>
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

    <form method="POST" action="{{ route('admin.celpip.module.save', [$test, 'listening']) }}" id="listening-form"
          data-upload-url="{{ route('admin.celpip.media', $test) }}">
        @csrf @method('PUT')

        {{-- ═════════ Main Section ═════════ --}}
        <section class="panel ls-card" id="id-34">
            <div class="ls-card__head">
                <span class="ls-tag">Main Section</span>
                <h2>Listening Test Instructions</h2>
                <p class="muted">The first screen students see when they open the Listening test. Filled in by default for every test; you can change it here.</p>
            </div>
            <div class="field">
                <label for="intro">Listening test instructions</label>
                <textarea id="intro" name="listening[intro][text]" class="ls-editor" rows="8">{{ $content['intro']['text'] }}</textarea>
            </div>
            @include('admin.celpip.partials.media', [
                'kind' => 'audio', 'label' => 'Listening instructions audio', 'id' => 'intro2',
                'name' => 'listening[intro][audio]', 'value' => $content['intro']['audio'],
            ])
        </section>

        {{-- ═════════ Parts 1–6 ═════════ --}}
        <h2 class="ls-parts-title">Parts</h2>

        @foreach (L::PARTS as $partNo => $partName)
            @if (isset(L::PART_DIVISIONS[$partNo]))
                @include('admin.celpip.partials.listening-part', [
                    'partNo' => $partNo, 'partName' => $partName, 'pc' => $content['parts'][$partNo],
                    'progress' => L::partProgress($content, $partNo),
                ])
            @else
                <details class="part-bar part-bar--later" id="id-part-{{ $partNo }}">
                    <summary>
                        <span class="part-bar__num">{{ $partNo }}</span>
                        <span class="part-bar__title">Add Part {{ $partNo }} <small>{{ $partName }}</small></span>
                        <span class="part-bar__progress">Coming next</span>
                        @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
                    </summary>
                    <div class="part-bar__body">
                        <p class="muted">Part {{ $partNo }} will be added next.</p>
                    </div>
                </details>
            @endif
        @endforeach

        <div class="save-bar">
            <span class="muted" data-save-status>Uploads are saved straight away; click Save to keep your text and answers.</span>
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
