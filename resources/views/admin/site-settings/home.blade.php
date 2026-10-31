@extends('admin.layout')

@section('title', 'Homepage Content')

@push('head')
    <style>
        .hc-card { border: 1px solid var(--line, #e3e7ee); border-radius: 12px; padding: 14px; margin-bottom: 12px; background: #fafbfe; }
        .hc-card__head { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
        .hc-card__head input[type=text] { flex: 1; min-width: 180px; font-weight: 700; }
        .hc-x { border: 0; background: #fdecee; color: #d7263d; border-radius: 8px; padding: 8px 12px; cursor: pointer; font-weight: 700; }
        .hc-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .hc-two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 800px) { .hc-grid, .hc-two { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    <p class="muted" style="margin:0 0 14px">Change the words on the home page. The headings of the other sections (Languages, Exams, Study methods, Achievements) are in <a href="{{ route('admin.seo.index', ['tab' => 'site-home']) }}">SEO → Home page</a>.</p>

    <form method="POST" action="{{ route('admin.site-settings.home.save') }}">
        @csrf

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Top of the page (hero)</h2></div>
            <div class="field">
                <label for="hero-eyebrow">Small line above the heading</label>
                <input id="hero-eyebrow" type="text" name="hero[eyebrow]" maxlength="120" value="{{ old('hero.eyebrow', $h['hero']['eyebrow']) }}">
            </div>
            <div class="field">
                <label for="hero-heading">Big heading</label>
                <input id="hero-heading" type="text" name="hero[heading]" maxlength="220" class="input-lg" value="{{ old('hero.heading', $h['hero']['heading']) }}">
                <p class="hint">To show words in blue, put them inside double square brackets, like [[right resources]].</p>
            </div>
            <div class="field">
                <label for="hero-text">Text under the heading</label>
                <textarea id="hero-text" name="hero[text]" rows="3" maxlength="500">{{ old('hero.text', $h['hero']['text']) }}</textarea>
            </div>
            <div class="hc-two">
                <div class="field"><label for="hero-b1">First button</label><input id="hero-b1" type="text" name="hero[button1]" maxlength="40" value="{{ old('hero.button1', $h['hero']['button1']) }}"></div>
                <div class="field"><label for="hero-b2">Second button</label><input id="hero-b2" type="text" name="hero[button2]" maxlength="40" value="{{ old('hero.button2', $h['hero']['button2']) }}"></div>
            </div>
            <p class="hint" style="margin:6px 0 8px">The three small facts under the buttons (leave both boxes of a fact empty to hide it):</p>
            <div class="hc-grid">
                @for ($i = 0; $i < 3; $i++)
                    @php $st = $h['hero']['stats'][$i] ?? ['big' => '', 'small' => '']; @endphp
                    <div class="hc-card" style="margin:0">
                        <div class="field"><label>Big word</label><input type="text" name="hero[stats][{{ $i }}][big]" maxlength="30" value="{{ old('hero.stats.' . $i . '.big', $st['big']) }}"></div>
                        <div class="field" style="margin:0"><label>Small text</label><input type="text" name="hero[stats][{{ $i }}][small]" maxlength="40" value="{{ old('hero.stats.' . $i . '.small', $st['small']) }}"></div>
                    </div>
                @endfor
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Exam cards</h2></div>
            <p class="hint" style="margin-top:0">The three cards under “Practice a Language Exam”. Each card opens its own exam page.</p>
            @foreach ($h['exams'] as $i => $e)
                <div class="hc-card">
                    <div class="hc-card__head">
                        <input type="text" name="exams[{{ $i }}][title]" maxlength="40" value="{{ old('exams.' . $i . '.title', $e['title']) }}" aria-label="Card title">
                        <input type="text" name="exams[{{ $i }}][button]" maxlength="40" value="{{ old('exams.' . $i . '.button', $e['button']) }}" aria-label="Button text" style="max-width:220px;font-weight:400" placeholder="Button text">
                    </div>
                    <textarea name="exams[{{ $i }}][text]" rows="2" maxlength="300" aria-label="Card text">{{ old('exams.' . $i . '.text', $e['text']) }}</textarea>
                </div>
            @endforeach

            <h3 style="margin:18px 0 8px;font-size:1rem">English Level Test banner</h3>
            <div class="hc-card">
                <div class="hc-card__head">
                    <input type="text" name="level[title]" maxlength="60" value="{{ old('level.title', $h['level']['title']) }}" aria-label="Banner title">
                    <input type="text" name="level[button]" maxlength="40" value="{{ old('level.button', $h['level']['button']) }}" aria-label="Button text" style="max-width:220px;font-weight:400">
                </div>
                <textarea name="level[text]" rows="2" maxlength="300" aria-label="Banner text">{{ old('level.text', $h['level']['text']) }}</textarea>
            </div>
        </section>

        <section class="panel" style="margin-bottom:16px">
            <div class="panel-head"><h2>Study method cards</h2></div>
            <p class="hint" style="margin-top:0">The cards under “Effective Study Methods”. Add, remove or change them. They show in this order. A card with no title is dropped when you save.</p>
            <div data-methods>
                @foreach (old('methods', $h['methods']) as $i => $m)
                    <div class="hc-card" data-method data-index="{{ $i }}">
                        <div class="hc-card__head">
                            <select name="methods[{{ $i }}][icon]" aria-label="Icon">
                                @foreach ($icons as $key => $label) <option value="{{ $key }}" @selected(($m['icon'] ?? '') === $key)>{{ $label }}</option> @endforeach
                            </select>
                            <input type="text" name="methods[{{ $i }}][title]" maxlength="60" value="{{ $m['title'] ?? '' }}" placeholder="Card title">
                            <button type="button" class="hc-x" data-up title="Move up">↑</button>
                            <button type="button" class="hc-x" data-down title="Move down" style="background:#eef2fb;color:#3a4766">↓</button>
                            <button type="button" class="hc-x" data-del>Remove</button>
                        </div>
                        <textarea name="methods[{{ $i }}][text]" rows="2" maxlength="300" placeholder="Short description">{{ $m['text'] ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-ghost" data-add-method>+ Add a card</button>
        </section>

        <template data-tpl-method>
            <div class="hc-card" data-method data-index="__M__">
                <div class="hc-card__head">
                    <select name="methods[__M__][icon]" aria-label="Icon">
                        @foreach ($icons as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
                    </select>
                    <input type="text" name="methods[__M__][title]" maxlength="60" placeholder="Card title">
                    <button type="button" class="hc-x" data-up title="Move up">↑</button>
                    <button type="button" class="hc-x" data-down title="Move down" style="background:#eef2fb;color:#3a4766">↓</button>
                    <button type="button" class="hc-x" data-del>Remove</button>
                </div>
                <textarea name="methods[__M__][text]" rows="2" maxlength="300" placeholder="Short description"></textarea>
            </div>
        </template>

        <button type="submit" class="btn btn-primary">Save home page</button>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {
            const wrap = document.querySelector('[data-methods]'), tpl = document.querySelector('[data-tpl-method]').innerHTML;
            document.addEventListener('click', e => {
                const t = e.target, card = t.closest('[data-method]');
                if (t.closest('[data-add-method]')) {
                    const n = Math.max(-1, ...[...wrap.children].map(c => +c.dataset.index)) + 1;
                    const d = document.createElement('div'); d.innerHTML = tpl.split('__M__').join(n).trim(); wrap.appendChild(d.firstElementChild); wrap.lastElementChild.querySelector('input').focus();
                } else if (card && t.closest('[data-del]')) { card.remove(); }
                else if (card && t.closest('[data-up]') && card.previousElementSibling) { wrap.insertBefore(card, card.previousElementSibling); }
                else if (card && t.closest('[data-down]') && card.nextElementSibling) { wrap.insertBefore(card.nextElementSibling, card); }
            });
        })();
    </script>
@endpush
