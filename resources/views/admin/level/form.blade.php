@extends('admin.layout')

@php
    $editing = $test->exists;
    $auto = $editing ? $test->cutoffs(max($test->questionCount(), 1)) : [];
@endphp

@section('title', $editing ? 'Edit English Level Test' : 'Add English Level Test')

@section('actions')
    <a href="{{ route('admin.level.index') }}" class="btn btn-sm btn-ghost">All level tests</a>
@endsection

@section('content')
    @if ($editing)
        <div class="panel">
            <div class="panel-head"><h2>Questions ({{ $test->questionCount() }} in total)</h2></div>
            <div class="rda-buttons">
                @foreach ([1, 2, 3] as $n)
                    @php $count = $test->section($n)->questions()->count(); @endphp
                    <a href="{{ route('admin.level.section', [$test, $n]) }}" class="btn btn-primary">
                        {{ $count ? 'Section ' . $n : 'Add Section ' . $n }} ({{ $count }} {{ Str::plural('question', $count) }})
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" class="panel form-narrow" data-seo-form
          action="{{ $editing ? route('admin.level.update', $test) : route('admin.level.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="field">
            <label for="title">Test name <span class="req">*</span></label>
            <input id="title" name="title" type="text" required maxlength="255" class="input-lg"
                   value="{{ old('title', $test->title) }}" placeholder="e.g. English Level Test" data-slug-source>
            @error('title') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="instructions">Test instructions</label>
            <textarea id="instructions" name="instructions" rows="4" placeholder="Shown to the student before the test (for example: no dictionary, about 3 minutes).">{{ old('instructions', $test->instructions) }}</textarea>
        </div>

        <div class="field">
            <label for="slug">Test URL</label>
            <div class="url-input">
                <span class="url-prefix">/english-level-test/</span>
                <input id="slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $test->slug) }}"
                       placeholder="auto-made-from-test-name" data-slug-target {{ $editing ? 'data-slug-locked' : '' }}>
            </div>
            @error('slug') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="meta_title">Meta title</label>
            <input id="meta_title" name="meta_title" type="text" maxlength="255" value="{{ old('meta_title', $test->meta_title) }}"
                   placeholder="Leave empty to use the test name" data-counter="60">
            <div class="meter"><span data-meter-for="meta_title"></span></div>
            <p class="hint" data-count-for="meta_title">Best length: 50–60 characters.</p>
        </div>

        <div class="field">
            <label for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description" rows="3" maxlength="500"
                      placeholder="A short summary shown under the title in Google results" data-counter="160">{{ old('meta_description', $test->meta_description) }}</textarea>
            <div class="meter"><span data-meter-for="meta_description"></span></div>
            <p class="hint" data-count-for="meta_description">Best length: 120–160 characters.</p>
        </div>

        @if ($editing)
            <div class="field">
                <label>Level cut-offs (optional)</label>
                <p class="hint">Lowest score needed for each level. Leave empty to use the automatic scale (based on the guide for 40 questions, scaled to your number of questions). Automatic values for {{ max($test->questionCount(), 1) }} questions are shown in grey.</p>
                <div class="field-row" style="grid-template-columns:repeat(5,1fr)">
                    @foreach (['A2', 'B1', 'B2', 'C1', 'C2'] as $lv)
                        <div><label for="th-{{ $lv }}">{{ $lv }} from</label>
                            <input id="th-{{ $lv }}" name="thresholds[{{ $lv }}]" type="number" min="0" value="{{ old('thresholds.' . $lv, $test->thresholds[$lv] ?? '') }}" placeholder="{{ $auto[$lv] }}"></div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="field">
            <label>Status <span class="req">*</span></label>
            <div class="status-options">
                <label class="status-option">
                    <input type="radio" name="status" value="active" @checked(old('status', $test->status) === 'active')>
                    <span><strong>Active</strong><small>Shown on the live website</small></span>
                </label>
                <label class="status-option">
                    <input type="radio" name="status" value="draft" @checked(old('status', $test->status) === 'draft')>
                    <span><strong>Draft</strong><small>Hidden from the live website</small></span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.level.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create test' }}</button>
        </div>
    </form>
@endsection
