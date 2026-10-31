@extends('admin.layout')

@php
    use App\Support\Toefl as T;
    $tabs = match ($module) {
        'reading' => [['k' => 'words', 'label' => 'Write the correct letter', 'types' => ['words']], ['k' => 'mc', 'label' => 'Multiple choice', 'types' => ['mc']]],
        'listening' => [['k' => 'add', 'label' => 'Add questions', 'types' => ['response', 'conversation', 'announcement', 'talk']]],
        'writing' => [['k' => 'build', 'label' => 'Complete the sentence', 'types' => ['build']], ['k' => 'email', 'label' => 'Add email', 'types' => ['email']], ['k' => 'discussion', 'label' => 'Add academic discussion', 'types' => ['discussion']]],
        'speaking' => [['k' => 'repeat', 'label' => 'Add listen and repeat questions', 'types' => ['repeat']], ['k' => 'interview', 'label' => 'Add interview questions', 'types' => ['interview']]],
    };
    $minuteLabels = [
        'reading' => ['1' => 'Module 1 (minutes)', '2' => 'Module 2 (minutes)'],
        'listening' => ['1' => 'Module 1 (minutes)', '2' => 'Module 2 (minutes)'],
        'writing' => ['build' => 'Build a Sentence (minutes)', 'email' => 'Write an Email (minutes)', 'discussion' => 'Academic Discussion (minutes)'],
        'speaking' => [],
    ][$module];
    $pageData = ['section' => $module, 'content' => $content, 'tabs' => $tabs, 'types' => T::TYPES[$module], 'modules' => $info['modules'], 'defaults' => ['seconds' => T::DEFAULT_SECONDS, 'response' => T::RESPONSE_STATEMENT]];
@endphp

@section('title', $info['label'] . ' – ' . $test->name)

@section('actions')
    <a href="{{ route('admin.toefl.index') }}" class="btn btn-sm btn-ghost">← All TOEFL Tests</a>
@endsection

@push('head')<link rel="stylesheet" href="{{ asset('css/admin-toefl.css') }}?v=3">@endpush

@section('content')
    <div class="module-tabs">
        @foreach (T::SECTIONS as $key => $mod)
            <a href="{{ route('admin.toefl.module', [$test, $key]) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">@include('admin.partials.icon', ['name' => $mod['icon']]) {{ $mod['label'] }}</a>
        @endforeach
    </div>

    @if (session('problems'))
        <div class="alert alert-error" role="alert">@include('admin.partials.icon', ['name' => 'warn'])
            <div><strong>Saved, but a few things are still missing:</strong><ul>@foreach (session('problems') as $p)<li>{{ $p }}</li>@endforeach</ul></div>
        </div>
    @endif

    <form method="POST" id="tf-form" action="{{ route('admin.toefl.module.save', [$test, $module]) }}" data-upload-url="{{ route('admin.toefl.media', $test) }}" data-section="{{ $module }}">
        @csrf @method('PUT')
        <input type="hidden" name="content_json" id="tf-json">

        <section class="panel">
            <h2 class="tf-h">{{ $info['label'] }} · address</h2>
            <div class="field">
                <label for="slug">URL <span class="req">*</span></label>
                <div class="url-input"><span class="url-prefix">/toefl/{{ $module }}/</span>
                    <input id="slug" name="slug" type="text" required maxlength="255" value="{{ old('slug', $row?->slug ?: $test->slug) }}"></div>
                @error('slug') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <p class="hint" style="margin-top:8px">The search title and description of this section are on the test’s edit page (Search results).</p>
        </section>

        <section class="panel tf-summary">
            <div>
                <strong>{{ $test->name }} · {{ $info['label'] }}</strong>
                <span class="muted" id="tf-counts">
                    @forelse ($counts as $m => $c){{ $m ? "Module $m" : 'All' }}: {{ $c['questions'] }} questions ({{ $c['graded'] }} graded)@if (! $loop->last) · @endif @empty No questions yet. @endforelse
                </span>
            </div>
            @if ($minuteLabels)
                <div class="tf-minutes">@foreach ($minuteLabels as $k => $label)<label>{{ $label }} <input type="number" min="0" max="120" data-minutes="{{ $k }}" value="{{ $content['minutes'][$k] ?? 0 }}"></label>@endforeach</div>
            @endif
        </section>

        <div class="tf-tabs" role="tablist">
            @foreach ($tabs as $i => $tab)<button type="button" class="tf-tab {{ $i === 0 ? 'is-active' : '' }}" data-tab="{{ $tab['k'] }}">{{ $tab['label'] }}</button>@endforeach
            <button type="button" class="tf-tab" data-tab="seq">Question sequence management</button>
        </div>

        @foreach ($tabs as $i => $tab)
            <div class="tf-pane" data-pane="{{ $tab['k'] }}" {{ $i === 0 ? '' : 'hidden' }}>
                <div class="tf-cards" data-cards="{{ $tab['k'] }}"></div>
                <div class="tf-add">@foreach ($tab['types'] as $t)<button type="button" class="btn btn-sm btn-ghost" data-add="{{ $t }}">+ Add {{ strtolower(T::TYPES[$module][$t]) }}</button>@endforeach</div>
            </div>
        @endforeach
        <div class="tf-pane" data-pane="seq" hidden><div id="tf-seq"></div></div>

        <div class="save-bar"><span class="muted">Click Save to keep your changes.</span><button class="btn btn-primary">Save {{ strtolower($info['label']) }}</button></div>
    </form>

    <script type="application/json" id="tf-data">{!! json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/admin-toefl.js') }}?v=9"></script>
@endpush
