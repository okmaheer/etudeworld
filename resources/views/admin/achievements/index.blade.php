@extends('admin.layout')

@section('title', 'Achievements')

@section('actions')
    <a href="{{ route('admin.achievements.create') }}" class="btn btn-sm btn-primary">+ Add student story</a>
@endsection

@section('content')
    <p class="muted" style="margin-bottom:16px">Student results shown in the “Our Achievements” slideshow on the home page. Use real students, with their permission. If no story is published, the whole section is hidden.</p>

    <div class="panel no-pad">
        @forelse ($items as $a)
            <div class="blog-row">
                <div class="blog-thumb" style="background-image:url({{ $a->photo_url }});border-radius:50%;width:56px;height:56px;aspect-ratio:1"></div>
                <div class="blog-info">
                    <a href="{{ route('admin.achievements.edit', $a) }}" class="blog-title">{{ $a->name }}</a>
                    <div class="meta-line">
                        @if ($a->exam) <span class="badge badge-blue">{{ $a->exam }}</span> @endif
                        @if ($a->score) <span class="badge badge-teal">{{ $a->score }}</span> @endif
                        <span class="badge {{ $a->is_published ? 'badge-green' : 'badge-grey' }}">{{ $a->is_published ? 'Shown' : 'Hidden' }}</span>
                        <span class="muted">order {{ $a->sort_order }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ route('admin.achievements.edit', $a) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $a->name }}">@include('admin.partials.icon', ['name' => 'edit'])</a>
                    <form method="POST" action="{{ route('admin.achievements.destroy', $a) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $a->name }}" data-confirm="Delete the story of {{ $a->name }}?">@include('admin.partials.icon', ['name' => 'trash'])</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <h2>No student stories yet</h2>
                <p class="muted">The “Our Achievements” section stays hidden until you add one.</p>
                <a href="{{ route('admin.achievements.create') }}" class="btn btn-primary">Add student story</a>
            </div>
        @endforelse
    </div>
@endsection
