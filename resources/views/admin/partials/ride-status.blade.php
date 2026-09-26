@php
  $map = [
    'active'    => ['Aktiv',        'badge--green'],
    'inactive'  => ['Passiv',       'badge--grey'],
    'completed' => ['Tamamlanıb',   'badge--blue'],
    'cancelled' => ['Ləğv edilib',  'badge--red'],
  ];
  [$label, $class] = $map[$status] ?? [$status, 'badge--grey'];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
