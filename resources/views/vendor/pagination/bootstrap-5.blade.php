@if ($paginator->hasPages())
    <nav style="display:flex; align-items:center; justify-content:center; gap:6px;">
        @if ($paginator->onFirstPage())
            <span style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:6px; color:#cbd5e1; font-size:12px;">‹ Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#1b7f79; font-size:12px; font-weight:600; text-decoration:none;">‹ Prev</a>
        @endif

        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding:6px 12px; background:#1b7f79; color:#fff; border-radius:6px; font-size:12px; font-weight:700;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#627386; font-size:12px; text-decoration:none;">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:6px 12px; border:1px solid #dce5ee; border-radius:6px; color:#1b7f79; font-size:12px; font-weight:600; text-decoration:none;">Next ›</a>
        @else
            <span style="padding:6px 12px; border:1px solid #e5e7eb; border-radius:6px; color:#cbd5e1; font-size:12px;">Next ›</span>
        @endif

        <span style="font-size:11px; color:#9ca3af; margin-left:6px;">
            {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} / {{ $paginator->total() }}
        </span>
    </nav>
@endif
