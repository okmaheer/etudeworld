@extends('admin.layout')

@section('title', $info['label'] . ' Questions')

@section('actions')
    <a href="{{ route('admin.celpip.index') }}" class="btn btn-sm btn-ghost">← All CELPIP Tests</a>
@endsection

@section('content')
    <div class="module-tabs">
        @foreach (\App\Models\CelpipTest::MODULES as $key => $mod)
            @if ($test->hasModule($key))
                <a href="{{ route('admin.celpip.module', [$test, $key]) }}" class="module-tab {{ $key === $module ? 'is-active' : '' }}">
                    @include('admin.partials.icon', ['name' => $mod['icon']]) {{ $mod['label'] }}
                </a>
            @endif
        @endforeach
    </div>

    <div class="panel empty-state">
        <div class="empty-icon">@include('admin.partials.icon', ['name' => $info['icon']])</div>
        <p class="muted">{{ $test->name }} · {{ $test->format === 'ls' ? 'CELPIP LS' : 'CELPIP General' }}</p>
        <h2>{{ $info['label'] }} questions</h2>
        @unless ($test->hasModule($module))
            <p class="field-error">Note: CELPIP-LS tests don't include a {{ $info['label'] }} module.</p>
        @endunless
        <p class="muted">This is where you'll add the {{ strtolower($info['label']) }} questions for this test. We'll build this part next.</p>
        <span class="badge badge-amber">Coming soon</span>
    </div>
@endsection
