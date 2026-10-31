@extends('admin.layout')

@php $editing = $item->exists; @endphp
@section('title', $editing ? 'Edit student story' : 'Add student story')

@section('actions')
    <a href="{{ route('admin.achievements.index') }}" class="btn btn-sm btn-ghost">All stories</a>
@endsection

@section('content')
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.achievements.update', $item) : route('admin.achievements.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="name">Student name <span class="req">*</span></label>
                        <input id="name" name="name" type="text" required maxlength="120" class="input-lg" value="{{ old('name', $item->name) }}" placeholder="e.g. Sara K.">
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="exam">Exam</label>
                            <input id="exam" name="exam" type="text" maxlength="120" value="{{ old('exam', $item->exam) }}" placeholder="e.g. IELTS Academic">
                        </div>
                        <div class="field">
                            <label for="score">Result</label>
                            <input id="score" name="score" type="text" maxlength="60" value="{{ old('score', $item->score) }}" placeholder="e.g. Band 8.0 or CLB 10">
                        </div>
                    </div>
                    <div class="field">
                        <label for="quote">What the student says</label>
                        <textarea id="quote" name="quote" rows="4" maxlength="600" placeholder="One or two sentences, in the student’s own words.">{{ old('quote', $item->quote) }}</textarea>
                        @error('quote') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </section>
            </div>

            <aside class="editor-side">
                <section class="panel">
                    <div class="panel-head"><h2>Photo</h2></div>
                    <label class="dropzone dropzone--round {{ $item->photo ? 'has-image' : '' }}" data-dropzone>
                        <img src="{{ $item->photo ? asset($item->photo) : '' }}" alt="" data-image-preview>
                        <span class="dz-empty">
                            @include('admin.partials.icon', ['name' => 'image'])
                            <strong>Click to choose a photo</strong>
                            <small>Square photo works best<br>JPG, PNG or WebP · max 4 MB</small>
                        </span>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-image-input>
                    </label>
                    @error('photo') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($item->photo)
                        <label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
                    @endif
                </section>
                <section class="panel">
                    <div class="field">
                        <label for="sort_order">Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $item->sort_order) }}">
                        <p class="hint">Smaller numbers come first.</p>
                    </div>
                    <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item->is_published))> Show on the home page</label>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">{{ $editing ? 'Save changes' : 'Add story' }}</button>
                </section>
            </aside>
        </div>
    </form>
@endsection
