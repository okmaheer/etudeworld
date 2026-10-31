@extends('admin.layout')

@section('title', 'All clients')

@section('actions')
    <a href="{{ route('admin.clients.create') }}" class="btn btn-sm btn-primary">+ Add a new client</a>
@endsection

@section('content')
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
        @foreach (['all' => 'All (' . $allCount . ')', 'active' => 'Active (' . $activeCount . ')', 'expired' => 'Expired (' . $expiredCount . ')'] as $k => $label)
            <a href="{{ route('admin.clients.index', ['show' => $k, 'q' => $search ?: null]) }}" class="btn btn-sm {{ $filter === $k ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
        @endforeach
        <form method="GET" style="margin-left:auto;display:flex;gap:8px">
            <input type="hidden" name="show" value="{{ $filter }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name or email" style="min-width:220px">
            <button class="btn btn-sm btn-ghost" type="submit">Search</button>
        </form>
    </div>

    <section class="panel" style="padding:0;overflow-x:auto">
        <table class="table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="text-align:left;color:#6b7488;font-size:.82rem">
                    <th style="padding:12px 16px">Client</th><th>Contact</th><th>Test type</th><th>Started</th><th>Ends</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $c)
                    <tr style="border-top:1px solid #e3e7ee">
                        <td style="padding:12px 16px"><strong>{{ $c->name }}</strong><br><small class="muted">{{ $c->email }}</small></td>
                        <td>{{ $c->phone ?: '—' }}</td>
                        <td>{{ \App\Models\Client::EXAMS[$c->exam] ?? $c->exam }}</td>
                        <td>{{ $c->starts_at->format('j M Y') }}</td>
                        <td>{{ $c->ends_at->format('j M Y') }} <small class="muted">({{ $c->days }} {{ $c->days === 1 ? 'day' : 'days' }})</small></td>
                        <td>
                            @if ($c->isActive())
                                <span class="badge badge-green">Active</span><br><small class="muted">{{ max(0, (int) ceil(now()->diffInDays($c->ends_at, false))) }} day(s) left</small>
                            @else
                                <span class="badge badge-grey">Expired</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;padding-right:16px;text-align:right">
                            @if ($c->isActive())
                                <form method="POST" action="{{ route('admin.clients.end', $c) }}" style="display:inline" onsubmit="return confirm('End the access of {{ $c->email }} right now?')">@csrf<button class="btn btn-sm btn-ghost" type="submit">End now</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.clients.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Remove {{ $c->email }} from the list?')">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost" type="submit" style="color:#d7263d">Remove</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted" style="padding:24px 16px">No clients here yet. <a href="{{ route('admin.clients.create') }}">Add your first client</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if ($clients->hasPages()) <div style="margin-top:14px">{{ $clients->links('admin.partials.pagination') }}</div> @endif
@endsection
