@extends('admin.layout')

@section('title', $info['name'] . ' – Modules')

@section('actions')
    <a href="{{ route('languages.show', $language) }}" target="_blank" class="btn btn-sm btn-ghost">View page</a>
    <a href="{{ route('admin.languages.modules.create', $language) }}" class="btn btn-sm btn-primary">+ Add module</a>
@endsection

@section('content')

    <p class="muted" style="margin-bottom:16px">A module is one learning area, like Grammar or Vocabulary. Each module has its own lessons.</p>

    <div class="panel no-pad">
        @forelse ($modules as $m)
            <div class="blog-row">
                <div style="font-size:1.8rem;width:44px;text-align:center">{{ $m->icon ?: '📘' }}</div>
                <div class="blog-info">
                    <a href="{{ route('admin.languages.lessons', [$language, $m]) }}" class="blog-title">{{ $m->title }}</a>
                    <div class="meta-line">
                        <span class="badge {{ $m->is_published ? 'badge-green' : 'badge-grey' }}">{{ $m->is_published ? 'Published' : 'Hidden' }}</span>
                        <span class="muted">{{ $m->lessons_count }} {{ \Illuminate\Support\Str::plural('lesson', $m->lessons_count) }}</span>
                        <span class="muted">· order {{ $m->sort_order }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ route('admin.languages.lessons', [$language, $m]) }}" class="btn btn-sm btn-ghost">Lessons</a>
                    <a href="{{ route('admin.languages.modules.edit', [$language, $m]) }}" class="icon-btn" title="Edit module" aria-label="Edit {{ $m->title }}">
                        @include('admin.partials.icon', ['name' => 'edit'])
                    </a>
                    <form method="POST" action="{{ route('admin.languages.modules.destroy', [$language, $m]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $m->title }}"
                                data-confirm="Delete “{{ $m->title }}” and all its {{ $m->lessons_count }} lessons? This cannot be undone.">
                            @include('admin.partials.icon', ['name' => 'trash'])
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <h2>No modules yet</h2>
                <p class="muted">Add your first {{ $info['name'] }} module.</p>
                <a href="{{ route('admin.languages.modules.create', $language) }}" class="btn btn-primary">Add module</a>
            </div>
        @endforelse
    </div>
@endsection
