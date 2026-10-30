{{-- İstifadəçi hücrəsi. $user null ola bilər (silinmiş sətirlər üçün). --}}
@php
  $link = $link ?? true;
  // Sayt hesabının telefonu yoxdur — onda e-poçt göstərilir.
  $sub  = $sub ?? ($user?->phone ?? $user?->email);
@endphp

@if (! $user)
  <span class="muted">—</span>
@else
  <div class="u">
    <span class="u__av">
      @if ($user->photo_url)
        <img src="{{ $user->photo_url }}" alt="">
      @else
        {{ mb_strtoupper(mb_substr(trim($user->full_name) ?: '?', 0, 1)) }}
      @endif
    </span>
    <span class="u__t">
      @if ($link)
        <strong><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->full_name ?: 'Adsız' }}</a></strong>
      @else
        <strong>{{ $user->full_name ?: 'Adsız' }}</strong>
      @endif
      <span>{{ $sub }}</span>
    </span>
    @if ($user->is_blocked)
      <span class="badge badge--red">Bloklu</span>
    @endif
  </div>
@endif
