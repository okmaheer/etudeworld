@extends('admin.layout')

@section('title', 'English Level Tests')

@section('actions')
    <a href="{{ route('admin.level.create') }}" class="btn btn-sm btn-primary">+ Add a test</a>
@endsection

@section('content')
    <div class="test-list">
        @forelse ($tests as $test)
            <div class="test-tab">
                <div class="test-name">
                    <a href="{{ route('admin.level.edit', $test) }}">{{ $test->title }}</a>
                    <div class="meta-line">
                        <span class="badge badge-blue">{{ $test->questionCount() }} questions</span>
                        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
                        <span class="muted-inline">/english-level-test/{{ $test->slug }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ route('admin.level.edit', $test) }}" class="btn btn-sm btn-ghost">Edit</a>
                    @if ($test->status === 'active')
                        <a href="{{ route('level-test.show', $test->slug) }}" target="_blank" class="btn btn-sm btn-ghost">View</a>
                    @endif
                    <form method="POST" action="{{ route('admin.level.destroy', $test) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete “{{ $test->title }}” and all its questions?">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="panel"><p>No tests yet. <a href="{{ route('admin.level.create') }}">Add the first one</a>.</p></div>
        @endforelse
    </div>
@endsection
