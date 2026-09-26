@extends('admin.layout')

@section('title', 'İstifadəçilər')
@section('subtitle', $users->total().' hesab')

@section('actions')
  <a href="{{ route('admin.users.create') }}" class="btn btn--primary btn--sm">Yeni istifadəçi</a>
@endsection

@section('content')

  <div class="card">
    <form method="GET" class="filters">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Ad, telefon və ya ID">
      </div>

      <div class="field">
        <label for="status">Vəziyyət</label>
        <select class="select" id="status" name="status">
          <option value="">Hamısı</option>
          <option value="active"  @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
          <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Bloklu</option>
          <option value="driver"  @selected(($filters['status'] ?? '') === 'driver')>Sürücü</option>
          <option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Silinmiş</option>
        </select>
      </div>

      <div class="field">
        <label for="mode">Rejim</label>
        <select class="select" id="mode" name="mode">
          <option value="">Hamısı</option>
          <option value="passenger" @selected(($filters['mode'] ?? '') === 'passenger')>Sərnişin</option>
          <option value="driver"    @selected(($filters['mode'] ?? '') === 'driver')>Sürücü</option>
        </select>
      </div>

      <div class="field">
        <label for="city_id">Şəhər</label>
        <select class="select" id="city_id" name="city_id">
          <option value="">Hamısı</option>
          @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) ($filters['city_id'] ?? '') === (string) $city->id)>
              {{ $city->name }}
            </option>
          @endforeach
        </select>
      </div>

      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.users.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>İstifadəçi</th>
            <th>Şəhər</th>
            <th>Rejim</th>
            <th class="num">Səfər</th>
            <th class="num">Rezerv.</th>
            <th>Qeydiyyat</th>
            <th>Vəziyyət</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td>@include('admin.partials.user', ['user' => $user])</td>
              <td class="muted">{{ $user->city?->name ?? '—' }}</td>
              <td>
                <span class="badge {{ $user->active_mode === 'driver' ? 'badge--blue' : 'badge--grey' }}">
                  {{ $user->active_mode === 'driver' ? 'Sürücü' : 'Sərnişin' }}
                </span>
              </td>
              <td class="num">{{ $user->rides_count }}</td>
              <td class="num">{{ $user->bookings_count }}</td>
              <td class="nowrap muted small">{{ $user->created_at?->format('d.m.Y') }}</td>
              <td>
                @if ($user->trashed())
                  <span class="badge badge--grey">Silinib</span>
                @elseif ($user->is_blocked)
                  <span class="badge badge--red">Bloklu</span>
                @elseif (! $user->phone_verified_at)
                  <span class="badge badge--amber">Təsdiqsiz</span>
                @else
                  <span class="badge badge--green">Aktiv</span>
                @endif
              </td>
              <td class="actions">
                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn--ghost btn--sm">Bax</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $users])
  </div>

@endsection
