<nav class="celpip-tabs" aria-label="TOEFL practice sections">
    <a href="{{ route('toefl.mocks') }}" class="{{ ($active ?? null) === 'mock' ? 'is-active' : '' }}">@include('admin.partials.icon', ['name' => 'layers']) Full mock tests</a>
    @foreach (\App\Support\Toefl::PUBLIC as $m => $info)
        <a href="{{ route('toefl.module', $m) }}" class="{{ ($active ?? null) === $m ? 'is-active' : '' }}">@include('admin.partials.icon', ['name' => $info['icon']]) {{ $info['label'] }}</a>
    @endforeach
</nav>
