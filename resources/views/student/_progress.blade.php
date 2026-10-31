{{-- Progress summary for one exam: tiles + a score chart for Listening and Reading. $res = auto-marked results, $subs = writing/speaking. --}}
@php
    $pct = fn ($r) => $r->total > 0 ? round($r->score / $r->total * 100) : 0;
    $ordered = $res->sortBy('created_at')->values();
    $avg = $ordered->count() ? round($ordered->avg($pct)) : null;
    $best = $ordered->count() ? $ordered->map($pct)->max() : null;
    $change = null;
    if ($ordered->count() >= 4) {
        $n = min(3, intdiv($ordered->count(), 2));
        $change = round($ordered->slice(-$n)->avg($pct) - $ordered->slice(0, $n)->avg($pct));
    }
    $marked = $subs->filter(fn ($s) => $s->isEvaluated())->count();
    $modules = ['listening' => 'Listening', 'reading' => 'Reading'];
@endphp
<div class="pp">
    <div class="pp-tiles">
        <div class="pp-tile"><b>{{ $res->count() + $subs->count() }}</b><span>Tests done</span></div>
        <div class="pp-tile"><b>{{ $avg !== null ? $avg . '%' : '–' }}</b><span>Average (Listening &amp; Reading)</span></div>
        <div class="pp-tile"><b>{{ $best !== null ? $best . '%' : '–' }}</b><span>Best score</span></div>
        <div class="pp-tile {{ $change === null ? '' : ($change >= 0 ? 'is-up' : 'is-down') }}">
            <b>{{ $change === null ? '–' : ($change > 0 ? '+' : '') . $change . ' pts' }}</b>
            <span>{{ $change === null ? 'Improvement (after 4 tests)' : 'Change since your first tests' }}</span>
        </div>
    </div>

    @if ($ordered->count() >= 2)
        <div class="pp-charts">
            @foreach ($modules as $mKey => $mLabel)
                @php
                    $pts = $ordered->where('module', $mKey)->values();
                    $W = 360; $H = 150; $L = 30; $R = 12; $T = 12; $B = 24;
                    $n = $pts->count();
                    $x = fn ($i) => $n > 1 ? $L + ($W - $L - $R) * $i / ($n - 1) : $L + ($W - $L - $R) / 2;
                    $y = fn ($v) => $T + ($H - $T - $B) * (1 - $v / 100);
                @endphp
                @continue($n < 2)
                <figure class="pp-chart">
                    <figcaption>{{ $mLabel }} <small>score % over time</small></figcaption>
                    <svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="{{ $mLabel }} scores over time">
                        @foreach ([0, 50, 100] as $g)
                            <line x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($g) }}" y2="{{ $y($g) }}" class="pp-grid"/>
                            <text x="{{ $L - 6 }}" y="{{ $y($g) + 4 }}" text-anchor="end" class="pp-axis">{{ $g }}</text>
                        @endforeach
                        <polyline class="pp-line" points="{{ $pts->map(fn ($r, $i) => round($x($i), 1) . ',' . round($y($pct($r)), 1))->implode(' ') }}"/>
                        @foreach ($pts as $i => $r)
                            <circle class="pp-dot" cx="{{ round($x($i), 1) }}" cy="{{ round($y($pct($r)), 1) }}" r="4"><title>{{ $r->created_at->format('M j, Y') }}: {{ $r->score }}/{{ $r->total }} ({{ $pct($r) }}%)</title></circle>
                        @endforeach
                        <text x="{{ $L }}" y="{{ $H - 6 }}" class="pp-axis">{{ $pts->first()->created_at->format('M j') }}</text>
                        <text x="{{ $W - $R }}" y="{{ $H - 6 }}" text-anchor="end" class="pp-axis">{{ $pts->last()->created_at->format('M j') }}</text>
                    </svg>
                </figure>
            @endforeach
        </div>
    @elseif ($res->count() === 1)
        <p class="pp-hint">Finish one more Listening or Reading test and your progress chart will appear here.</p>
    @endif
    @if ($marked)
        <p class="pp-hint">{{ $marked }} Writing/Speaking {{ $marked === 1 ? 'answer has' : 'answers have' }} been evaluated by your teacher. See the feedback below.</p>
    @endif
</div>
