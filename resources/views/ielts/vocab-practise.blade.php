<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Practise vocabulary – {{ $test->name }}</title>
    <style>
        body { font: 16px/1.5 Arial, "Noto Sans", sans-serif; background: #eef1f6; color: #17202e; margin: 0; padding: 20px; }
        .wrap { max-width: 640px; margin: 0 auto; }
        .top { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
        select, button { font: inherit; padding: 8px 14px; border-radius: 8px; border: 1px solid #b7bcc6; background: #fff; cursor: pointer; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 4px 18px rgba(19,35,74,.08); padding: 34px 26px; min-height: 240px; text-align: center; cursor: pointer; display: flex; flex-direction: column; justify-content: center; gap: 8px; }
        .card .w { font-size: 28px; font-weight: 700; } .card .t { color: #5b6577; } .ex { font-style: italic; color: #3b4658; } .tr { font-size: 22px; color: #1f4fbf; }
        .hint { color: #7b8496; font-size: 13px; }
        .nav { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }
        .primary { background: #1f4fbf; color: #fff; border-color: #1f4fbf; }
        [hidden] { display: none !important; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <strong>{{ $test->name }} – {{ ucfirst($module) }} words</strong>
        <label>My language:
            <select onchange="location.search = this.value ? '?lang=' + this.value : ''">
                <option value="">English only</option>
                @foreach ($languages as $code => $name)<option value="{{ $code }}" @selected($lang === $code)>{{ $name }}</option>@endforeach
            </select></label>
    </div>
    <div class="card" id="card" role="button" tabindex="0"></div>
    <p class="hint" style="text-align:center">Click the card to turn it over.</p>
    <div class="nav">
        <button type="button" id="prev">← Back</button>
        <span id="count"></span>
        <button type="button" id="next" class="primary">Next →</button>
    </div>
</div>
<script>
    const words = @json($cards);
    const lang = @json($lang);
    let i = 0, back = false;
    const card = document.getElementById('card');
    const e = s => String(s ?? '').replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
    function draw() {
        const w = words[i];
        card.innerHTML = back
            ? `<div>${e(w.meaning)}</div>${w.synonyms ? `<div class="t">Synonyms: ${e(w.synonyms)}</div>` : ''}${w.example ? `<div class="ex">${e(w.example)}</div>` : ''}${w.tr ? `<div class="tr" ${['ar','ur'].includes(lang) ? 'dir="rtl"' : ''}>${e(w.tr)}</div>` : ''}`
            : `<div class="w">${e(w.word)}</div><div class="t">${e(w.type)}</div>`;
        document.getElementById('count').textContent = (i + 1) + ' / ' + words.length;
    }
    const go = d => { i = (i + d + words.length) % words.length; back = false; draw(); };
    card.addEventListener('click', () => { back = !back; draw(); });
    card.addEventListener('keydown', ev => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); back = !back; draw(); } });
    document.getElementById('next').addEventListener('click', () => go(1));
    document.getElementById('prev').addEventListener('click', () => go(-1));
    document.addEventListener('keydown', ev => { if (ev.key === 'ArrowRight') go(1); if (ev.key === 'ArrowLeft') go(-1); });
    draw();
</script>
</body>
</html>
