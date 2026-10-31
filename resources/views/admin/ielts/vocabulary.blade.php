@extends('admin.layout')

{{-- Vocabulary Builder: review the difficult words the AI found in the passages / transcripts. --}}
@section('title', 'Vocabulary – ' . ucfirst($module))

@section('actions')
    <a href="{{ route('admin.ielts.' . $module, $test) }}" class="btn btn-sm btn-ghost">← {{ ucfirst($module) }} questions</a>
@endsection

@section('content')
    @include('admin.ielts.reading._head')

    @unless ($hasKey)
        <div class="panel rda-help"><p><strong>One-time setup:</strong> to let the AI suggest words, add this line to the website's <code>.env</code> file: <code>OPENAI_API_KEY=your-key-here</code>, then restart the website.</p></div>
    @endunless

    <section class="panel">
        <p class="muted">The AI reads the {{ $module === 'listening' ? 'transcripts' : 'passages' }} and suggests the difficult words, with meanings, synonyms and translations. Nothing is shown to students until you tick <strong>Approved</strong>.</p>
        <div class="rda-buttons">
            <form method="POST" action="{{ route('admin.ielts.vocab.generate', [$test, $module]) }}">@csrf
                <button class="btn btn-primary" @disabled(! $hasText)>Find difficult words</button>
            </form>
            @if ($words->isNotEmpty())
                <form method="POST" action="{{ route('admin.ielts.vocab.translate', [$test, $module]) }}">@csrf
                    <button class="btn btn-ghost">Fill missing translations</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.ielts.vocab.add', [$test, $module]) }}" style="display:flex;gap:8px">@csrf
                <input type="text" name="word" class="input" placeholder="Add a word yourself" required>
                <button class="btn btn-ghost">Add</button>
            </form>
        </div>
        @unless ($hasText)<p class="rda-warn">Add the {{ $module === 'listening' ? 'transcripts' : 'passages' }} first.</p>@endunless
    </section>

    @if ($words->isEmpty())
        <p class="muted">No words yet.</p>
    @else
        <form method="POST" action="{{ route('admin.ielts.vocab.save', [$test, $module]) }}">@csrf @method('PUT')
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
