@extends('admin.layout')

@php $editing = $module->exists; @endphp
@section('title', ($editing ? 'Edit module' : 'Add module') . ' – ' . $info['name'])

@section('actions')
    <a href="{{ route('admin.languages.index', $language) }}" class="btn btn-sm btn-ghost">All modules</a>
@endsection

@section('content')
    <form method="POST" action="{{ $editing ? route('admin.languages.modules.update', [$language, $module]) : route('admin.languages.modules.store', $language) }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="panel" style="max-width:720px">
            <div class="field">
                <label for="title">Module name <span class="req">*</span></label>
                <input id="title" name="title" type="text" required maxlength="120" class="input-lg" value="{{ old('title', $module->title) }}" placeholder="e.g. Grammar">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="icon">Icon (an emoji)</label>
                <input id="icon" name="icon" type="text" maxlength="8" value="{{ old('icon', $module->icon) }}" placeholder="📚" style="max-width:120px">
                <p class="hint">Shown on the module card. Examples: 📚 🧩 💬 🎧 📖 ✍️ 🗣️ 🔤</p>
            </div>

            <div class="field">
                <label for="description">Short description</label>
                <textarea id="description" name="description" rows="3" maxlength="400" placeholder="One or two sentences shown on the module card.">{{ old('description', $module->description) }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="sort_order">Order</label>
                <input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $module->sort_order) }}" style="max-width:120px">
                <p class="hint">Smaller numbers come first.</p>
            </div>

            <div class="field">
                <label for="meta_title">SEO title <small class="muted">(optional)</small></label>
                <input id="meta_title" name="meta_title" type="text" maxlength="160" value="{{ old('meta_title', $module->meta_title) }}" placeholder="Shown in Google. Leave empty to use the title.">
                <p class="hint">About 50–60 characters works best.</p>
            </div>
            <div class="field">
                <label for="meta_description">SEO description <small class="muted">(optional)</small></label>
                <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="Shown under the title in Google. Leave empty to use the summary.">{{ old('meta_description', $module->meta_description) }}</textarea>
                <p class="hint">About 140–160 characters works best.</p>
            </div>

            <label class="check"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $module->is_published))> Show this module on the website</label>

            <button type="submit" class="btn btn-primary" style="margin-top:14px">{{ $editing ? 'Save changes' : 'Add module' }}</button>
        </section>
    </form>
@endsection
