@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" style="display:flex; align-items:center; gap:8px;">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:6px; color:#9ca3af; font-size:13px; cursor:not-allowed;">← Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#1b7f79; font-size:13px; text-decoration:none; font-weight:600;">← Prev</a>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding:6px 12px; background:#1b7f79; color:#fff; border-radius:6px; font-size:13px; font-weight:700;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#627386; font-size:13px; text-decoration:none;">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#1b7f79; font-size:13px; text-decoration:none; font-weight:600;">Next →</a>
        @else
            <span style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:6px; color:#9ca3af; font-size:13px; cursor:not-allowed;">Next →</span>
        @endif

        {{-- Showing text --}}
        <span style="font-size:12px; color:#9ca3af; margin-left:8px;">
            {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>
    </nav>
@endif
