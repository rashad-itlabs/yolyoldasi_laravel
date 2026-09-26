@php
  $map = [
    'pending'                => ['Gözləyir',            'badge--amber'],
    'confirmed'              => ['Təsdiqlənib',         'badge--green'],
    'rejected'               => ['Rədd edilib',         'badge--red'],
    'cancelled_by_passenger' => ['Sərnişin ləğv edib',  'badge--grey'],
    'cancelled_by_driver'    => ['Sürücü ləğv edib',    'badge--grey'],
    'completed'              => ['Tamamlanıb',          'badge--blue'],
  ];
  [$label, $class] = $map[$status] ?? [$status, 'badge--grey'];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
