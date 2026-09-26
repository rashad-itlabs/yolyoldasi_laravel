@php $rating = (int) round($rating ?? 0); @endphp
<span class="stars" title="{{ $rating }}/5">
  @for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $rating ? '' : 'off' }}">★</span>@endfor
</span>
