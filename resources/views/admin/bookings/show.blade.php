@extends('admin.layout')

@section('title', 'Rezervasiya #'.$booking->id)
@section('subtitle', $booking->ride?->fromCity?->name.' → '.$booking->ride?->toCity?->name)

@section('actions')
  <a href="{{ route('admin.bookings.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>
  @if ($booking->ride)
    <a href="{{ route('admin.rides.show', $booking->ride) }}" class="btn btn--ghost btn--sm">Səfərə bax</a>
  @endif
@endsection

@section('content')

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head">
          <h2>Rezervasiya</h2>
          @include('admin.partials.booking-status', ['status' => $booking->status])
        </div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Yer sayı</dt><dd>{{ $booking->seats }}</dd></div>
            <div class="row"><dt>Məbləğ</dt><dd>{{ number_format((float) $booking->total_price, 2) }} ₼</dd></div>
            <div class="row"><dt>Sərnişin mesajı</dt><dd>{{ $booking->message ?: '—' }}</dd></div>
            <div class="row"><dt>Yaradılıb</dt><dd>{{ $booking->created_at?->format('d.m.Y H:i') }}</dd></div>
            <div class="row"><dt>Qərar verilib</dt><dd>{{ $booking->decided_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            @if ($booking->cancelled_at)
              <div class="row"><dt>Ləğv edilib</dt><dd>{{ $booking->cancelled_at->format('d.m.Y H:i') }}</dd></div>
              <div class="row"><dt>Ləğv səbəbi</dt><dd>{{ $booking->cancellation_reason ?: '—' }}</dd></div>
            @endif
            <div class="row"><dt>Rəy yazılıb</dt>
              <dd>
                Sərnişin: {{ $booking->passenger_reviewed ? 'bəli' : 'xeyr' }} ·
                Sürücü: {{ $booking->driver_reviewed ? 'bəli' : 'xeyr' }}
              </dd>
            </div>
            <div class="row"><dt>Söhbət</dt>
              <dd>
                @if ($booking->conversation)
                  <span class="badge {{ $booking->conversation->is_locked ? 'badge--grey' : 'badge--green' }}">
                    {{ $booking->conversation->is_locked ? 'Bağlı' : 'Açıq' }}
                  </span>
                @else
                  <span class="muted">—</span>
                @endif
              </dd>
            </div>
          </dl>
        </div>
      </div>

      @if ($booking->reviews->isNotEmpty())
        <div class="card">
          <div class="card__head"><h2>Bu səfərə yazılan rəylər</h2></div>
          <div class="card__body card__body--flush">
            <div class="table-wrap">
              <table class="tbl">
                <thead><tr><th>Müəllif</th><th>Qiymət</th><th>Şərh</th><th>Tarix</th></tr></thead>
                <tbody>
                  @foreach ($booking->reviews as $review)
                    <tr>
                      <td>@include('admin.partials.user', ['user' => $review->author])</td>
                      <td>@include('admin.partials.stars', ['rating' => $review->rating])</td>
                      <td class="small">{{ $review->comment ?: '—' }}</td>
                      <td class="nowrap muted small">{{ $review->created_at?->format('d.m.Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      @endif
    </div>

    <div>
      <div class="card">
        <div class="card__head"><h2>Sərnişin</h2></div>
        <div class="card__body">@include('admin.partials.user', ['user' => $booking->passenger])</div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Sürücü</h2></div>
        <div class="card__body">@include('admin.partials.user', ['user' => $booking->driver])</div>
      </div>

      @if ($booking->ride)
        <div class="card">
          <div class="card__head"><h2>Səfər</h2></div>
          <div class="card__body">
            <dl class="rows">
              <div class="row"><dt>Marşrut</dt>
                <dd><a href="{{ route('admin.rides.show', $booking->ride) }}">
                  {{ $booking->ride->fromCity?->name }} → {{ $booking->ride->toCity?->name }}
                </a></dd>
              </div>
              <div class="row"><dt>Yola düşmə</dt><dd>{{ $booking->ride->departure_at?->format('d.m.Y H:i') }}</dd></div>
              <div class="row"><dt>Status</dt><dd>@include('admin.partials.ride-status', ['status' => $booking->ride->status])</dd></div>
              <div class="row"><dt>Avtomobil</dt>
                <dd>
                  @if ($booking->ride->vehicle)
                    {{ $booking->ride->vehicle->brand }} {{ $booking->ride->vehicle->model }}
                  @else — @endif
                </dd>
              </div>
            </dl>
          </div>
        </div>
      @endif
    </div>

  </div>

@endsection
