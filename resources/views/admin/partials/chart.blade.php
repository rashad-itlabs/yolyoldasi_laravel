{{-- Sadə SVG qrafik — panel üçün ayrıca kitabxana çəkməyə dəyməz. --}}
@php
  $w = 720; $h = 190;
  $padL = 34; $padR = 10; $padT = 12; $padB = 26;

  $n    = max(1, count($chart['labels']));
  $max  = max(1, max(max($chart['users'] ?: [0]), max($chart['rides'] ?: [0])));
  $stepX = $n > 1 ? ($w - $padL - $padR) / ($n - 1) : 0;

  $x = fn ($i) => $padL + $i * $stepX;
  $y = fn ($v) => $padT + ($h - $padT - $padB) * (1 - $v / $max);

  $line = function (array $series) use ($x, $y) {
      return collect($series)->map(fn ($v, $i) => round($x($i), 1).','.round($y($v), 1))->implode(' ');
  };

  // Yuvarlaq şkala: 0, orta, maksimum.
  $ticks = [0, (int) ceil($max / 2), $max];
@endphp

<svg class="chart" viewBox="0 0 {{ $w }} {{ $h }}" role="img"
     aria-label="Son {{ $n }} günün qeydiyyat və səfər sayı">

  @foreach ($ticks as $tick)
    @php $ty = $y($tick); @endphp
    <line x1="{{ $padL }}" y1="{{ round($ty, 1) }}" x2="{{ $w - $padR }}" y2="{{ round($ty, 1) }}"
          stroke="#e2e8f0" stroke-width="1"/>
    <text x="{{ $padL - 7 }}" y="{{ round($ty + 3.5, 1) }}" text-anchor="end"
          font-size="10" fill="#94a3b8" font-weight="600">{{ $tick }}</text>
  @endforeach

  @if ($n > 1)
    <polyline points="{{ $line($chart['users']) }}" fill="none" stroke="#05d9a5"
              stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>
    <polyline points="{{ $line($chart['rides']) }}" fill="none" stroke="#2563eb"
              stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"/>
  @endif

  @foreach ($chart['labels'] as $i => $label)
    @if ($n <= 16 || $i % 2 === 0)
      <text x="{{ round($x($i), 1) }}" y="{{ $h - 8 }}" text-anchor="middle"
            font-size="10" fill="#94a3b8" font-weight="600">{{ $label }}</text>
    @endif
  @endforeach

  @foreach ($chart['users'] as $i => $v)
    <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($v), 1) }}" r="2.6" fill="#05d9a5"/>
  @endforeach

  @foreach ($chart['rides'] as $i => $v)
    <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($v), 1) }}" r="2.6" fill="#2563eb"/>
  @endforeach
</svg>
