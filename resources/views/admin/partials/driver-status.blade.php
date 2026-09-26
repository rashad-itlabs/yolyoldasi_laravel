@php
  $map = [
    'not_uploaded' => ['Yüklənməyib', 'badge--grey'],
    'pending'      => ['Gözləyir',    'badge--amber'],
    'approved'     => ['Təsdiqli',    'badge--green'],
    'rejected'     => ['Rədd edilib', 'badge--red'],
  ];
  [$label, $class] = $map[$status] ?? [$status, 'badge--grey'];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
