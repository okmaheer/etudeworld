@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pages">
        @if ($paginator->onFirstPage())
            <span class="page disabled">‹ Prev</span>
        @else
            <a class="page" href="{{ $paginator->previousPageUrl() }}">‹ Prev</a>
        @endif
        <span class="page current">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a class="page" href="{{ $paginator->nextPageUrl() }}">Next ›</a>
        @else
            <span class="page disabled">Next ›</span>
        @endif
    </nav>
@endif
