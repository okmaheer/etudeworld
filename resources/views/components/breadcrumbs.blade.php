@props(['items' => [], 'align' => 'left', 'schema' => true])
{{-- Breadcrumb trail. "Home" is always the first step.
     $items: the steps after Home, as [label, url]. The last one is the current page (no url needed).
     A step without a url is shown as plain text (a group name that has no page). --}}
@php
    $trail = collect($items)->map(fn ($i) => ['label' => $i[0], 'url' => $i[1] ?? null])->values();
    $lastIndex = $trail->count() - 1;
    $ld = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')]];
    foreach ($trail as $n => $t) {
        $url = $t['url'] ?: ($n === $lastIndex ? url()->current() : null);
        if ($url) $ld[] = ['@type' => 'ListItem', 'position' => count($ld) + 1, 'name' => $t['label'], 'item' => $url];
    }
@endphp
<nav class="crumbs crumbs--{{ $align }}" aria-label="Breadcrumb">
    <ol>
        <li class="crumbs__home">
            <a href="{{ route('home') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/></svg>
                <span>Home</span>
            </a>
        </li>
        @foreach ($trail as $n => $t)
            @if ($n === $lastIndex)
                <li class="crumbs__current" aria-current="page"><span>{{ $t['label'] }}</span></li>
            @elseif ($t['url'])
                <li><a href="{{ $t['url'] }}">{{ $t['label'] }}</a></li>
            @else
                <li class="crumbs__label"><span>{{ $t['label'] }}</span></li>
            @endif
        @endforeach
    </ol>
</nav>
@if ($schema && count($ld) > 1)
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $ld], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
