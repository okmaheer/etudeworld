@extends('admin.layout')

@php $editing = $author->exists; @endphp

@section('title', $editing ? 'Edit Author' : 'Add New Author')

@section('actions')
    <a href="{{ route('admin.authors.index') }}" class="btn btn-sm btn-ghost">All authors</a>
@endsection

@section('content')
    <form method="POST" enctype="multipart/form-data" class="author-form"
          action="{{ $editing ? route('admin.authors.update', $author) : route('admin.authors.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="editor-layout">
            <div class="editor-main">
                <section class="panel">
                    <div class="field">
                        <label for="name">Author name <span class="req">*</span></label>
                        <input id="name" name="name" type="text" required maxlength="255" class="input-lg"
                               value="{{ old('name', $author->name) }}" placeholder="e.g. Sarah Ahmed">
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                        @if ($editing)
                            <p class="hint">Author page: <a href="{{ route('authors.show', $author) }}" target="_blank">{{ url('/author/' . $author->slug) }}</a></p>
                        @endif
                    </div>

                    <div class="field">
                        <label for="bio">Author bio</label>
                        <p class="hint">A few sentences about the author: experience, qualifications, what they teach. Shown on every blog they write and on their author page.</p>
                        <textarea id="bio" name="bio" rows="8">{{ old('bio', $author->bio) }}</textarea>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><h2>Social media links</h2></div>
                    @foreach (\App\Models\Author::SOCIALS as $key => $label)
                        <div class="field social-field">
                            <label for="{{ $key }}">
                                <span class="social-badge social-badge--{{ $key }}">@include('partials.social-icon', ['name' => $key])</span>
                                {{ $label }}
                            </label>
                            <input id="{{ $key }}" name="{{ $key }}" type="text" maxlength="255" value="{{ old($key, $author->{$key}) }}"
                                   placeholder="{{ ['facebook' => 'https://www.facebook.com/your-page', 'x' => 'https://x.com/your-handle', 'linkedin' => 'https://www.linkedin.com/in/your-name'][$key] }}">
                            @error($key) <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <p class="hint">Leave any of these empty to hide that icon.</p>
                </section>
            </div>

            <aside class="editor-side">
                <section class="panel">
                    <div class="panel-head"><h2>Author photo</h2></div>
                    <label class="dropzone dropzone--round {{ $author->image ? 'has-image' : '' }}" data-dropzone>
                        <img src="{{ $author->image ? asset($author->image) : '' }}" alt="" data-image-preview>
                        <span class="dz-empty">
                            @include('admin.partials.icon', ['name' => 'image'])
                            <strong>Click to choose a photo</strong>
                            <small>Square photo works best<br>JPG, PNG or WebP · max 4 MB</small>
                        </span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-image-input>
                    </label>
                    @error('image') <p class="field-error">{{ $message }}</p> @enderror
                    @if ($author->image)
                        <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
                    @endif
                </section>

                <section class="panel">
                    <button type="submit" class="btn btn-primary btn-block">{{ $editing ? 'Save changes' : 'Add author' }}</button>
                    @if ($editing)
                        <button type="submit" form="delete-author" class="btn btn-danger btn-block" style="margin-top:10px"
                                data-confirm="Delete {{ $author->name }}? Their blogs will be kept but will show no author.">Delete author</button>
                    @endif
                </section>
            </aside>
        </div>
    </form>

    @if ($editing)
        <form id="delete-author" method="POST" action="{{ route('admin.authors.destroy', $author) }}" hidden>
            @csrf @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
    {{-- One shared text editor (TinyMCE, free open-source version): see public/js/rich-editor.js --}}
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/rich-editor.js') }}?v=1"></script>
    <script>EtudeRichEditor.init({ selector: '#bio', profile: 'simple', height: 280, seo: false });</script>
@endpush
