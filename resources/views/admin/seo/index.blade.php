@extends('admin.layout')

@section('title', 'SEO')

@section('content')
    <p class="muted" style="margin:0 0 14px">Change what Google shows for the exam pages, and the heading and text on them. Leave a field empty to use the grey default text. Each test has its own search settings on the test's edit page.</p>

    <div class="field" style="max-width:420px;margin-bottom:16px">
        <label for="seo-pick">Choose a page group</label>
        <select id="seo-pick" onchange="if (this.value) location.href = this.value">
            <optgroup label="Exam pages">
                @foreach ($groups as $key => $g)
                    <option value="{{ route('admin.seo.index', ['tab' => $key]) }}" @selected($tab === $key)>{{ $g['label'] }}</option>
                @endforeach
                <option value="{{ route('admin.seo.index', ['tab' => 'home']) }}" @selected($tab === 'home')>Exam home pages (IELTS, CELPIP, TOEFL)</option>
            </optgroup>
            <optgroup label="Rest of the website">
                @foreach ($siteSections as $key => $sec)
                    <option value="{{ route('admin.seo.index', ['tab' => 'site-' . $key]) }}" @selected($tab === 'site-' . $key)>{{ $sec['label'] }} ({{ $sec['count'] }})</option>
                @endforeach
            </optgroup>
        </select>
    </div>

    <form method="POST" action="{{ route('admin.seo.save') }}" data-seo-form>
        @csrf
        <input type="hidden" name="tab" value="{{ $tab }}">

        @foreach ($rows as $i => $r)
            @php
                $s = $saved[$r['group'] . '|' . $r['page']] ?? null;
                $key = $r['group'] . '_' . $r['page'];
                $name = fn ($f) => 'pages[' . $r['group'] . '][' . $r['page'] . '][' . $f . ']';
                $d = $r['defaults'];
                $filled = $s && ($s->meta_title || $s->meta_description || $s->h1 || $s->intro || $s->content);
            @endphp
            <details class="panel seo-row" style="margin-bottom:12px" @if ($i === 0) open @endif data-seo-row>
                <summary style="cursor:pointer;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                    <strong>{{ $r['label'] }}</strong>
                    <code class="muted">{{ $r['url'] }}</code>
                    <a href="{{ $r['url'] }}" target="_blank" rel="noopener" class="muted" style="font-size:.85em" onclick="event.stopPropagation()">View page ↗</a>
                    @if ($filled)<span class="badge badge-green" style="margin-left:auto">Edited</span>@endif
                </summary>

                @if (! empty($r['part']))
                    <p class="hint" style="margin-top:14px" data-part="{{ $r['part'] }}">Change the text of this section on the home page. Leave a field empty to keep the grey text.</p>
                    <div class="field">
                        <label for="mt_{{ $key }}">Small label above the heading</label>
                        <input id="mt_{{ $key }}" data-fetch="eyebrow" name="{{ $name('meta_title') }}" type="text" maxlength="255" value="{{ $s?->meta_title }}" placeholder="">
                    </div>
                    <div class="field">
                        <label for="h1_{{ $key }}">Heading</label>
                        <input id="h1_{{ $key }}" data-fetch="h2" name="{{ $name('h1') }}" type="text" maxlength="255" value="{{ $s?->h1 }}" placeholder="">
                    </div>
                    <div class="field">
                        <label for="in_{{ $key }}">Text under the heading</label>
                        <textarea id="in_{{ $key }}" data-fetch="sectext" name="{{ $name('intro') }}" rows="3" maxlength="2000" placeholder="">{{ $s?->intro }}</textarea>
                    </div>
                @else
                <div class="field" style="margin-top:14px">
                    <label for="mt_{{ $key }}">Meta title</label>
                    <input id="mt_{{ $key }}" @if (! empty($r['fetch'])) data-fetch="title" @endif name="{{ $name('meta_title') }}" type="text" maxlength="255" value="{{ $s?->meta_title }}" placeholder="{{ $d['title'] }}" data-counter="60">
                    <div class="meter"><span data-meter-for="mt_{{ $key }}"></span></div>
                    <p class="hint" data-count-for="mt_{{ $key }}">Best length: 30–60 characters.</p>
                </div>
                <div class="field">
                    <label for="md_{{ $key }}">Meta description</label>
                    <textarea id="md_{{ $key }}" @if (! empty($r['fetch'])) data-fetch="description" @endif name="{{ $name('meta_description') }}" rows="3" maxlength="500" placeholder="{{ $d['description'] }}" data-counter="160">{{ $s?->meta_description }}</textarea>
                    <div class="meter"><span data-meter-for="md_{{ $key }}"></span></div>
                    <p class="hint" data-count-for="md_{{ $key }}">Best length: 120–160 characters.</p>
                </div>

                @if ($r['full'])
                    <div class="field">
                        <label for="h1_{{ $key }}">Main heading (H1)</label>
                        <input id="h1_{{ $key }}" @if (! empty($r['fetch'])) data-fetch="h1" @endif name="{{ $name('h1') }}" type="text" maxlength="255" value="{{ $s?->h1 }}" placeholder="{{ $d['h1'] }}">
                    </div>
                    <div class="field">
                        <label for="in_{{ $key }}">Text under the heading</label>
                        <textarea id="in_{{ $key }}" @if (! empty($r['fetch'])) data-fetch="intro" @endif name="{{ $name('intro') }}" rows="3" maxlength="2000" placeholder="{{ $d['intro'] }}">{{ $s?->intro }}</textarea>
                    </div>
                    <div class="field">
                        <label for="ct_{{ $key }}">More text below the tests</label>
                        <textarea id="ct_{{ $key }}" class="seo-rich" name="{{ $name('content') }}" rows="8">{{ $s?->content }}</textarea>
                        <p class="hint">Add H2 headings, paragraphs, lists and links here. It is shown under the test cards. Leave it empty to show nothing.</p>
                    </div>
                @endif
                @endif
            </details>
        @endforeach

        <div class="form-actions" style="justify-content:flex-start">
            <button type="submit" class="btn btn-primary">Save SEO settings</button>
        </div>
    </form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('js/editor-paste-clean.js') }}?v=1"></script>
    <script src="{{ asset('js/editor-blocks.js') }}?v=5"></script>
    <script src="{{ asset('js/rich-editor.js') }}?v=1"></script>
    <script>
        // The text editors start when a section is opened (they cannot start inside a closed box).
        function startEditors(row) {
            row.querySelectorAll('textarea.seo-rich').forEach(function (ta) {
                if (ta.dataset.ready) return;
                ta.dataset.ready = '1';
                EtudeRichEditor.init({ target: ta, height: 420, seo: false });
            });
        }
        // For the other pages of the site, show the text the page uses now as the grey hint (read from the live page).
        function showCurrent(row) {
            var a = row.querySelector('a[target]');
            var part = row.querySelector('[data-part]');
            if (part && !row.dataset.fetched) {
                row.dataset.fetched = '1';
                fetch('/', {credentials: 'same-origin'}).then(function (r) { return r.text(); }).then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var sec = doc.getElementById(part.dataset.part); if (!sec) return;
                    var set = function (k, sel) { var f = row.querySelector('[data-fetch=' + k + ']'), n = sec.querySelector(sel); if (f && n) f.placeholder = n.textContent.trim(); };
                    set('eyebrow', '.eyebrow'); set('h2', 'h2'); set('sectext', '.section-head p');
                }).catch(function () {});
                return;
            }
            if (!a || row.dataset.fetched || !row.querySelector('[data-fetch]')) return;
            row.dataset.fetched = '1';
            fetch(a.getAttribute('href'), {credentials: 'same-origin'}).then(function (r) { return r.text(); }).then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var t = doc.querySelector('title'), d = doc.querySelector('meta[name="description"]');
                var tf = row.querySelector('[data-fetch=title]'), df = row.querySelector('[data-fetch=description]');
                if (t && tf) tf.placeholder = t.textContent.trim();
                if (d && df) df.placeholder = d.getAttribute('content') || '';
                var h = doc.querySelector('main h1'), hf = row.querySelector('[data-fetch=h1]'), inf = row.querySelector('[data-fetch=intro]');
                if (h && hf) hf.placeholder = h.textContent.trim();
                var pp = h && h.nextElementSibling;
                if (pp && pp.tagName === 'P' && inf) inf.placeholder = pp.textContent.trim();
            }).catch(function () {});
        }
        document.querySelectorAll('[data-seo-row]').forEach(function (row) {
            if (row.open) { startEditors(row); showCurrent(row); }
            row.addEventListener('toggle', function () { if (row.open) { startEditors(row); showCurrent(row); } });
        });
    </script>
@endpush
