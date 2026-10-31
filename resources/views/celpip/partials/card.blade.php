{{--
    One test card.
    $test   – the CelpipTest
    $format – general | ls
    $module – null for a full mock test, or listening / reading / writing / speaking
--}}
@php
    $isMock  = $module === null;
    $locked  = $test->isLocked();
    $ready   = $isMock
        ? collect($test->examModules())->contains(fn ($m) => $test->moduleReady($m))
        : $test->moduleReady($module);
    $url     = $isMock
        ? route('celpip.practice.mock', [$format, $test->slug])
        : route('celpip.practice.test', [$format, $module, $test->slug]);
    $info    = $isMock ? null : \App\Models\CelpipTest::PUBLIC_MODULES[$module];
@endphp

<article class="test-card {{ $locked ? 'is-locked' : '' }} {{ $ready ? '' : 'is-soon' }} test-card--{{ $module ?? 'mock' }}">
    <div class="test-card__top">
        <span class="test-card__icon">@include('admin.partials.icon', ['name' => $isMock ? 'layers' : $info['icon']])</span>
        @if ($locked)
            <span class="pill pill--premium">@include('admin.partials.icon', ['name' => 'lock']) Premium</span>
        @elseif ($test->type === 'paid')
            <span class="pill pill--premium">Premium</span>
        @else
            <span class="pill pill--free">Free</span>
        @endif
        @if ($test->status !== 'active')
            <span class="pill pill--draft" title="Only you (admin) can see this">Draft</span>
        @endif
    </div>

    <h3 class="test-card__title">{{ $test->name }}</h3>

    @if ($isMock)
        <ul class="test-card__modules">
            @foreach ($test->examModules() as $m)
                <li class="{{ $test->moduleReady($m) ? 'is-ready' : '' }}">
                    {{ \App\Models\CelpipTest::PUBLIC_MODULES[$m]['label'] }}
                </li>
            @endforeach
        </ul>
        <p class="test-card__meta">@include('admin.partials.icon', ['name' => 'clock']) {{ $format === 'ls' ? 'about 1 hour 10 min' : 'about 3 hours' }} · all sections</p>
    @else
        <p class="test-card__meta">@include('admin.partials.icon', ['name' => 'clock']) {{ $info['time'] }} · {{ $info['parts'] }}</p>
    @endif

    <div class="test-card__foot">
        @if ($locked)
            <span class="btn-test btn-test--locked" aria-hidden="true">@include('admin.partials.icon', ['name' => 'lock']) Unlock with Premium</span>
        @elseif (! $ready)
            <span class="btn-test btn-test--soon">Coming soon</span>
        @else
            <a href="{{ $url }}" class="btn-test">Start {{ $isMock ? 'mock test' : 'test' }} @include('admin.partials.icon', ['name' => 'arrow'])</a>
        @endif
    </div>
    @if ($locked)
        @php $lockExam = 'celpip'; @endphp
        <div class="lock-screen" role="note">
            <span class="lock-screen__icon">@include('admin.partials.icon', ['name' => 'lock'])</span>
            <strong>Premium test</strong>
            <p>{{ \App\Support\PaidAccess::message($lockExam) }}</p>
            @if (! auth('student')->check())
                <a href="{{ route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']) }}" class="btn btn-primary btn-sm">Log in</a>
            @else
                <a href="{{ \Illuminate\Support\Facades\Route::has('purchase') ? route('purchase') : route('contact') }}" class="btn btn-primary btn-sm">Purchase now</a>
            @endif
        </div>
    @endif
</article>
