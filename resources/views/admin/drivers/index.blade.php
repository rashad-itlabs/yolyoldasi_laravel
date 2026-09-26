@extends('admin.layout')

@section('title', 'Sürücü təsdiqi')
@section('subtitle', ($counts['pending'] ?? 0).' profil növbədə')

@section('content')

  <div class="card">
    <div class="tabs">
      @foreach ([
        'pending'      => 'Gözləyir',
        'approved'     => 'Təsdiqli',
        'rejected'     => 'Rədd edilib',
        'not_uploaded' => 'Yüklənməyib',
        'all'          => 'Hamısı',
      ] as $key => $label)
        <a class="tab {{ $status === $key ? 'is-active' : '' }}"
           href="{{ route('admin.drivers.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null])) }}">
          {{ $label }}
          <span class="n">{{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" class="filters">
      <input type="hidden" name="status" value="{{ $status }}">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Sürücünün adı və ya telefonu">
      </div>
      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.drivers.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Sürücü</th>
            <th>Avtomobil</th>
            <th class="num">Sənəd</th>
            <th>Göndərilib</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($profiles as $profile)
            <tr>
              <td>@include('admin.partials.user', ['user' => $profile->user])</td>
              <td>
                @if ($profile->vehicle)
                  {{ $profile->vehicle->brand }} {{ $profile->vehicle->model }}
                  <span class="muted small">{{ $profile->vehicle->plate }}</span>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td class="num">{{ $profile->documents_count }}/4</td>
              <td class="nowrap muted small">{{ $profile->submitted_at?->format('d.m.Y H:i') ?? '—' }}</td>
              <td>@include('admin.partials.driver-status', ['status' => $profile->status])</td>
              <td class="actions">
                <a href="{{ route('admin.drivers.show', $profile) }}" class="btn btn--primary btn--sm">Yoxla</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty"><strong>Nəticə yoxdur</strong>Bu vəziyyətdə profil yoxdur.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $profiles])
  </div>

@endsection
