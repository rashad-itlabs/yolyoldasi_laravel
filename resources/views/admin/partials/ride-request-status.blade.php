@php
  $map = [
    'open'      => ['Açıq',        'badge--green'],
    'fulfilled' => ['Matçlanıb',   'badge--blue'],
    'expired'   => ['Vaxtı keçib', 'badge--grey'],
    'cancelled' => ['Ləğv edilib', 'badge--red'],
  ];
  [$label, $class] = $map[$status] ?? [$status, 'badge--grey'];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
