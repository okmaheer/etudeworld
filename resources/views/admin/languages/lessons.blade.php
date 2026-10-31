@extends('admin.layout')

@section('title', $module->title . ' – Lessons')

@section('actions')
    <a href="{{ route('admin.languages.index', $language) }}" class="btn btn-sm btn-ghost">← All modules</a>
    <a href="{{ route('admin.languages.lessons.create', [$language, $module]) }}" class="btn btn-sm btn-primary">+ Add lesson</a>
@endsection

@section('content')

    <div class="panel no-pad">
        @forelse ($lessons as $l)
            <div class="blog-row">
                <div class="blog-info">
                    <a href="{{ route('admin.languages.lessons.edit', [$language, $module, $l]) }}" class="blog-title">{{ $l->title }}</a>
                    <div class="meta-line">
                        @if ($l->level) <span class="badge badge-blue">{{ $l->level }}</span> @endif
                        <span class="badge {{ $l->is_published ? 'badge-green' : 'badge-grey' }}">{{ $l->is_published ? 'Published' : 'Hidden' }}</span>
                        <span class="muted">order {{ $l->sort_order }} · updated {{ $l->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ $l->setRelation('module', $module)->url }}" target="_blank" class="icon-btn" title="View lesson" aria-label="View {{ $l->title }}">
                        @include('admin.partials.icon', ['name' => 'external'])
                    </a>
                    <a href="{{ route('admin.languages.lessons.edit', [$language, $module, $l]) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $l->title }}">
                        @include('admin.partials.icon', ['name' => 'edit'])
                    </a>
                    <form method="POST" action="{{ route('admin.languages.lessons.destroy', [$language, $module, $l]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $l->title }}"
                                data-confirm="Delete “{{ $l->title }}” permanently?">
                            @include('admin.partials.icon', ['name' => 'trash'])
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <h2>No lessons yet</h2>
                <p class="muted">Add the first lesson for “{{ $module->title }}”.</p>
                <a href="{{ route('admin.languages.lessons.create', [$language, $module]) }}" class="btn btn-primary">Add lesson</a>
            </div>
        @endforelse
    </div>
@endsection
