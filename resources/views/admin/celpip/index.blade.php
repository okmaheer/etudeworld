@extends('admin.layout')

@section('title', 'All CELPIP Tests')

@section('actions')
    <a href="{{ route('admin.celpip.create') }}" class="btn btn-sm btn-primary">+ Add a test</a>
@endsection

@section('content')
    <div class="filters">
        <a href="{{ route('admin.celpip.index') }}" class="chip {{ request()->hasAny(['format', 'status']) ? '' : 'is-active' }}">All</a>
        <a href="{{ route('admin.celpip.index', ['format' => 'general']) }}" class="chip {{ request('format') === 'general' ? 'is-active' : '' }}">General</a>
        <a href="{{ route('admin.celpip.index', ['format' => 'ls']) }}" class="chip {{ request('format') === 'ls' ? 'is-active' : '' }}">LS</a>
        <a href="{{ route('admin.celpip.index', ['status' => 'active']) }}" class="chip {{ request('status') === 'active' ? 'is-active' : '' }}">Active</a>
        <a href="{{ route('admin.celpip.index', ['status' => 'draft']) }}" class="chip {{ request('status') === 'draft' ? 'is-active' : '' }}">Drafts</a>
    </div>

    <div class="test-list">
        @forelse ($tests as $test)
            <div class="test-tab">
                <div class="test-name">
                    <a href="{{ route('admin.celpip.edit', $test) }}">{{ $test->name }}</a>
                    <div class="meta-line">
                        <span class="badge badge-blue">{{ $test->format === 'ls' ? 'LS' : 'General' }}</span>
                        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
                        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
                    </div>
                </div>

                <div class="test-actions">
                    <div class="module-icons">
                        @foreach (\App\Models\CelpipTest::MODULES as $key => $mod)
                            @if ($test->hasModule($key))
                                <a href="{{ route('admin.celpip.module', [$test, $key]) }}" class="icon-btn icon-btn--{{ $key }} {{ $test->moduleFilled($key) ? 'is-filled' : '' }}"
                                   title="{{ $mod['label'] }}{{ $test->moduleFilled($key) ? ' (questions added)' : '' }}" aria-label="{{ $mod['label'] }} questions for {{ $test->name }}">
                                    @include('admin.partials.icon', ['name' => $mod['icon']])
                                </a>
                            @else
                                <span class="icon-btn is-disabled" title="CELPIP-LS has no {{ $mod['label'] }} module" aria-hidden="true">
                                    @include('admin.partials.icon', ['name' => $mod['icon']])
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <span class="divider" aria-hidden="true"></span>
                    <a href="{{ route('admin.celpip.edit', $test) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $test->name }}">
                        @include('admin.partials.icon', ['name' => 'edit'])
                    </a>
                    <form method="POST" action="{{ route('admin.celpip.destroy', $test) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $test->name }}"
                                data-confirm="Delete “{{ $test->name }}”? This removes the test permanently.">
                            @include('admin.partials.icon', ['name' => 'trash'])
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="panel empty-state">
                <div class="empty-icon">@include('admin.partials.icon', ['name' => 'test'])</div>
                <h2>No CELPIP tests {{ request()->hasAny(['format', 'status']) ? 'match this filter' : 'yet' }}</h2>
                <p class="muted">Tests you add will appear here.</p>
                <a href="{{ route('admin.celpip.create') }}" class="btn btn-primary">Add a CELPIP test</a>
            </div>
        @endforelse
    </div>

    @if ($tests->isNotEmpty())
        <p class="legend">
            <span>@include('admin.partials.icon', ['name' => 'page']) Writing</span>
            <span>@include('admin.partials.icon', ['name' => 'mic']) Speaking</span>
            <span>@include('admin.partials.icon', ['name' => 'pen']) Reading</span>
            <span>@include('admin.partials.icon', ['name' => 'headphones']) Listening</span>
            <span><span class="filled-dot" aria-hidden="true"></span> Questions added</span>
        </p>
    @endif
@endsection
