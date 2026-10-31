{{-- Tabs to move between the mock tests and each skill. --}}
<nav class="celpip-tabs" aria-label="IELTS practice sections">
    <a href="{{ route('ielts.practice.mocks', $format) }}" class="{{ ($active ?? null) === 'mock' ? 'is-active' : '' }}">
        @include('admin.partials.icon', ['name' => 'layers']) Full mock tests
    </a>
    @foreach (\App\Models\IeltsTest::PUBLIC_MODULES as $m => $info)
        <a href="{{ route('ielts.practice.module', [$format, $m]) }}" class="{{ ($active ?? null) === $m ? 'is-active' : '' }}">
            @include('admin.partials.icon', ['name' => $info['icon']]) {{ $info['label'] }}
        </a>
    @endforeach
</nav>
