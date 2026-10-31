{{-- Menu items, any number of levels deep. An item with 'items' gets its own sub-menu. --}}
@foreach ($items as $j => $item)
    @if (! empty($item['items']))
        <li class="has-sub">
            {{-- A plain label (not clickable, not a page). Its sub-menu opens on hover;
                 on phones the small arrow button opens it. --}}
            <div class="sub-row">
                @if (! empty($item['link']))
                    <a class="sub-label" href="{{ $item['url'] ?? route($item['route']) }}">{{ $item['label'] }}</a>
                @else
                    <span class="sub-label">{{ $item['label'] }}</span>
                @endif
                <button type="button" class="sub-toggle" aria-expanded="false" aria-controls="{{ $prefix }}-{{ $j }}" aria-label="Show {{ $item['label'] }} options" tabindex="0">
                    <svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M7.5 2.5 4 6l3.5 3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <ul class="submenu" id="{{ $prefix }}-{{ $j }}">
                @include('partials.nav-items', ['items' => $item['items'], 'prefix' => $prefix . '-' . $j])
            </ul>
        </li>
    @else
        <li><a href="{{ $item['url'] ?? route($item['route']) }}">{{ $item['label'] }}</a></li>
    @endif
@endforeach
