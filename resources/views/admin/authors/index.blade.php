@extends('admin.layout')

@section('title', 'All Authors')

@section('actions')
    <a href="{{ route('admin.authors.create') }}" class="btn btn-sm btn-primary">+ Add new author</a>
@endsection

@section('content')
    @if ($authors->isEmpty())
        <div class="panel empty-state">
            <div class="empty-icon">@include('admin.partials.icon', ['name' => 'author'])</div>
            <h2>No authors yet</h2>
            <p class="muted">Add the people who write your blogs. You can then pick an author on each blog.</p>
            <a href="{{ route('admin.authors.create') }}" class="btn btn-primary">Add new author</a>
        </div>
    @else
        <div class="author-grid">
            @foreach ($authors as $author)
                <div class="author-card">
                    <div class="author-avatar">
                        @if ($author->image)
                            <img src="{{ asset($author->image) }}" alt="">
                        @else
                            <span>{{ $author->initials }}</span>
                        @endif
                    </div>
                    <div class="author-info">
                        <a href="{{ route('admin.authors.edit', $author) }}" class="author-name">{{ $author->name }}</a>
                        <p class="muted">{{ $author->blogs_count }} {{ \Illuminate\Support\Str::plural('blog', $author->blogs_count) }}</p>
                        <div class="author-socials">
                            @forelse ($author->social_links as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="social-badge social-badge--{{ $link['key'] }}" title="{{ $link['label'] }}">
                                    @include('partials.social-icon', ['name' => $link['key']])
                                </a>
                            @empty
                                <span class="muted small">No social links</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="row-actions">
                        <a href="{{ route('authors.show', $author) }}" target="_blank" class="icon-btn" title="View author page" aria-label="View {{ $author->name }}'s page">
                            @include('admin.partials.icon', ['name' => 'external'])
                        </a>
                        <a href="{{ route('admin.authors.edit', $author) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $author->name }}">
                            @include('admin.partials.icon', ['name' => 'edit'])
                        </a>
                        <form method="POST" action="{{ route('admin.authors.destroy', $author) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $author->name }}"
                                    data-confirm="Delete {{ $author->name }}?{{ $author->blogs_count ? ' Their ' . $author->blogs_count . ' blog(s) will be kept but will show no author.' : '' }}">
                                @include('admin.partials.icon', ['name' => 'trash'])
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
