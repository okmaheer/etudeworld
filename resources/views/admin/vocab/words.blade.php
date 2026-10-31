@extends('admin.layout')

{{-- Vocabulary Builder (CELPIP sections and topics): review the words the AI suggests. --}}
@section('title', $title)

@section('actions')
    <a href="{{ $back[0] }}" class="btn btn-sm btn-ghost">{{ $back[1] }}</a>
@endsection

@section('content')
    @unless ($hasKey)
        <div class="panel rda-help"><p><strong>One-time setup:</strong> to let the AI suggest words, add this line to the website's <code>.env</code> file: <code>OPENAI_API_KEY=your-key-here</code>, then restart the website.</p></div>
    @endunless

    @isset($topic)
        <section class="panel">
            <form method="POST" action="{{ route('admin.vocab-topics.update', $topic) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">@csrf @method('PUT')
                <strong>{{ strtoupper($topic->exam) }} {{ ucfirst($topic->skill) }}</strong>
                <input type="text" name="name" class="input" value="{{ $topic->name }}" style="flex:1;min-width:200px" required>
                <select name="status" class="input"><option value="active" @selected($topic->status === 'active')>Active</option><option value="draft" @selected($topic->status === 'draft')>Draft (hidden)</option></select>
                <button class="btn btn-ghost">Save topic</button>
            </form>
        </section>
    @endisset

    @if ($source)
        <section class="panel">
            <form method="POST" action="{{ $source['url'] }}">@csrf @method('PUT')
                <label><strong>Transcripts</strong> <span class="muted">(paste the text of all the recordings of this Listening test, then save)</span>
                    <textarea name="text" class="input" rows="8" style="width:100%;margin-top:6px">{{ $source['text'] }}</textarea></label>
                <p><button class="btn btn-ghost">Save transcripts</button></p>
            </form>
        </section>
    @endif

    <section class="panel">
        <p class="muted">{{ $intro }}</p>
        <div class="rda-buttons">
            <form method="POST" action="{{ $routes['generate'] }}">@csrf
                <button class="btn btn-primary" @disabled(! $hasText)>{{ $generateLabel }}</button>
            </form>
            @if ($words->isNotEmpty())
                <form method="POST" action="{{ $routes['translate'] }}">@csrf
                    <button class="btn btn-ghost">Fill missing translations</button>
                </form>
            @endif
            <form method="POST" action="{{ $routes['add'] }}" style="display:flex;gap:8px">@csrf
                <input type="text" name="word" class="input" placeholder="Add a word yourself" required>
                <button class="btn btn-ghost">Add</button>
            </form>
        </div>
        @unless ($hasText)<p class="rda-warn">{{ $missing }}</p>@endunless
    </section>

    @if ($words->isEmpty())
        <p class="muted">No words yet.</p>
    @else
        <form method="POST" action="{{ $routes['save'] }}">@csrf @method('PUT')
            <p class="muted">{{ $words->where('approved', true)->count() }} of {{ $words->count() }} approved.
                <label><input type="checkbox" onclick="document.querySelectorAll('[data-ap]').forEach(c => c.checked = this.checked)"> Tick all as approved</label></p>
            @foreach ($words as $w)
                <section class="panel" style="margin-bottom:12px">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                        <input type="text" class="input" name="w[{{ $w->id }}][word]" value="{{ $w->word }}" style="flex:2;min-width:180px;font-weight:700">
                        <input type="text" class="input" name="w[{{ $w->id }}][type]" value="{{ $w->type }}" placeholder="type" style="flex:1;min-width:110px">
                        <label><input type="checkbox" data-ap name="w[{{ $w->id }}][approved]" value="1" @checked($w->approved)> Approved</label>
                        <label class="muted"><input type="checkbox" name="w[{{ $w->id }}][delete]" value="1"> Delete</label>
                    </div>
                    <textarea class="input" rows="2" name="w[{{ $w->id }}][meaning]" placeholder="Meaning" style="width:100%;margin-top:8px">{{ $w->meaning }}</textarea>
                    <input type="text" class="input" name="w[{{ $w->id }}][synonyms]" value="{{ implode(', ', $w->synonyms ?? []) }}" placeholder="Synonyms, separated by commas" style="width:100%;margin-top:8px">
                    <input type="text" class="input" name="w[{{ $w->id }}][example]" value="{{ $w->example }}" placeholder="Example sentence" style="width:100%;margin-top:8px">
                    <details style="margin-top:8px"><summary class="muted">Translations</summary>
                        @foreach (\App\Models\VocabWord::LANGUAGES as $code => $name)
                            <label style="display:block;margin-top:6px"><span class="muted">{{ $name }}</span>
                                <input type="text" class="input" name="w[{{ $w->id }}][tr][{{ $code }}]" value="{{ $w->translations[$code] ?? '' }}" style="width:100%" @if (in_array($code, ['ar', 'ur'])) dir="rtl" @endif></label>
                        @endforeach
                    </details>
                </section>
            @endforeach
            <div class="save-bar"><span class="muted">Click Save to keep your changes.</span><button class="btn btn-primary">Save</button></div>
        </form>
    @endif
@endsection
