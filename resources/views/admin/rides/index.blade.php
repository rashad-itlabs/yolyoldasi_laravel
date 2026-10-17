@extends('admin.layout')

@section('title', 'Səfərlər')
@section('subtitle', $rides->total().' elan')

@section('content')

  <div class="card">
    <div class="tabs">
      @foreach (['' => 'Hamısı', 'active' => 'Aktiv', 'inactive' => 'Passiv', 'completed' => 'Tamamlanıb', 'cancelled' => 'Ləğv edilib'] as $key => $label)
        <a class="tab {{ ($filters['status'] ?? '') === $key ? 'is-active' : '' }}"
           href="{{ route('admin.rides.index', array_filter(['status' => $key] + $filters)) }}">
          {{ $label }}
          <span class="n">{{ $key === '' ? $counts->sum() : ($counts[$key] ?? 0) }}</span>
        </a>
      @endforeach
    </div>

    <form method="GET" class="filters">
      <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">

      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Səfər ID, sürücü adı və ya telefonu">
      </div>

      <div class="field">
        <label for="from_city_id">Haradan</label>
        <select class="select" id="from_city_id" name="from_city_id">
          <option value="">Hamısı</option>
          @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) ($filters['from_city_id'] ?? '') === (string) $city->id)>{{ $city->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="to_city_id">Haraya</label>
        <select class="select" id="to_city_id" name="to_city_id">
          <option value="">Hamısı</option>
          @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) ($filters['to_city_id'] ?? '') === (string) $city->id)>{{ $city->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="date_from">Tarixdən</label>
        <input class="input" type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
      </div>

      <div class="field">
        <label for="date_to">Tarixə</label>
        <input class="input" type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
      </div>

      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.rides.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th class="num">#</th>
            <th>Marşrut</th>
            <th>Sürücü</th>
            <th>Yola düşmə</th>
            <th class="num">Yer</th>
            <th class="num">Qiymət</th>
            <th class="num">Rezerv.</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($rides as $ride)
            <tr>
              <td class="num muted">{{ $ride->id }}</td>
              <td><strong>{{ $ride->fromCity?->name }} → {{ $ride->toCity?->name }}</strong></td>
              <td>@include('admin.partials.user', ['user' => $ride->driver])</td>
              <td class="nowrap small">{{ $ride->departureLocal()?->format('d.m.Y H:i') }}</td>
              <td class="num">{{ $ride->booked_seats }}/{{ $ride->total_seats }}</td>
              <td class="num nowrap">{{ number_format((float) $ride->price_per_seat, 2) }} ₼</td>
              <td class="num">{{ $ride->bookings_count }}</td>
              <td>@include('admin.partials.ride-status', ['status' => $ride->status])</td>
              <td class="actions">
                <a href="{{ route('admin.rides.show', $ride) }}" class="btn btn--ghost btn--sm">Bax</a>
                @include('admin.partials.ride-delete', ['ride' => $ride])
              </td>
            </tr>
          @empty
            <tr><td colspan="9"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $rides])
  </div>

@endsection
