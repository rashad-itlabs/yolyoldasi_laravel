{{-- Laravel-in hazır səhifələmə görünüşləri Tailwind/Bootstrap tələb edir,
     panel isə öz CSS-i ilə işləyir — ona görə burada əl ilə qurulub. --}}
@if ($paginator->hasPages())
  @php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $from    = max(1, $current - 2);
    $to      = min($last, $current + 2);
  @endphp

  <nav class="pager" aria-label="Səhifələmə">
    <span class="pager__info">
      {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }}
    </span>

    @if ($paginator->onFirstPage())
      <span class="pg is-disabled">‹</span>
    @else
      <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Əvvəlki">‹</a>
    @endif

    @if ($from > 1)
      <a href="{{ $paginator->url(1) }}">1</a>
      @if ($from > 2) <span class="pg gap">…</span> @endif
    @endif

    @for ($page = $from; $page <= $to; $page++)
      @if ($page === $current)
        <span class="pg is-current" aria-current="page">{{ $page }}</span>
      @else
        <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
      @endif
    @endfor

    @if ($to < $last)
      @if ($to < $last - 1) <span class="pg gap">…</span> @endif
      <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    @if ($paginator->hasMorePages())
      <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Növbəti">›</a>
    @else
      <span class="pg is-disabled">›</span>
    @endif
  </nav>
@endif
