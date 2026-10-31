@extends('admin.layout')

@section('title', 'Important spellings (IELTS Listening)')

@section('content')
    <section class="panel">
        <p class="muted">The answers of IELTS Listening <strong>Part 1 and Part 4</strong> (fill-in-the-blank questions) are collected here automatically when you save a Listening test. Numbers, dates, very easy words and duplicates are left out. Tick <strong>Show</strong> for the words students should practise. Students only see the word, never the question.</p>
        <div class="rda-buttons">
            <form method="POST" action="{{ route('admin.spellings.rescan') }}">@csrf<button class="btn btn-ghost">Scan all Listening tests again</button></form>
            <form method="POST" action="{{ route('admin.spellings.sort') }}">@csrf<button class="btn btn-ghost">Sort into groups again</button></form>
            <form method="POST" action="{{ route('admin.spellings.add') }}" style="display:flex;gap:8px">@csrf
                <input type="text" name="word" class="input" placeholder="Add a word yourself" required>
                <button class="btn btn-ghost">Add</button>
            </form>
        </div>
    </section>

    @if ($words->isEmpty())
        <p class="muted">No words yet. Save a Listening test that has Part 1 or Part 4 fill-in-the-blank questions, or press "Scan all Listening tests again".</p>
    @else
        <datalist id="sp-cats">@foreach (\App\Support\SpellingCollector::CATEGORIES as $c)<option value="{{ $c }}">@endforeach</datalist>
        <form method="POST" action="{{ route('admin.spellings.save') }}">@csrf @method('PUT')
            <p class="muted">{{ $words->where('shown', true)->count() }} of {{ $words->count() }} shown to students.
                <label><input type="checkbox" onclick="document.querySelectorAll('[data-sh]').forEach(c => c.checked = this.checked)"> Tick all as shown</label></p>
            @foreach ($words as $w)
                <section class="panel" style="margin-bottom:8px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    <strong style="flex:1;min-width:140px">{{ $w->word }}</strong>
                    <span class="muted">in {{ count((array) $w->tests) }} {{ count((array) $w->tests) === 1 ? 'test' : 'tests' }}</span>
                    <input type="text" class="input" name="w[{{ $w->id }}][category]" value="{{ $w->category }}" placeholder="Group" list="sp-cats" style="width:190px">
                    <input type="text" class="input" name="w[{{ $w->id }}][alternatives]" value="{{ implode(', ', (array) $w->alternatives) }}" placeholder="Other spellings" style="width:170px">
                    <label><input type="checkbox" data-sh name="w[{{ $w->id }}][shown]" value="1" @checked($w->shown)> Show</label>
                    <label class="muted"><input type="checkbox" name="w[{{ $w->id }}][delete]" value="1"> Delete</label>
                </section>
            @endforeach
            <div class="save-bar"><span class="muted">Click Save to keep your changes.</span><button class="btn btn-primary">Save</button></div>
        </form>
    @endif
@endsection
