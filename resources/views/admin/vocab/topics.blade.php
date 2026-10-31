@extends('admin.layout')

@section('title', 'Vocabulary topics')

@section('content')
    <section class="panel">
        <p class="muted">Writing and Speaking vocabulary is organised by topic (for example Environment, Education, Technology, Health). Add a topic, let the AI generate words for it, then review and approve them.</p>
        <form method="POST" action="{{ route('admin.vocab-topics.store') }}" style="display:flex;gap:8px;flex-wrap:wrap">@csrf
            <select name="exam" class="input"><option value="ielts">IELTS</option><option value="celpip">CELPIP</option></select>
            <select name="skill" class="input"><option value="writing">Writing</option><option value="speaking">Speaking</option></select>
            <input type="text" name="name" class="input" placeholder="Topic, e.g. Environment" required style="flex:1;min-width:200px">
            <button class="btn btn-primary">Add topic</button>
        </form>
    </section>

    @forelse ($topics->groupBy(fn ($t) => strtoupper($t->exam) . ' ' . ucfirst($t->skill)) as $group => $rows)
        <h3 style="margin:22px 0 8px">{{ $group }}</h3>
        @foreach ($rows as $t)
            <section class="panel" style="margin-bottom:10px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
                <strong style="flex:1;min-width:180px">{{ $t->name }}</strong>
                <span class="muted">{{ (int) ($counts[$t->id]->a ?? 0) }} of {{ (int) ($counts[$t->id]->c ?? 0) }} approved</span>
                <span class="badge {{ $t->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($t->status) }}</span>
                <a class="btn btn-sm btn-primary" href="{{ route('admin.vocab-topics.show', $t) }}">Open</a>
                <form method="POST" action="{{ route('admin.vocab-topics.destroy', $t) }}" onsubmit="return confirm('Delete this topic and all its words?')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost">Delete</button></form>
            </section>
        @endforeach
    @empty
        <p class="muted">No topics yet.</p>
    @endforelse
@endsection
