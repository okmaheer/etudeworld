@extends('admin.layout')

@section('title', 'Info Pages')

@section('actions')
    <a href="{{ route('admin.info-pages.create') }}" class="btn btn-sm btn-primary">+ Add page</a>
@endsection

@section('content')
    <p class="muted" style="margin-bottom:16px">Pages like About Us, Contact Us and Privacy Policy. A page stays on the “Coming soon” screen until it has text and is published.</p>

    <div class="panel no-pad">
        @foreach ($pages as $p)
            <div class="blog-row">
                <div class="blog-info">
                    <a href="{{ route('admin.info-pages.edit', $p) }}" class="blog-title">{{ $p->title }}</a>
                    <div class="meta-line">
                        <span class="badge {{ $p->is_live ? 'badge-green' : 'badge-grey' }}">{{ $p->is_live ? 'Live' : ($p->is_published ? 'No text yet' : 'Hidden') }}</span>
                        <span class="muted">{{ $p->public_path }}</span>
                        <span class="muted">· updated {{ $p->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="row-actions">
                    <a href="{{ url($p->public_path) }}" target="_blank" class="icon-btn" title="View page" aria-label="View {{ $p->title }}">@include('admin.partials.icon', ['name' => 'external'])</a>
                    <a href="{{ route('admin.info-pages.edit', $p) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $p->title }}">@include('admin.partials.icon', ['name' => 'edit'])</a>
                    @unless ($p->is_system)
                        <form method="POST" action="{{ route('admin.info-pages.destroy', $p) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="icon-btn icon-btn--danger" title="Delete" aria-label="Delete {{ $p->title }}" data-confirm="Delete “{{ $p->title }}” permanently?">@include('admin.partials.icon', ['name' => 'trash'])</button>
                        </form>
                    @endunless
                </div>
            </div>
        @endforeach
    </div>
@endsection
