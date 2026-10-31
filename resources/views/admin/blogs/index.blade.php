@extends('admin.layout')

@section('title', 'All Blogs')

@section('actions')
    <a href="{{ route('admin.blogs.create') }}" class="btn btn-sm btn-primary">+ New blog</a>
@endsection

@section('content')
    <div class="tabs">
        <a href="{{ route('admin.blogs.create') }}" class="tab">New blog</a>
        <a href="{{ route('admin.blogs.index') }}" class="tab is-active">All blogs</a>
    </div>

    <form method="GET" action="{{ route('admin.blogs.index') }}" class="filter-bar">
        <label for="filter-category">Show</label>
        <select id="filter-category" name="category" onchange="this.form.submit()">
            <option value="">All categories</option>
            <option value="blogs" @selected(request('category') === 'blogs')>All blogs (not sample answers)</option>
            <option value="sample-answers" @selected(request('category') === 'sample-answers')>All sample answers</option>
            @foreach (\App\Models\Blog::categoryGroups() as $group => $cats)
                <optgroup label="{{ $group }}">
                    @foreach ($cats as $value => $cat)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $cat['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <noscript><button class="btn btn-sm btn-ghost">Filter</button></noscript>
        @if (request('category'))
            <a href="{{ route('admin.blogs.index') }}" class="filter-clear">Clear</a>
        @endif
    </form>

    <div class="panel no-pad">
        @forelse ($blogs as $blog)
            <div class="blog-row">
                <div class="blog-thumb" style="{{ $blog->image ? 'background-image:url(' . asset($blog->image) . ')' : '' }}"></div>
                <div class="blog-info">
                    <a href="{{ route('admin.blogs.edit', $blog) }}" class="blog-title">{{ $blog->title }}</a>
                    <div class="meta-line">
                        <span class="badge badge-blue">{{ $blog->category_label }}</span>
                        <span class="badge {{ $blog->status === 'published' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($blog->status) }}</span>
                        <span class="muted">{{ $blog->public_path }}</span>
                        <span class="muted">· updated {{ $blog->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ route('admin.blogs.edit', $blog) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $blog->title }}">
                        @include('admin.partials.icon', ['name' => 'edit'])
                    </a>
                    <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $blog->title }}"
                                data-confirm="Delete “{{ $blog->title }}” permanently?">
                            @include('admin.partials.icon', ['name' => 'trash'])
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-icon">@include('admin.partials.icon', ['name' => 'blog'])</div>
                <h2>No blogs yet</h2>
                <p class="muted">Your blogs will be listed here.</p>
                <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">Write a blog</a>
            </div>
        @endforelse
    </div>

    {{ $blogs->links('admin.partials.pagination') }}
@endsection
