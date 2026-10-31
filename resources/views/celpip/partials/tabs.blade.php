{{-- Tabs to move between the mock tests and each skill. --}}
@php $order = array_values(array_filter(['reading', 'listening', 'writing', 'speaking'], fn ($m) => $format !== 'ls' || \App\Models\CelpipTest::MODULES[$m]['ls'])); @endphp
<nav class="celpip-tabs" aria-label="CELPIP practice sections">
    <a href="{{ route('celpip.practice.mocks', $format) }}" class="{{ ($active ?? null) === 'mock' ? 'is-active' : '' }}">
        @include('admin.partials.icon', ['name' => 'layers']) Full mock tests
    </a>
    @foreach ($order as $m)
        <a href="{{ route('celpip.practice.module', [$format, $m]) }}" class="{{ ($active ?? null) === $m ? 'is-active' : '' }}">
            @include('admin.partials.icon', ['name' => \App\Models\CelpipTest::PUBLIC_MODULES[$m]['icon']]) {{ \App\Models\CelpipTest::PUBLIC_MODULES[$m]['label'] }}
        </a>
    @endforeach
</nav>
