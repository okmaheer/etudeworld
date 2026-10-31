@extends('layouts.app')

@section('title', 'My Portal – ' . config('app.name'))
@push('head')
    <meta name="robots" content="noindex, follow">
@endpush

@php use App\Support\Exams; use App\Models\TestSubmission; @endphp

@section('content')
<section class="section portal">
    <div class="container">
        @if (session('success')) <p class="portal-flash">{{ session('success') }}</p> @endif

        <div class="portal-head">
            <div class="portal-avatar">
                @if ($student->avatar) <img src="{{ $student->avatar }}" alt="" referrerpolicy="no-referrer"> @else {{ $student->initials() }} @endif
            </div>
            <div>
                <h1>{{ $student->name }}</h1>
                <p class="muted">{{ $student->email }}</p>
            </div>
            <form method="POST" action="{{ route('student.logout') }}" class="portal-logout">@csrf<button type="submit" class="btn btn-ghost">Log out</button></form>
        </div>

        <nav class="portal-tabs" aria-label="Exams">
            @foreach (Exams::ALL as $key => $e)
                @php $n = count($results[$key] ?? []) + count($submissions[$key] ?? []); @endphp
                <a href="{{ route('student.portal', ['exam' => $key]) }}" class="{{ $exam === $key ? 'is-active' : '' }}">{{ $e['label'] }} @if ($n)<span>{{ $n }}</span>@endif</a>
            @endforeach
            <a href="{{ route('student.portal', ['exam' => 'vocab']) }}" class="{{ $exam === 'vocab' ? 'is-active' : '' }}">My vocabulary @if ($vocab->count())<span>{{ $vocab->count() }}</span>@endif</a>
        </nav>

        @if ($exam === 'vocab')
            @if ($vocab->isEmpty())
                <div class="portal-empty">
                    <h2>No vocabulary lists yet</h2>
                    <p>Open a word list and your progress will be saved here.</p>
                    <a href="{{ route('vocab.ielts') }}" class="btn btn-primary">Vocabulary Builder</a>
                </div>
            @else
                <div class="pv-grid">
                    @foreach ($vocab as $p)
                        @php $complete = $p->total > 0 && $p->done >= $p->total; @endphp
                        <a class="pv-card {{ $complete ? 'is-complete' : '' }}" href="{{ $p->list['show'] }}">
                            @if ($complete)<b class="pv-tag">✓ Completed</b>@endif
                            <strong>{{ $p->list['kind'] === 'topic' ? $p->list['label'] : $p->list['label'] . ' · ' . $p->list['skillLabel'] }}</strong>
                            <span>{{ $p->done }} of {{ $p->total }} words learned</span>
                            <span class="pv-bar"><i style="width:{{ $p->total ? round($p->done / $p->total * 100) : 0 }}%"></i></span>
                            <em>{{ $complete ? 'Review' : ($p->done ? 'Continue' : 'Start') }} →</em>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif

        @php $subs = $submissions[$exam] ?? collect(); $res = $results[$exam] ?? collect(); @endphp
        @if ($exam !== 'vocab')

        @if ($subs->isEmpty() && $res->isEmpty())
            <div class="portal-empty">
                <h2>No {{ Exams::ALL[$exam]['label'] }} results yet</h2>
                <p>When you finish a test, your results will appear here.</p>
                <a href="{{ route(Exams::ALL[$exam]['route']) }}" class="btn btn-primary">Practice {{ Exams::ALL[$exam]['label'] }}</a>
            </div>
        @endif

        @if ($subs->isNotEmpty() || $res->isNotEmpty())
            @include('student._progress')
        @endif

        @if ($subs->isNotEmpty())
            <h2 class="portal-h">Writing &amp; Speaking <small>evaluated by your teacher</small></h2>
            <div class="portal-list">
                @foreach ($subs as $s)
                    <article class="portal-item">
                        <div>
                            <strong>{{ $s->test_name }} · {{ TestSubmission::MODULES[$s->module] }}</strong>
                            <small>{{ $s->created_at->format('M j, Y') }} · {{ $s->mode === 'mock' ? 'Full mock test' : 'Practice' }}</small>
                        </div>
                        @if ($s->isEvaluated())
                            <span class="portal-badge is-done">{{ $s->score ?: 'Evaluated' }}</span>
                        @elseif ($s->status === 'saved')
                            <button type="button" class="btn btn-sm btn-primary" data-request="{{ route('toefl.submissions.request', $s) }}">Request feedback</button>
                        @else
                            <span class="portal-badge">Waiting for evaluation</span>
                        @endif
                        @if ($s->isEvaluated() && $s->feedback)
                            <div class="portal-feedback"><p class="portal-label">Teacher's feedback</p>{!! nl2br(e($s->feedback)) !!}</div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($res->isNotEmpty())
            <h2 class="portal-h">Listening &amp; Reading <small>marked automatically</small></h2>
            <div class="portal-list">
                @foreach ($res as $r)
                    <article class="portal-item">
                        <div>
                            <strong>{{ $r->test_name }} · {{ ucfirst($r->module) }}</strong>
                            <small>{{ $r->created_at->format('M j, Y') }} · {{ $r->mode === 'mock' ? 'Full mock test' : 'Practice' }}</small>
                        </div>
                        <span class="portal-badge is-done">{{ $r->score }} / {{ $r->total }} correct</span>
                        @if ($r->level) <p class="portal-level">{{ $r->level }}</p> @endif
                    </article>
                @endforeach
            </div>
        @endif
        @endif
    </div>
</section>
<script>
document.querySelectorAll('[data-request]').forEach(function (b) {
    b.addEventListener('click', function () {
        b.disabled = true;
        fetch(b.dataset.request, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' } })
            .then(function (r) { if (r.ok) { b.outerHTML = '<span class="portal-badge">Waiting for evaluation</span>'; } else { b.disabled = false; } });
    });
});
</script>
@endsection
