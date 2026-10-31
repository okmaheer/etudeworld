@extends('admin.layout')

@php $editing = $lesson->exists; @endphp
@section('title', ($editing ? 'Edit lesson' : 'Add lesson') . ' – ' . $module->title)

@section('actions')
    <a href="{{ route('admin.languages.lessons', [$language, $module]) }}" class="btn btn-sm btn-ghost">← {{ $module->title }}</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.languages.lessons.update', [$language, $module, $lesson]) : route('admin.languages.lessons.store', [$language, $module]) }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="title">Lesson title <span class="req">*</span></label>
                        <input id="title" name="title" type="text" required maxlength="160" class="input-lg" value="{{ old('title', $lesson->title) }}" placeholder="e.g. Der, die, das: German articles">
                        @error('title') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="summary">Short summary</label>
                        <textarea id="summary" name="summary" rows="2" maxlength="400" placeholder="One sentence shown in the lesson list.">{{ old('summary', $lesson->summary) }}</textarea>
                    </div>
                    <div class="field">
                        <label for="body">Lesson content</label>
                        <textarea id="body" name="body" rows="18">{{ old('body', $lesson->body) }}</textarea>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>Search engines (SEO)</h2></div>
            <div class="field">
                <label for="meta_title">SEO title <small class="muted">(optional)</small></label>
                <input id="meta_title" name="meta_title" type="text" maxlength="160" value="{{ old('meta_title', $lesson->meta_title) }}" placeholder="Shown in Google. Leave empty to use the title.">
                <p class="hint">About 50–60 characters works best.</p>
            </div>
            <div class="field">
                <label for="meta_description">SEO description <small class="muted">(optional)</small></label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="Shown under the title in Google. Leave empty to use the summary.">{{ old('meta_description', $lesson->meta_description) }}</textarea>
                <p class="hint">About 140–160 characters works best.</p>
            </div>
                </section>
            </div>

            <aside class="editor-side">
                <section class="panel">
                    <div class="field">
                        <label for="level">Level</label>
                        <select id="level" name="level">
                            <option value="">No level</option>
                            @foreach (\App\Models\LanguageLesson::LEVELS as $v => $label)
                                <option value="{{ $v }}" @selected(old('level', $lesson->level) === $v)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="sort_order">Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $lesson->sort_order) }}">
                        <p class="hint">Smaller numbers come first.</p>
                    </div>
                    <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $lesson->is_published))> Show on the website</label>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">{{ $editing ? 'Save changes' : 'Add lesson' }}</button>
                </section>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
    {{-- One shared text editor (TinyMCE, free open-source version): see public/js/rich-editor.js --}}
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/rich-editor.js') }}?v=1"></script>
    <script>EtudeRichEditor.init({ selector: '#body', height: 520, seo: false });</script>
@endpush
