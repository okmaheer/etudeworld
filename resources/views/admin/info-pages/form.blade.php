@extends('admin.layout')

@php $editing = $page->exists; @endphp
@section('title', $editing ? 'Edit: ' . $page->title : 'Add page')

@section('actions')
    <a href="{{ route('admin.info-pages.index') }}" class="btn btn-sm btn-ghost">All pages</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.info-pages.update', $page) : route('admin.info-pages.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="title">Page title <span class="req">*</span></label>
                        <input id="title" name="title" type="text" required maxlength="255" class="input-lg" value="{{ old('title', $page->title) }}">
                        @error('title') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="body">Page content</label>
                        <textarea id="body" name="body" rows="20">{{ old('body', $page->body) }}</textarea>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>Search engines (SEO)</h2></div>
                    <div class="field">
                        <label for="meta_title">SEO title <small class="muted">(optional)</small></label>
                        <input id="meta_title" name="meta_title" type="text" maxlength="160" value="{{ old('meta_title', $page->meta_title) }}" placeholder="Leave empty to use the page title.">
                    </div>
                    <div class="field">
                        <label for="meta_description">SEO description <small class="muted">(optional)</small></label>
                        <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="Leave empty to use the first lines of the page.">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                </section>
            </div>

            <aside class="editor-side">
                <section class="panel">
                    @if ($editing && $page->is_system)
                        <p class="muted" style="margin:0 0 12px">Address: <strong>{{ $page->public_path }}</strong><br><small>This page’s address cannot be changed.</small></p>
                    @else
                        <div class="field">
                            <label for="slug">Address</label>
                            <input id="slug" name="slug" type="text" maxlength="120" value="{{ old('slug', $page->slug) }}" placeholder="e.g. refund-policy">
                            <p class="hint">Shown as {{ url('/page') }}/<em>address</em>. Leave empty to make it from the title.</p>
                        </div>
                    @endif
                    <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Show on the website</label>
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:14px">{{ $editing ? 'Save changes' : 'Add page' }}</button>
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
