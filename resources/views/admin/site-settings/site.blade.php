@extends('admin.layout')

@section('title', 'Site Settings')

@push('head')
    <style>
        .ss-col { border: 1px solid var(--line, #e3e7ee); border-radius: 12px; padding: 14px; margin-bottom: 14px; background: #fafbfe; }
        .ss-col__head { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
        .ss-col__head input { flex: 1; font-weight: 700; }
        .ss-link { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; margin-bottom: 8px; }
        .ss-x { border: 0; background: #fdecee; color: #d7263d; border-radius: 8px; padding: 0 12px; cursor: pointer; font-weight: 700; }
        .ss-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 800px) { .ss-grid, .ss-link { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <p class="muted" style="margin:0 0 14px">Everything here shows in the footer and menu on every page of the website.</p>

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.site-settings.site.save') }}">
        @csrf

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Logo</h2></div>
            <p class="hint" style="margin-top:0">Shown in the top menu, the footer, the test screens and the browser tab. Use a square picture with a see-through (transparent) background – PNG or SVG works best.</p>
            <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
                <label class="dropzone dropzone--round has-image" data-dropzone style="width:120px;height:120px;flex:none">
                    <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" data-image-preview style="object-fit:contain">
                    <span class="dz-empty"><strong>Choose</strong></span>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" data-image-input>
                </label>
                <div>
                    <p style="margin:0 0 8px"><strong>Click the picture to choose a new logo</strong><br><small class="muted">PNG, JPG, WebP or SVG · max 2 MB</small></p>
                    @if (! empty($s['logo']))
                        <label class="check"><input type="checkbox" name="remove_logo" value="1"> Go back to the original logo</label>
                    @endif
                </div>
            </div>
            @error('logo') <p class="field-error">{{ $message }}</p> @enderror
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Footer text and contact</h2></div>
            <div class="field">
                <label for="footer_about">Text under the logo in the footer</label>
                <textarea id="footer_about" name="footer_about" rows="3" maxlength="400">{{ old('footer_about', $s['footer_about']) }}</textarea>
            </div>
            <div class="field" style="max-width:420px">
                <label for="contact_email">Contact email</label>
                <input id="contact_email" name="contact_email" type="email" maxlength="190" value="{{ old('contact_email', $s['contact_email']) }}" placeholder="hello@yourwebsite.com">
                <p class="hint">Shown in the footer as an “email us” link. Leave empty to hide it.</p>
                @error('contact_email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Social links</h2></div>
            <p class="hint" style="margin-top:0">Paste the full address of each page, for example https://www.facebook.com/yourpage. The icon is shown in the footer either way; it opens the page once you add the link.</p>
            <div class="ss-grid">
                @foreach (['linkedin' => 'LinkedIn', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $k => $label)
                    <div class="field">
                        <label for="social-{{ $k }}">{{ $label }}</label>
                        <input id="social-{{ $k }}" name="social[{{ $k }}]" type="text" maxlength="300" value="{{ old('social.' . $k, $s['social'][$k] ?? '') }}" placeholder="https://">
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Footer links</h2></div>
            <p class="hint" style="margin-top:0">Each box is one column in the footer. For the address, type a page address on this website (like <code>/about-us</code>) or a full web address (like <code>https://…</code>). A link with no name is dropped when you save.</p>
            <div data-columns>
                @foreach (old('columns', $s['columns']) as $ci => $col)
                    <div class="ss-col" data-col data-index="{{ $ci }}">
                        <div class="ss-col__head">
                            <input type="text" name="columns[{{ $ci }}][title]" maxlength="60" value="{{ $col['title'] ?? '' }}" placeholder="Column heading">
                            <button type="button" class="ss-x" data-del-col title="Remove this column">Remove column</button>
                        </div>
                        <div data-links>
                            @foreach (($col['links'] ?? []) as $li => $l)
                                <div class="ss-link" data-link data-index="{{ $li }}">
                                    <input type="text" name="columns[{{ $ci }}][links][{{ $li }}][label]" maxlength="80" value="{{ $l['label'] ?? '' }}" placeholder="Link name">
                                    <input type="text" name="columns[{{ $ci }}][links][{{ $li }}][url]" maxlength="300" value="{{ $l['url'] ?? '' }}" placeholder="/page-address or https://…">
                                    <button type="button" class="ss-x" data-del-link title="Remove this link">✕</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-ghost" data-add-link>+ Add a link</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-ghost" data-add-col>+ Add a column</button>
        </section>

        <template data-tpl-col>
            <div class="ss-col" data-col data-index="__C__">
                <div class="ss-col__head">
                    <input type="text" name="columns[__C__][title]" maxlength="60" placeholder="Column heading">
                    <button type="button" class="ss-x" data-del-col>Remove column</button>
                </div>
                <div data-links></div>
                <button type="button" class="btn btn-sm btn-ghost" data-add-link>+ Add a link</button>
            </div>
        </template>
        <template data-tpl-link>
            <div class="ss-link" data-link data-index="__L__">
                <input type="text" name="columns[__C__][links][__L__][label]" maxlength="80" placeholder="Link name">
                <input type="text" name="columns[__C__][links][__L__][url]" maxlength="300" placeholder="/page-address or https://…">
                <button type="button" class="ss-x" data-del-link title="Remove this link">✕</button>
            </div>
        </template>

        <button type="submit" class="btn btn-primary">Save site settings</button>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-site-settings.js') }}?v=1"></script>
@endpush
