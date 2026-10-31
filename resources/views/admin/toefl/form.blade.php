@extends('admin.layout')

@php $editing = $test->exists; @endphp

@section('title', $editing ? 'Edit TOEFL Test' : 'Add a TOEFL Test')

@section('actions')
    <a href="{{ route('admin.toefl.index') }}" class="btn btn-sm btn-ghost">All TOEFL Tests</a>
@endsection

@section('content')
    <form method="POST" class="panel form-narrow" data-seo-form action="{{ $editing ? route('admin.toefl.update', $test) : route('admin.toefl.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="field">
            <label for="name">Test name <span class="req">*</span></label>
            <input id="name" name="name" type="text" required maxlength="255" class="input-lg" value="{{ old('name', $test->name) }}" placeholder="e.g. TOEFL iBT Practice Test 1" data-slug-source>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="slug">URL <span class="req">*</span></label>
            <div class="url-input">
                <span class="url-prefix">/toefl/</span>
                <input id="slug" name="slug" type="text" maxlength="255" value="{{ old('slug', $test->slug) }}" placeholder="auto-made-from-test-name" data-slug-target {{ $editing ? 'data-slug-locked' : '' }}>
            </div>
            <p class="hint">Used for the full mock test. Each section (Reading, Listening…) has its own URL on its own page. Meta titles and descriptions are in “Search results (SEO)” below.</p>
            @error('slug') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label>Test type <span class="req">*</span></label>
            <div class="segmented">
                @foreach (\App\Models\ToeflTest::TYPES as $value => $label)
                    <label><input type="radio" name="type" value="{{ $value }}" required @checked(old('type', $test->type) === $value)><span>{{ $label }}</span></label>
                @endforeach
            </div>
            @error('type') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label>Status <span class="req">*</span></label>
            <div class="status-options">
                <label class="status-option"><input type="radio" name="status" value="active" @checked(old('status', $test->status) === 'active')><span><strong>Active</strong><small>Shown on the live website</small></span></label>
                <label class="status-option"><input type="radio" name="status" value="draft" @checked(old('status', $test->status) === 'draft')><span><strong>Draft</strong><small>Hidden from the live website</small></span></label>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.toefl.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add test' }}</button>
        </div>
    </form>

    @if ($editing)
        @include('admin.partials.test-seo', ['exam' => 'toefl', 'test' => $test, 'pages' => array_keys(\App\Support\Toefl::PUBLIC)])
    @endif
@endsection
