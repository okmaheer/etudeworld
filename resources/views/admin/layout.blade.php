<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="editor-content-css" content="{{ asset('css/content-blocks.css') }}?v=3">
    <meta name="editor-upload-url" content="{{ route('admin.editor-upload') }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }} Admin</title>
    <link rel="icon" href="{{ \App\Models\SiteSetting::logoUrl() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=25">
    @stack('head')
</head>
<body class="admin">
@php
    $is = fn (...$patterns) => request()->routeIs(...$patterns) ? 'is-active' : '';
    $seoHomeTab = request()->routeIs('admin.seo.*') && request('tab') === 'site-home';
    $homeOpen = request()->routeIs('admin.site-settings.home*', 'admin.achievements.*') || $seoHomeTab;
    $websiteOpen = request()->routeIs('admin.site-settings.site*', 'admin.site-settings.contact*', 'admin.info-pages.*');
    $clientsOpen = request()->routeIs('admin.clients.*');
    $unreadMessages = \Illuminate\Support\Facades\Schema::hasTable('contact_messages') ? \App\Models\ContactMessage::where('is_read', false)->count() : 0;
    $coursesOpen = request()->routeIs('admin.courses.*', 'admin.course-requests.*', 'admin.course-bookings.*');
    $newRequests = \Illuminate\Support\Facades\Schema::hasTable('course_requests') ? \App\Models\CourseRequest::where('status', 'new')->count() : 0;
    $authorsOpen = request()->routeIs('admin.authors.*');
    $celpipOpen = request()->routeIs('admin.celpip.*');
    $ieltsOpen = request()->routeIs('admin.ielts.*');
    $toeflOpen = request()->routeIs('admin.toefl.*');
    $levelOpen = request()->routeIs('admin.level.*');
        $germanOpen = request()->routeIs('admin.languages.*');
    $vocabOpen = request()->routeIs('admin.vocab-topics*');
    $vocabGroupOpen = $vocabOpen || request()->routeIs('admin.vocab-overview', 'admin.spellings*');
    $evalPending = fn ($exam) => \App\Models\TestSubmission::where('exam', $exam)->where('status', 'pending')->count();
    $evalOpen = request()->routeIs('admin.evaluations.*');
    $evalExam = request()->route('exam');
    $evalTotal = $evalPending('celpip') + $evalPending('ielts') + $evalPending('toefl');
    $testsOpen = $celpipOpen || $ieltsOpen || $toeflOpen || $levelOpen || $evalOpen;
@endphp

<aside class="sidebar" id="sidebar">
    <a href="{{ route('admin.dashboard') }}" class="side-brand">
        <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="34" height="34">
        <span>Etude <strong>World</strong><small>Admin</small></span>
    </a>

    <nav class="side-nav" aria-label="Admin navigation">
        <a href="{{ route('admin.dashboard') }}" class="side-link {{ $is('admin.dashboard') }}">
            @include('admin.partials.icon', ['name' => 'home']) Dashboard
        </a>
        <div class="side-group {{ $homeOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $homeOpen ? 'is-active' : '' }}" aria-expanded="{{ $homeOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'home']) Home page
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.site-settings.home') }}" class="{{ $is('admin.site-settings.home*') }}">Hero &amp; cards</a>
                <a href="{{ route('admin.achievements.index') }}" class="{{ $is('admin.achievements.*') }}">Achievements</a>
                <a href="{{ route('admin.seo.index', ['tab' => 'site-home']) }}" class="{{ $seoHomeTab ? 'is-active' : '' }}">Title, description &amp; headings</a>
            </div>
        </div>
        <div class="side-group {{ $websiteOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $websiteOpen ? 'is-active' : '' }}" aria-expanded="{{ $websiteOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'page']) Website
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.site-settings.site') }}" class="{{ $is('admin.site-settings.site*') }}">Logo, footer &amp; social</a>
                <a href="{{ route('admin.site-settings.contact') }}" class="{{ $is('admin.site-settings.contact*') }}">Contact details &amp; hours</a>
                <a href="{{ route('admin.info-pages.index') }}" class="{{ $is('admin.info-pages.*') }}">Pages (About, Contact…)</a>
            </div>
        </div>
        <a href="{{ route('admin.blogs.create') }}" class="side-link {{ $is('admin.blogs.*') }}">
            @include('admin.partials.icon', ['name' => 'blog']) Blogs
        </a>
        <div class="side-group {{ $germanOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $germanOpen ? 'is-active' : '' }}" aria-expanded="{{ $germanOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'page']) Languages
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                @foreach (\App\Models\LanguageModule::LANGUAGES as $langKey => $langInfo)
                    <a href="{{ route('admin.languages.index', $langKey) }}" class="{{ $germanOpen && request()->route('language') === $langKey ? 'is-active' : '' }}">{{ $langInfo['name'] }}</a>
                @endforeach
            </div>
        </div>
        <div class="side-group {{ $testsOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $testsOpen ? 'is-active' : '' }}" aria-expanded="{{ $testsOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'test']) Tests
                @if ($evalTotal) <span class="side-count">{{ $evalTotal }}</span> @endif
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.ielts.index') }}" class="{{ $ieltsOpen ? 'is-active' : '' }}">IELTS tests</a>
                <a href="{{ route('admin.celpip.index') }}" class="{{ $celpipOpen ? 'is-active' : '' }}">CELPIP tests</a>
                <a href="{{ route('admin.toefl.index') }}" class="{{ $toeflOpen ? 'is-active' : '' }}">TOEFL tests</a>
                <a href="{{ route('admin.level.index') }}" class="{{ $levelOpen ? 'is-active' : '' }}">English level tests</a>
                @foreach (['ielts' => 'IELTS', 'celpip' => 'CELPIP', 'toefl' => 'TOEFL'] as $examKey => $examName)
                    <a href="{{ route('admin.evaluations.index', $examKey) }}" class="{{ $evalOpen && $evalExam === $examKey ? 'is-active' : '' }}">
                        {{ $examName }} evaluations @if ($n = $evalPending($examKey)) <span class="side-count">{{ $n }}</span> @endif
                    </a>
                @endforeach
            </div>
        </div>

        <a href="{{ route('admin.seo.index') }}" class="side-link {{ $seoHomeTab ? '' : $is('admin.seo.*') }}">
            @include('admin.partials.icon', ['name' => 'page']) SEO
        </a>

        <div class="side-group {{ $vocabGroupOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $vocabGroupOpen ? 'is-active' : '' }}" aria-expanded="{{ $vocabGroupOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'list']) Vocabulary
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.vocab-overview') }}" class="{{ $is('admin.vocab-overview') }}">Vocabulary overview</a>
                <a href="{{ route('admin.vocab-topics') }}" class="{{ $vocabOpen ? 'is-active' : '' }}">Vocabulary topics</a>
                <a href="{{ route('admin.spellings') }}" class="{{ $is('admin.spellings*') }}">Listening spellings</a>
            </div>
        </div>

        <div class="side-group {{ $authorsOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $authorsOpen ? 'is-active' : '' }}" aria-expanded="{{ $authorsOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'author']) Manage Authors
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.authors.create') }}" class="{{ $is('admin.authors.create') }}">Add new author</a>
                <a href="{{ route('admin.authors.index') }}" class="{{ $is('admin.authors.index', 'admin.authors.edit') }}">All authors</a>
            </div>
        </div>

        <a href="{{ route('admin.messages.index') }}" class="side-link {{ $is('admin.messages.*') }}">
            @include('admin.partials.icon', ['name' => 'mail']) Messages
            @if ($unreadMessages) <span class="side-count">{{ $unreadMessages }}</span> @endif
        </a>
        <div class="side-group {{ $coursesOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $coursesOpen ? 'is-active' : '' }}" aria-expanded="{{ $coursesOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'page']) Courses
                @if ($newRequests) <span class="side-count">{{ $newRequests }}</span> @endif
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.courses.index') }}" class="{{ $is('admin.courses.*') }}">Course cards</a>
                <a href="{{ route('admin.course-requests.index') }}" class="{{ $is('admin.course-requests.*') }}">Course Requests @if ($newRequests) ({{ $newRequests }} new) @endif</a>
                <a href="{{ route('admin.course-bookings.index') }}" class="{{ $is('admin.course-bookings.*') }}">Course Bookings</a>
            </div>
        </div>
        <div class="side-group {{ $clientsOpen ? 'open' : '' }}">
            <button type="button" class="side-link side-toggle {{ $clientsOpen ? 'is-active' : '' }}" aria-expanded="{{ $clientsOpen ? 'true' : 'false' }}">
                @include('admin.partials.icon', ['name' => 'users']) Manage Paid Clients
                @include('admin.partials.icon', ['name' => 'chevron', 'class' => 'chev'])
            </button>
            <div class="side-sub">
                <a href="{{ route('admin.clients.create') }}" class="{{ $is('admin.clients.create') }}">Add a new client</a>
                <a href="{{ route('admin.clients.index') }}" class="{{ $is('admin.clients.index') }}">All clients</a>
            </div>
        </div>
    </nav>

</aside>
<div class="sidebar-backdrop" data-close-sidebar></div>

<div class="main">
    <header class="topbar">
        <button class="menu-btn" type="button" data-open-sidebar aria-label="Open menu">
            @include('admin.partials.icon', ['name' => 'menu'])
        </button>
        <h1 class="page-title">@yield('title', 'Dashboard')</h1>
        <div class="topbar-right">
            @yield('actions')
            <div class="admin-account">
                <button type="button" class="admin-avatar" aria-expanded="false" aria-label="My account" data-admin-account>
                    @include('admin.partials.icon', ['name' => 'author'])
                </button>
                <div class="admin-account__menu" data-admin-menu hidden>
                    <p><strong>Signed in as</strong><small>{{ auth()->user()->email }}</small></p>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit">@include('admin.partials.icon', ['name' => 'logout']) Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="content">
        @if (session('success'))
            <div class="alert alert-success" role="status">
                @include('admin.partials.icon', ['name' => 'check']) {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                @include('admin.partials.icon', ['name' => 'warn'])
                <div>
                    <strong>Please fix the following:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="{{ asset('js/admin.js') }}?v=2"></script>
<script>
    (function () {
        var btn = document.querySelector('[data-admin-account]'), menu = document.querySelector('[data-admin-menu]');
        if (!btn || !menu) return;
        var set = function (open) { menu.hidden = !open; btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
        btn.addEventListener('click', function (e) { e.stopPropagation(); set(menu.hidden); });
        document.addEventListener('click', function (e) { if (!menu.contains(e.target)) set(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    })();
</script>
@stack('scripts')
</body>
</html>
