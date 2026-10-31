@php
    $menu = [
        [
            // Language → exam. "Language Exams", "English", "French"… are labels only (no page).
            'label' => 'English Language Exams',
            'route' => null,
            'items' => [
                ['label' => 'IELTS',           'route' => 'ielts', 'link' => true, 'items' => [
                    ['label' => 'IELTS Academic',         'route' => 'ielts.academic'],
                    ['label' => 'IELTS General Training', 'route' => 'ielts.general'],
                ]],
                ['label' => 'CELPIP', 'route' => 'celpip', 'link' => true, 'items' => [
                    ['label' => 'CELPIP General', 'route' => 'celpip.general'],
                    ['label' => 'CELPIP LS',      'route' => 'celpip.ls'],
                ]],
                ['label' => 'TOEFL', 'route' => 'toefl', 'link' => true, 'items' => [
                    ['label' => 'TOEFL iBT 2026 Practice Tests', 'route' => 'toefl.hub'],
                ]],
            ],
        ],
        [
            // Learn a language: one page per language (the pages live at /languages/{language})
            'label' => 'Languages',
            'route' => null,
            'items' => collect(\App\Models\LanguageModule::LANGUAGES)->map(fn ($l, $k) => ['label' => 'Learn ' . $l['name'], 'url' => route('languages.show', $k)])->values()->all(),
        ],
        [
            'label' => 'Vocabulary Builder',
            'route' => null,
            'items' => [
                ['label' => 'IELTS', 'route' => 'vocab.ielts', 'link' => true, 'items' => [
                    ['label' => 'Listening', 'route' => 'vocab.ielts.listening'],
                    ['label' => 'Reading',   'route' => 'vocab.ielts.reading'],
                    ['label' => 'Writing',   'route' => 'vocab.ielts.writing'],
                    ['label' => 'Speaking',  'route' => 'vocab.ielts.speaking'],
                    ['label' => 'Important Spellings for IELTS Listening', 'route' => 'vocab.ielts.spellings'],
                ]],
                ['label' => 'CELPIP', 'route' => 'vocab.celpip', 'link' => true, 'items' => [
                    ['label' => 'Listening', 'route' => 'vocab.celpip.listening'],
                    ['label' => 'Reading',   'route' => 'vocab.celpip.reading'],
                    ['label' => 'Writing',   'route' => 'vocab.celpip.writing'],
                    ['label' => 'Speaking',  'route' => 'vocab.celpip.speaking'],
                ]],
            ],
        ],
        [
            'label' => 'Tools',
            'route' => null,
            'items' => [
                ['label' => 'Study Planner',                     'route' => 'tools.study-planner'],
                ['label' => 'IELTS Overall Score Calculator',    'route' => 'tools.ielts-calculator'],
                ['label' => 'IELTS to TOEFL Converter',          'route' => 'tools.ielts-to-toefl'],
                ['label' => 'IELTS to PTE Converter',            'route' => 'tools.ielts-to-pte'],
                ['label' => 'IELTS to CELPIP Converter',         'route' => 'tools.ielts-to-celpip'],
                ['label' => 'IELTS to CEFR Converter',           'route' => 'tools.ielts-to-cefr'],
                ['label' => 'English Level Test / CEFR Calculator', 'route' => 'tools.cefr-test'],
            ],
        ],
        [
            'label' => 'Blogs',
            'route' => null,
            'items' => [
                ['label' => 'Language Learning', 'route' => 'blog.language'],
                ['label' => 'IELTS',             'route' => 'blog.ielts'],
                ['label' => 'CELPIP',            'route' => 'blog.celpip'],
                ['label' => 'TOEFL',             'route' => 'blog.toefl'],
                ['label' => 'Study Methods',     'route' => 'blog.study'],
            ],
        ],
    ];

    // Sample Answers: several levels deep, built from app/Support/SampleAnswers.php
    $sample = \App\Support\SampleAnswers::menu();
    $hubs = \App\Support\SampleAnswers::hubs();
    $toItems = function (array $nodes) use (&$toItems, $hubs) {
        return array_map(fn ($n) => [
            'label' => $n['label'],
            'route' => $n['route'],
            'link'  => isset($hubs[$n['route']]),   // Speaking / Writing are real hub pages
            'items' => $toItems($n['children'] ?? []),
        ], $nodes);
    };
    $menu[] = ['label' => $sample['label'], 'route' => $sample['route'], 'items' => $toItems($sample['children'])];
@endphp

<header class="site-header" id="top">
    <nav class="nav container" aria-label="Main navigation">
        <a href="{{ route('home') }}" class="brand" aria-label="{{ config('app.name') }} home">
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="44" height="44">
            <span>Etude <strong>World</strong></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-menu" id="nav-menu">
            @foreach ($menu as $i => $group)
                <li class="nav-item has-dropdown">
                    <button class="nav-link" type="button" aria-expanded="false" aria-controls="dd-{{ $i }}">
                        {{ $group['label'] }}
                        <svg class="chev" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <ul class="dropdown" id="dd-{{ $i }}">
                        @include('partials.nav-items', ['items' => $group['items'], 'prefix' => 'dd-' . $i])
                    </ul>
                </li>
            @endforeach
        </ul>

        {{-- Search: the magnifier opens a search bar under the menu --}}
        <button type="button" class="nav-search-btn" aria-expanded="false" aria-controls="site-search" aria-label="Search the website" data-search-toggle>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        </button>

        {{-- Student account: log in / sign up, or the student's portal --}}
        @php $student = auth('student')->user(); @endphp
        <div class="nav-account {{ $student ? 'has-dropdown' : '' }}">
            @if ($student)
                <button type="button" class="nav-avatar" aria-expanded="false" aria-label="My account" data-account-toggle>
                    @if ($student->avatar) <img src="{{ $student->avatar }}" alt="" referrerpolicy="no-referrer"> @else {{ $student->initials() }} @endif
                </button>
                <div class="account-menu" data-account-menu hidden>
                    <p><strong>{{ $student->name }}</strong><small>{{ $student->email }}</small></p>
                    <a href="{{ route('student.portal') }}">My portal &amp; results</a>
                    <form method="POST" action="{{ route('student.logout') }}">@csrf<button type="submit">Log out</button></form>
                </div>
            @else
                <a href="{{ route('student.login', request()->routeIs('student.login') ? [] : ['return' => request()->fullUrl()]) }}" class="nav-login" aria-label="Log in or sign up">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                    <span>Log in</span>
                </a>
            @endif
        </div>
    </nav>
    <div class="search-bar" id="site-search" hidden>
        <form action="{{ route('search') }}" method="GET" role="search" class="container search-bar__form">
            <label class="sr-only" for="hs-q">Search the website</label>
            <input id="hs-q" type="search" name="q" maxlength="60" placeholder="Search tests, tools, lessons, blog posts…" autocomplete="off" required minlength="2">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
    </div>
</header>
