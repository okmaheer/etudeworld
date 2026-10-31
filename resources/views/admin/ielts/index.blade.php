@extends('admin.layout')

@section('title', 'All IELTS Tests')

@section('actions')
    <a href="{{ route('admin.ielts.create') }}" class="btn btn-sm btn-primary">+ Add a test</a>
@endsection

@section('content')
    <div class="filters">
        <a href="{{ route('admin.ielts.index') }}" class="chip {{ request()->hasAny(['format', 'status']) ? '' : 'is-active' }}">All</a>
        <a href="{{ route('admin.ielts.index', ['format' => 'academic']) }}" class="chip {{ request('format') === 'academic' ? 'is-active' : '' }}">Academic</a>
        <a href="{{ route('admin.ielts.index', ['format' => 'gt']) }}" class="chip {{ request('format') === 'gt' ? 'is-active' : '' }}">General Training</a>
        <a href="{{ route('admin.ielts.index', ['status' => 'active']) }}" class="chip {{ request('status') === 'active' ? 'is-active' : '' }}">Active</a>
        <a href="{{ route('admin.ielts.index', ['status' => 'draft']) }}" class="chip {{ request('status') === 'draft' ? 'is-active' : '' }}">Drafts</a>
    </div>

    <div class="test-list">
        @forelse ($tests as $test)
            <div class="test-tab">
                <div class="test-name">
                    <a href="{{ route('admin.ielts.edit', $test) }}">{{ $test->name }}</a>
                    <div class="meta-line">
                        <span class="badge badge-blue">{{ $test->formatShort() }}</span>
                        <span class="badge {{ $test->type === 'paid' ? 'badge-amber' : 'badge-teal' }}">{{ ucfirst($test->type) }}</span>
                        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
                        <span class="muted-inline">{{ $test->publicPath() }}</span>
                    </div>
                </div>

                <div class="test-actions">
                    <div class="module-icons">
                        {{-- Reading (GT only), Writing and Speaking are built; Listening comes next --}}
                        @foreach (\App\Models\IeltsTest::MODULES as $key => $mod)
                            @if ($test->hasModule($key))
                                @php $filled = $test->moduleReady($key); @endphp
                                <a href="{{ $test->adminModuleUrl($key) }}" class="icon-btn icon-btn--{{ $key }} {{ $filled ? 'is-filled' : '' }}"
                                   title="{{ $mod['label'] }}{{ $filled ? ' (questions added)' : '' }}" aria-label="{{ $mod['label'] }} questions for {{ $test->name }}">
                                    @include('admin.partials.icon', ['name' => $mod['icon']])
                                </a>
                            @else
                                <span class="icon-btn is-disabled" title="{{ $mod['label'] }} questions – coming soon" aria-hidden="true">
                                    @include('admin.partials.icon', ['name' => $mod['icon']])
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <span class="divider" aria-hidden="true"></span>
                    <a href="{{ route('admin.ielts.edit', $test) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $test->name }}">
                        @include('admin.partials.icon', ['name' => 'edit'])
                    </a>
                    <form method="POST" action="{{ route('admin.ielts.destroy', $test) }}">
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
                <h2>No IELTS tests {{ request()->hasAny(['format', 'status']) ? 'match this filter' : 'yet' }}</h2>
                <p class="muted">Tests you add will appear here.</p>
                <a href="{{ route('admin.ielts.create') }}" class="btn btn-primary">Add an IELTS test</a>
            </div>
        @endforelse
    </div>

    @if ($tests->isNotEmpty())
        <p class="legend">
            <span>@include('admin.partials.icon', ['name' => 'headphones']) Listening</span>
            <span>@include('admin.partials.icon', ['name' => 'pen']) Reading</span>
            <span>@include('admin.partials.icon', ['name' => 'page']) Writing</span>
            <span>@include('admin.partials.icon', ['name' => 'mic']) Speaking</span>
            <span class="muted-inline">Click an icon to add that skill's questions. Listening comes next.</span>
        </p>
    @endif
@endsection
