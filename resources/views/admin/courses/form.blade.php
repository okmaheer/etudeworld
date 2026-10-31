@extends('admin.layout')

@php $editing = $course->exists; @endphp
@section('title', $editing ? 'Edit course' : 'Add a course')

@section('actions')
    <a href="{{ route('admin.courses.index') }}" class="btn btn-sm btn-ghost">All courses</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.courses.update', $course) : route('admin.courses.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="title">Course name <span class="req">*</span></label>
                        <input id="title" name="title" type="text" required maxlength="160" class="input-lg" value="{{ old('title', $course->title) }}" placeholder="e.g. IELTS Listening Course">
                        @error('title') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="summary">Short description</label>
                        <input id="summary" name="summary" type="text" maxlength="400" value="{{ old('summary', $course->summary) }}" placeholder="One line under the name">
                    </div>
                    <div class="field">
                        <label for="chips">Quick facts <small class="muted">(one per line)</small></label>
                        <textarea id="chips" name="chips" rows="4" placeholder="7 Classes&#10;1 hour each class">{{ old('chips', $course->chips) }}</textarea>
                        <p class="hint">Shown as small blue labels, e.g. number of classes, length of a class, duration.</p>
                    </div>
                    <div class="field">
                        <label for="features">What is included <small class="muted">(one per line, each gets a tick)</small></label>
                        <textarea id="features" name="features" rows="8">{{ old('features', $course->features) }}</textarea>
                    </div>
                    <div class="field">
                        <label for="note">Small note <small class="muted">(optional)</small></label>
                        <input id="note" name="note" type="text" maxlength="200" value="{{ old('note', $course->note) }}" placeholder="e.g. Expert feedback not available">
                    </div>
                </section>
            </div>

            <aside class="editor-side">
                <section class="panel">
                    <div class="field">
                        <label for="exam">Exam block <span class="req">*</span></label>
                        <select id="exam" name="exam">
                            @foreach (\App\Models\Course::EXAMS as $k => $label) <option value="{{ $k }}" @selected(old('exam', $course->exam) === $k)>{{ $label }}</option> @endforeach
                        </select>
                    </div>
                    <div class="field-row">
                        <div class="field">
                            <label for="price">Price <span class="req">*</span></label>
                            <input id="price" name="price" type="number" step="0.01" min="0" required value="{{ old('price', $course->price) }}">
                            @error('price') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="currency">Currency</label>
                            <input id="currency" name="currency" type="text" maxlength="3" value="{{ old('currency', $course->currency) }}">
                            @error('currency') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="button_label">Button text</label>
                        <input id="button_label" name="button_label" type="text" maxlength="40" value="{{ old('button_label', $course->button_label) }}">
                    </div>
                    <div class="field">
                        <label for="button_url">Button address</label>
                        <input id="button_url" name="button_url" type="text" maxlength="300" value="{{ old('button_url', $course->button_url) }}" placeholder="Leave empty = payments page">
                    </div>
                </section>
                <section class="panel">
                    <div class="field">
                        <label for="badge">Ribbon <small class="muted">(optional)</small></label>
                        <input id="badge" name="badge" type="text" maxlength="40" value="{{ old('badge', $course->badge) }}" placeholder="e.g. Best value">
                    </div>
                    <div class="field">
                        <label for="slug">Page link name</label>
                        <input id="slug" name="slug" type="text" maxlength="160" value="{{ old('slug', $course->slug) }}" placeholder="made from the name">
                        @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="sort_order">Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $course->sort_order) }}">
                        <p class="hint">Smaller numbers come first.</p>
                    </div>
                    <label class="check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course->is_featured))> Highlight this card (blue border)</label>
                    <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $course->is_published))> Show on the website</label>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">{{ $editing ? 'Save changes' : 'Add course' }}</button>
                </section>
            </aside>
        </div>
    </form>
@endsection
