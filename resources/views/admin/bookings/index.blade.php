@extends('admin.layout')

@section('title', 'Rezervasiyalar')
@section('subtitle', $bookings->total().' müraciət')

@section('content')

  <div class="card">
    <div class="tabs">
      @foreach (['' => 'Hamısı'] + \App\Http\Controllers\Admin\BookingController::STATUS_LABELS as $key => $label)
        <a class="tab {{ ($filters['status'] ?? '') === $key ? 'is-active' : '' }}"
           href="{{ route('admin.bookings.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null])) }}">
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
               placeholder="Rezervasiya ID, səfər ID, ad və ya telefon">
      </div>
      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.bookings.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th class="num">#</th>
            <th>Sərnişin</th>
            <th>Sürücü</th>
            <th>Marşrut</th>
            <th class="num">Yer</th>
            <th class="num">Məbləğ</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($bookings as $booking)
            <tr>
              <td class="num muted">{{ $booking->id }}</td>
              <td>@include('admin.partials.user', ['user' => $booking->passenger])</td>
              <td>@include('admin.partials.user', ['user' => $booking->driver])</td>
              <td>
                <strong>{{ $booking->ride?->fromCity?->name }} → {{ $booking->ride?->toCity?->name }}</strong>
                <div class="muted small">{{ $booking->ride?->departureLocal()?->format('d.m.Y H:i') }}</div>
              </td>
              <td class="num">{{ $booking->seats }}</td>
              <td class="num nowrap">{{ number_format((float) $booking->total_price, 2) }} ₼</td>
              <td>@include('admin.partials.booking-status', ['status' => $booking->status])</td>
              <td class="actions"><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--ghost btn--sm">Bax</a></td>
            </tr>
          @empty
            <tr><td colspan="8"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $bookings])
  </div>

@endsection
