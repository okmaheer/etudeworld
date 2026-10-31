@if ($items->isEmpty())
    <p class="celpip-empty">{{ $empty }}</p>
@else
    <div class="test-grid">
        @foreach ($items as $t)
            @include('celpip.partials.card', ['test' => $t, 'module' => $module])
        @endforeach
    </div>
@endif
