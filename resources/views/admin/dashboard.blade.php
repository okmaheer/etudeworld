@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    @php
        $fmt = fn ($n) => $n === null ? '—' : number_format($n);
    @endphp

    <section class="stats-head">
        <h2>Website stats</h2>
        @unless ($gaConnected)
            <span class="badge badge-amber" title="Visitor numbers will appear once Google Analytics 4 is linked">Google Analytics not linked yet</span>
        @endunless
    </section>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon--green">@include('admin.partials.icon', ['name' => 'live'])</div>
            <div>
                <p class="stat-label">Live visitors <span class="live-dot" aria-hidden="true"></span></p>
                <p class="stat-value">{{ $fmt($liveVisitors) }}</p>
                <p class="stat-note">On the website right now</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue">@include('admin.partials.icon', ['name' => 'calendar'])</div>
            <div>
                <p class="stat-label">Visitors today</p>
                <p class="stat-value">{{ $fmt($todayVisitors) }}</p>
                <p class="stat-note">{{ now()->format('l, j M') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--purple">@include('admin.partials.icon', ['name' => 'chart'])</div>
            <div>
                <p class="stat-label">Visitors this month</p>
                <p class="stat-value">{{ $fmt($monthVisitors) }}</p>
                <p class="stat-note">{{ now()->format('F Y') }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--orange">@include('admin.partials.icon', ['name' => 'money'])</div>
            <div>
                <p class="stat-label">Paid clients</p>
                <p class="stat-value">{{ number_format($paidClients) }}</p>
                <p class="stat-note"><a href="{{ route('admin.clients.index') }}">Manage clients →</a></p>
            </div>
        </div>
    </div>

    <div class="two-col">
        <section class="panel">
            <div class="panel-head">
                <h2>Blogs</h2>
                <a href="{{ route('admin.blogs.create') }}" class="btn btn-sm btn-primary">+ New blog</a>
            </div>
            <div class="mini-stats">
                <div><strong>{{ $blogsPublished }}</strong><span>Published</span></div>
                <div><strong>{{ $blogsDraft }}</strong><span>Drafts</span></div>
            </div>
            @forelse ($recentBlogs as $blog)
                <a href="{{ route('admin.blogs.edit', $blog) }}" class="row-link">
                    <span class="row-title">{{ $blog->title }}</span>
                    <span class="badge {{ $blog->status === 'published' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($blog->status) }}</span>
                </a>
            @empty
                <p class="muted empty-line">No blogs yet. <a href="{{ route('admin.blogs.create') }}">Write your first one</a>.</p>
            @endforelse
        </section>

        @foreach ($examCards as $card)
            <section class="panel">
                <div class="panel-head">
                    <h2>{{ $card['name'] }} tests</h2>
                    <a href="{{ route('admin.' . $card['key'] . '.create') }}" class="btn btn-sm btn-primary">+ Add test</a>
                </div>
                <div class="mini-stats">
                    <div><strong>{{ $card['active'] }}</strong><span>Active</span></div>
                    <div><strong>{{ $card['draft'] }}</strong><span>Drafts</span></div>
                </div>
                @forelse ($card['recent'] as $test)
                    <a href="{{ route('admin.' . $card['key'] . '.edit', $test) }}" class="row-link">
                        <span class="row-title">{{ $test->name }}</span>
                        <span class="badge {{ $test->status === 'active' ? 'badge-green' : 'badge-grey' }}">{{ ucfirst($test->status) }}</span>
                    </a>
                @empty
                    <p class="muted empty-line">No tests yet. <a href="{{ route('admin.' . $card['key'] . '.create') }}">Add your first test</a>.</p>
                @endforelse
            </section>
        @endforeach
    </div>
@endsection
