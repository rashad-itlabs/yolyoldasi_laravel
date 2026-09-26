@extends('admin.layout')

@section('title', 'Səfər #'.$ride->id)
@section('subtitle', $ride->fromCity?->name.' → '.$ride->toCity?->name.' · '.$ride->departure_at?->format('d.m.Y H:i'))

@section('actions')
  <a href="{{ route('admin.rides.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>

  @if ($ride->status === 'active')
    <form method="POST" action="{{ route('admin.rides.deactivate', $ride) }}" class="inline">
      @csrf
      <button class="btn btn--ghost btn--sm" type="submit">Passivləşdir</button>
    </form>
  @elseif ($ride->status === 'inactive')
    <form method="POST" action="{{ route('admin.rides.activate', $ride) }}" class="inline">
      @csrf
      <button class="btn btn--primary btn--sm" type="submit">Aktivləşdir</button>
    </form>
  @endif

  @unless (in_array($ride->status, ['cancelled', 'completed']))
    <button class="btn btn--danger btn--sm" data-modal="#cancelRide" type="button">Səfəri ləğv et</button>
  @endunless

  @include('admin.partials.ride-delete', ['ride' => $ride])
@endsection

@section('content')

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head">
          <h2>Rezervasiyalar</h2>
          <span class="muted">{{ $bookings->count() }} müraciət</span>
        </div>
        <div class="card__body card__body--flush">
          @if ($bookings->isEmpty())
            <div class="empty"><strong>Rezervasiya yoxdur</strong>Bu səfərə hələ müraciət edilməyib.</div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <thead>
                  <tr><th>Sərnişin</th><th class="num">Yer</th><th class="num">Məbləğ</th><th>Status</th><th>Tarix</th><th></th></tr>
                </thead>
                <tbody>
                  @foreach ($bookings as $booking)
                    <tr>
                      <td>@include('admin.partials.user', ['user' => $booking->passenger])</td>
                      <td class="num">{{ $booking->seats }}</td>
                      <td class="num nowrap">{{ number_format((float) $booking->total_price, 2) }} ₼</td>
                      <td>@include('admin.partials.booking-status', ['status' => $booking->status])</td>
                      <td class="nowrap muted small">{{ $booking->created_at?->format('d.m.Y H:i') }}</td>
                      <td class="actions">
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--ghost btn--sm">Bax</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card__head">
          <h2>Səfər</h2>
          @include('admin.partials.ride-status', ['status' => $ride->status])
        </div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Marşrut</dt><dd><strong>{{ $ride->fromCity?->name }} → {{ $ride->toCity?->name }}</strong></dd></div>
            <div class="row"><dt>Yola düşmə</dt><dd>{{ $ride->departure_at?->format('d.m.Y H:i') }}</dd></div>
            <div class="row"><dt>Yer</dt><dd>{{ $ride->booked_seats }} / {{ $ride->total_seats }} dolu</dd></div>
            <div class="row"><dt>Qiymət</dt><dd>{{ number_format((float) $ride->price_per_seat, 2) }} ₼ / yer</dd></div>
            <div class="row"><dt>Ani rezervasiya</dt><dd>{{ $ride->instant_booking ? 'Açıq' : 'Bağlı' }}</dd></div>
            <div class="row"><dt>Götürmə</dt><dd>{{ $ride->pickup_point ?: '—' }}</dd></div>
            <div class="row"><dt>Endirmə</dt><dd>{{ $ride->dropoff_point ?: '—' }}</dd></div>
            <div class="row"><dt>Qeyd</dt><dd>{{ $ride->note ?: '—' }}</dd></div>
            <div class="row"><dt>Yaradılıb</dt><dd>{{ $ride->created_at?->format('d.m.Y H:i') }}</dd></div>
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Sürücü</h2></div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Ad</dt><dd>@include('admin.partials.user', ['user' => $ride->driver])</dd></div>
            <div class="row"><dt>Reytinq</dt><dd>
              @include('admin.partials.stars', ['rating' => round($ride->driver?->stats?->driver_rating ?? 0)])
              <span class="muted small">{{ $ride->driver?->stats?->driver_review_count ?? 0 }} rəy</span>
            </dd></div>
            <div class="row"><dt>Avtomobil</dt><dd>
              @if ($ride->vehicle)
                {{ $ride->vehicle->brand }} {{ $ride->vehicle->model }}
                <span class="muted small">{{ $ride->vehicle->plate }}</span>
              @else — @endif
            </dd></div>
          </dl>
        </div>
      </div>
    </div>

  </div>

  <dialog class="modal" id="cancelRide">
    <form method="POST" action="{{ route('admin.rides.cancel', $ride) }}">
      @csrf
      <div class="card__head"><h2>Səfəri ləğv et</h2></div>
      <div class="modal__body">
        <p class="muted small mt-0">
          Açıq və təsdiqlənmiş rezervasiyalar da bağlanacaq, sərnişinlərə bildiriş gedəcək.
        </p>
        <div class="field">
          <label for="cancelReason">Səbəb</label>
          <textarea class="textarea" id="cancelReason" name="reason" required maxlength="255"
                    placeholder="Məsələn: saxta elan"></textarea>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--ghost" type="button" data-close>İmtina</button>
        <button class="btn btn--danger" type="submit">Ləğv et</button>
      </div>
    </form>
  </dialog>

@endsection
