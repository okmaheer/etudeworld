@extends('admin.layout')

@php $editing = $test->exists; @endphp

@section('title', $editing ? 'Edit CELPIP Test' : 'Add a CELPIP Test')

@section('actions')
    <a href="{{ route('admin.celpip.index') }}" class="btn btn-sm btn-ghost">All CELPIP Tests</a>
@endsection

@section('content')
    <form method="POST" id="add-test-id" class="panel form-narrow" data-seo-form
          action="{{ $editing ? route('admin.celpip.update', $test) : route('admin.celpip.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="field">
            <label for="name">Test name <span class="req">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255" class="input-lg"
                   value="{{ old('name', $test->name) }}" placeholder="e.g. CELPIP General Practice Test 1" data-slug-source>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="slug">URL <span class="req">*</span></label>
            <div class="url-input">
                <span class="url-prefix">/celpip/</span>
                <input id="slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $test->slug) }}"
                       placeholder="auto-made-from-test-name" data-slug-target {{ $editing ? 'data-slug-locked' : '' }}>
            </div>
            @error('slug') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label>Test format <span class="req">*</span></label>
                <div class="segmented">
                    @foreach (\App\Models\CelpipTest::FORMATS as $value => $label)
                        <label>
                            <input type="radio" name="format" value="{{ $value }}" required @checked(old('format', $test->format) === $value)>
                            <span>{{ $value === 'ls' ? 'LS' : 'General' }}</span>
                        </label>
                    @endforeach
                </div>
                @error('format') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label>Test type <span class="req">*</span></label>
                <div class="segmented">
                    @foreach (\App\Models\CelpipTest::TYPES as $value => $label)
                        <label>
                            <input type="radio" name="type" value="{{ $value }}" required @checked(old('type', $test->type) === $value)>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        @unless ($editing)
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
        @endunless

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
            <a href="{{ route('admin.celpip.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add test' }}</button>
        </div>
    </form>

    @if ($editing)
        @include('admin.partials.test-seo', ['exam' => 'celpip', 'test' => $test, 'pages' => array_keys(\App\Models\CelpipTest::PUBLIC_MODULES)])
    @endif
@endsection
