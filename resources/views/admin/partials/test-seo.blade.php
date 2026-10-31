{{--
    "Search results (SEO)" box on a test's edit page: the full mock test and each section.
    Needs: $exam (ielts|celpip|toefl), $test, $pages (which sections this exam has).
--}}
@php
    $all = array_merge(['mock'], $pages);
    $vals = \App\Models\TestSeo::forAdmin($exam, $test, $all);
    $labels = ['mock' => 'Full mock test', 'reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing', 'speaking' => 'Speaking'];
    $pre = ['ielts' => '/ielts/academic or /ielts/general-training', 'celpip' => '/celpip/general or /celpip/ls', 'toefl' => '/toefl'][$exam];
@endphp
<form method="POST" action="{{ route('admin.test-seo.save', [$exam, $test->id]) }}" class="panel form-narrow" data-seo-form style="margin-top:20px">
    @csrf
    @method('PUT')
    <h2 style="margin:0 0 4px;font-size:1.1rem">Search results (SEO)</h2>
    <p class="muted" style="margin:0 0 14px">What Google and link previews show for this test. Leave a field empty to use the test name. Tick “Let Google list this page” only for pages you want found in search – other test pages stay hidden from Google.</p>

    @foreach ($all as $p)
        @php $v = $vals[$p]; @endphp
        <details class="seo-sub" style="border:1px solid var(--line,#e5e7ef);border-radius:10px;padding:10px 14px;margin-bottom:10px" @if ($p === 'mock' || $v['meta_title'] || $v['meta_description']) open @endif>
            <summary style="cursor:pointer;font-weight:700">{{ $labels[$p] }}
                @if ($v['meta_title'] || $v['meta_description'])<span class="badge badge-green" style="margin-left:8px">Edited</span>@endif
                @if ($v['indexable'])<span class="badge" style="margin-left:6px">Google can list it</span>@endif
            </summary>
            <div class="field" style="margin-top:12px">
                <label for="st_{{ $p }}">Meta title</label>
                <input id="st_{{ $p }}" name="seo[{{ $p }}][meta_title]" type="text" maxlength="255" value="{{ $v['meta_title'] }}" placeholder="Leave empty to use the test name" data-counter="60">
                <div class="meter"><span data-meter-for="st_{{ $p }}"></span></div>
                <p class="hint" data-count-for="st_{{ $p }}">Best length: 30–60 characters.</p>
            </div>
            <div class="field">
                <label for="sd_{{ $p }}">Meta description</label>
                <textarea id="sd_{{ $p }}" name="seo[{{ $p }}][meta_description]" rows="3" maxlength="500" placeholder="A short summary shown under the title in Google results" data-counter="160">{{ $v['meta_description'] }}</textarea>
                <div class="meter"><span data-meter-for="sd_{{ $p }}"></span></div>
                <p class="hint" data-count-for="sd_{{ $p }}">Best length: 120–160 characters.</p>
            </div>
            <label style="display:flex;gap:8px;align-items:center;font-size:.92rem">
                <input type="checkbox" name="seo[{{ $p }}][indexable]" value="1" @checked($v['indexable'])> Let Google list this page
            </label>
        </details>
    @endforeach

    <div class="form-actions" style="justify-content:flex-start">
        <button type="submit" class="btn btn-primary">Save search settings</button>
    </div>
</form>
