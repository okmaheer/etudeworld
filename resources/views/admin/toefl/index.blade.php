@extends('admin.layout')

@section('title', 'All TOEFL Tests')

@section('actions')
    <a href="{{ route('admin.toefl.create') }}" class="btn btn-sm btn-primary">+ Add a test</a>
@endsection

@section('content')
    <div class="filters">
        <a href="{{ route('admin.toefl.index') }}" class="chip {{ request()->hasAny(['type', 'status']) ? '' : 'is-active' }}">All</a>
        <a href="{{ route('admin.toefl.index', ['type' => 'free']) }}" class="chip {{ request('type') === 'free' ? 'is-active' : '' }}">Free</a>
        <a href="{{ route('admin.toefl.index', ['type' => 'paid']) }}" class="chip {{ request('type') === 'paid' ? 'is-active' : '' }}">Paid</a>
        <a href="{{ route('admin.toefl.index', ['status' => 'active']) }}" class="chip {{ request('status') === 'active' ? 'is-active' : '' }}">Active</a>
        <a href="{{ route('admin.toefl.index', ['status' => 'draft']) }}" class="chip {{ request('status') === 'draft' ? 'is-active' : '' }}">Drafts</a>
    </div>

    <div class="test-list">
        @forelse ($tests as $test)
            <div class="test-tab">
                <div class="test-name">
                    <a href="{{ route('admin.toefl.edit', $test) }}">{{ $test->name }}</a>
                    <div class="meta-line">
                        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
                        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
                    </div>
                </div>
                <div class="test-actions">
                    <div class="module-icons">
                        @foreach (\App\Support\Toefl::SECTIONS as $key => $mod)
                            <a href="{{ route('admin.toefl.module', [$test, $key]) }}" class="icon-btn icon-btn--{{ $key }} {{ $test->moduleFilled($key) ? 'is-filled' : '' }}"
                               title="{{ $mod['label'] }}{{ $test->moduleFilled($key) ? ' (questions added)' : '' }}" aria-label="{{ $mod['label'] }} questions for {{ $test->name }}">
                                @include('admin.partials.icon', ['name' => $mod['icon']])
                            </a>
                        @endforeach
                    </div>
                    <span class="divider" aria-hidden="true"></span>
                    <a href="{{ route('admin.toefl.edit', $test) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $test->name }}">@include('admin.partials.icon', ['name' => 'edit'])</a>
                    <form method="POST" action="{{ route('admin.toefl.destroy', $test) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $test->name }}"
                                data-confirm="Delete “{{ $test->name }}”? This removes the test permanently.">@include('admin.partials.icon', ['name' => 'trash'])</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="panel empty-state">
                <div class="empty-icon">@include('admin.partials.icon', ['name' => 'test'])</div>
                <h2>No TOEFL tests {{ request()->hasAny(['type', 'status']) ? 'match this filter' : 'yet' }}</h2>
                <p class="muted">Tests you add will appear here.</p>
                <a href="{{ route('admin.toefl.create') }}" class="btn btn-primary">Add a TOEFL test</a>
            </div>
        @endforelse
    </div>

    @if ($tests->isNotEmpty())
        <p class="legend">
            <span>@include('admin.partials.icon', ['name' => 'pen']) Reading</span>
            <span>@include('admin.partials.icon', ['name' => 'headphones']) Listening</span>
            <span>@include('admin.partials.icon', ['name' => 'page']) Writing</span>
            <span>@include('admin.partials.icon', ['name' => 'mic']) Speaking</span>
            <span><span class="filled-dot" aria-hidden="true"></span> Questions added</span>
        </p>
    @endif
@endsection
