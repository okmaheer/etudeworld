@extends('admin.layout')

@section('title', $label . ' Evaluation')

@section('content')
    <p class="muted ev-intro">Students' <strong>Writing</strong> answers and <strong>Speaking</strong> recordings from the {{ $label }} tests.
        Listening and Reading are marked automatically, so they don't appear here.
        @if ($pending) <strong>{{ $pending }}</strong> waiting for evaluation. @endif</p>

    @php $q = fn ($extra) => route('admin.evaluations.index', array_merge(['exam' => $exam], array_filter(array_merge(request()->only(['status', 'module', 'q']), $extra), fn ($v) => $v !== null))); @endphp
    <div class="filters">
        <a href="{{ $q(['status' => null]) }}" class="chip {{ request('status') ? '' : 'is-active' }}">All</a>
        <a href="{{ $q(['status' => 'pending']) }}" class="chip {{ request('status') === 'pending' ? 'is-active' : '' }}">Waiting</a>
        <a href="{{ $q(['status' => 'evaluated']) }}" class="chip {{ request('status') === 'evaluated' ? 'is-active' : '' }}">Evaluated</a>
        <span class="filters__sep"></span>
        <a href="{{ $q(['module' => null]) }}" class="chip {{ request('module') ? '' : 'is-active' }}">Writing + Speaking</a>
        <a href="{{ $q(['module' => 'writing']) }}" class="chip {{ request('module') === 'writing' ? 'is-active' : '' }}">Writing</a>
        <a href="{{ $q(['module' => 'speaking']) }}" class="chip {{ request('module') === 'speaking' ? 'is-active' : '' }}">Speaking</a>
        <form method="GET" class="ev-search">
            @foreach (request()->only(['status', 'module']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search student or test…">
        </form>
    </div>

    <div class="panel ev-table-wrap">
        @if ($submissions->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">@include('admin.partials.icon', ['name' => 'check'])</div>
                <h2>Nothing here yet</h2>
                <p class="muted">When students finish the Writing or Speaking section of a {{ $label }} test, their answers will appear here.</p>
            </div>
        @else
            <table class="ev-table">
                <thead><tr><th>Date</th><th>Student</th><th>Test</th><th>Section</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($submissions as $s)
                        <tr>
                            <td>{{ $s->created_at->format('M j, Y') }}<br><small class="muted">{{ $s->created_at->format('g:i a') }}</small></td>
                            <td><strong>{{ $s->student_name }}</strong><br><small class="muted">{{ $s->student_email }}</small></td>
                            <td>{{ $s->test_name }}<br><small class="muted">{{ $s->mode === 'mock' ? 'Full mock test' : 'Practice' }}</small></td>
                            <td><span class="badge {{ $s->module === 'speaking' ? 'badge-blue' : 'badge-teal' }}">{{ \App\Models\TestSubmission::MODULES[$s->module] }}</span></td>
                            <td>
                                @if ($s->isEvaluated())
                                    <span class="badge badge-green">Evaluated</span>@if ($s->score) <small>· {{ $s->score }}</small> @endif
                                @else
                                    <span class="badge badge-amber">Waiting</span>
                                @endif
                            </td>
                            <td class="ev-actions"><a href="{{ route('admin.evaluations.show', [$exam, $s]) }}" class="btn btn-sm {{ $s->isEvaluated() ? 'btn-ghost' : 'btn-primary' }}">{{ $s->isEvaluated() ? 'View' : 'Evaluate' }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="ev-pages">{{ $submissions->links('pagination::simple-default') }}</div>
        @endif
    </div>
@endsection
