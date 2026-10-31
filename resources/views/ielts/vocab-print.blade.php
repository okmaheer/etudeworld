<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $test->name }} – {{ ucfirst($module) }} vocabulary</title>
    <style>
        body { font: 14px/1.45 Arial, "Noto Sans", sans-serif; color: #17202e; margin: 0; padding: 24px; max-width: 900px; margin-inline: auto; }
        .bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; padding: 10px 14px; background: #eef3fb; border-radius: 8px; margin-bottom: 18px; }
        .bar button, .bar select { font: inherit; padding: 7px 12px; }
        .bar button { background: #1f4fbf; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
        h1 { font-size: 22px; margin: 0 0 2px; } .sub { color: #5b6577; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #c9d1de; padding: 7px 9px; vertical-align: top; text-align: left; }
        th { background: #f1f4f9; } tr { page-break-inside: avoid; }
        .w { font-weight: 700; } .t { color: #5b6577; font-size: 12px; } .ex { color: #3b4658; font-style: italic; }
        .tr { font-size: 16px; } [dir=rtl] { text-align: right; }
        .foot { margin-top: 18px; color: #5b6577; font-size: 12px; }
        @media print { .bar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="bar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <label>My language:
            <select onchange="location.search = this.value ? '?lang=' + this.value : ''">
                <option value="">English only</option>
                @foreach ($languages as $code => $name)<option value="{{ $code }}" @selected($lang === $code)>{{ $name }}</option>@endforeach
            </select></label>
        <span style="color:#5b6577">In the print window, choose “Save as PDF”.</span>
    </div>
    <h1>{{ $test->name }} – {{ ucfirst($module) }} vocabulary</h1>
    <p class="sub">{{ $words->count() }} words and phrases · Etude World</p>
    <table>
        <thead><tr><th style="width:4%">#</th><th style="width:24%">Word / phrase</th><th>Meaning</th>@if ($lang)<th style="width:22%">{{ $languages[$lang] }}</th>@endif</tr></thead>
        <tbody>
        @foreach ($words as $i => $w)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><span class="w">{{ $w->word }}</span><br><span class="t">{{ $w->type }}</span></td>
                <td>{{ $w->meaning }}
                    @if (! empty($w->synonyms))<br><span class="t">Synonyms: {{ implode(', ', $w->synonyms) }}</span>@endif
                    @if ($w->example)<br><span class="ex">{{ $w->example }}</span>@endif</td>
                @if ($lang)<td class="tr" @if (in_array($lang, ['ar', 'ur'])) dir="rtl" @endif>{{ $w->translations[$lang] ?? '' }}</td>@endif
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="foot">Learn these words online: {{ route('vocab.test', ['module' => $module, 'test' => $test->id]) }}</p>
    <script>if (!location.search.includes('np')) window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>
</html>
