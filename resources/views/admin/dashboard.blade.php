@extends('admin.layout')

@section('title', 'İcmal')
@section('subtitle', now()->translatedFormat('d F Y, H:i'))

@section('content')

  <div class="stat-grid">
    <a class="stat" href="{{ route('admin.users.index') }}">
      <div class="stat__label">İstifadəçi</div>
      <div class="stat__value">{{ number_format($stats['users_total']) }}</div>
      <div class="stat__hint up">+{{ $stats['users_new_7d'] }} son 7 gündə</div>
    </a>

    <a class="stat" href="{{ route('admin.drivers.index', ['status' => 'pending']) }}">
      <div class="stat__label">Sürücü təsdiqi</div>
      <div class="stat__value">{{ number_format($stats['drivers_pending']) }}</div>
      <div class="stat__hint {{ $stats['drivers_pending'] ? 'warn' : '' }}">
        {{ $stats['drivers_pending'] ? 'gözləyir' : 'növbə boşdur' }} · {{ $stats['drivers_approved'] }} təsdiqli
      </div>
    </a>

    <a class="stat" href="{{ route('admin.rides.index', ['status' => 'active']) }}">
      <div class="stat__label">Aktiv səfər</div>
      <div class="stat__value">{{ number_format($stats['rides_active']) }}</div>
      <div class="stat__hint">ümumi {{ number_format($stats['rides_total']) }}</div>
    </a>

    <a class="stat" href="{{ route('admin.bookings.index', ['status' => 'pending']) }}">
      <div class="stat__label">Rezervasiya</div>
      <div class="stat__value">{{ number_format($stats['bookings_total']) }}</div>
      <div class="stat__hint {{ $stats['bookings_pending'] ? 'warn' : '' }}">
        {{ $stats['bookings_pending'] }} cavab gözləyir
      </div>
    </a>

    <a class="stat" href="{{ route('admin.reports.index') }}">
      <div class="stat__label">Açıq şikayət</div>
      <div class="stat__value">{{ number_format($stats['reports_open']) }}</div>
      <div class="stat__hint {{ $stats['reports_open'] ? 'bad' : '' }}">
        {{ $stats['reports_open'] ? 'baxılmalıdır' : 'hamısı bağlıdır' }}
      </div>
    </a>

    <a class="stat" href="{{ route('admin.users.index', ['status' => 'blocked']) }}">
      <div class="stat__label">Bloklu hesab</div>
      <div class="stat__value">{{ number_format($stats['users_blocked']) }}</div>
      <div class="stat__hint">{{ $stats['users_total'] ? round($stats['users_blocked'] / $stats['users_total'] * 100, 1) : 0 }}% bütün hesablardan</div>
    </a>
  </div>

  <div class="card">
    <div class="card__head">
      <h2>Son 14 gün</h2>
      <div class="chart-legend">
        <span><i style="background:#05d9a5"></i>Qeydiyyat</span>
        <span><i style="background:#2563eb"></i>Səfər</span>
      </div>
    </div>
    <div class="card__body">
      @include('admin.partials.chart', ['chart' => $chart])
    </div>
  </div>

  <div class="split split--even">

    <div class="card">
      <div class="card__head">
        <h2>Təsdiq gözləyən sürücülər</h2>
        <a href="{{ route('admin.drivers.index') }}" class="btn btn--ghost btn--sm">Hamısı</a>
      </div>
      <div class="card__body card__body--flush">
        @if ($pendingDrivers->isEmpty())
          <div class="empty"><strong>Növbə boşdur</strong>Təsdiq gözləyən sürücü yoxdur.</div>
        @else
          <div class="table-wrap">
            <table class="tbl">
              <tbody>
                @foreach ($pendingDrivers as $profile)
                  <tr>
                    <td>@include('admin.partials.user', ['user' => $profile->user])</td>
                    <td class="nowrap muted small">
                      {{ $profile->submitted_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="actions">
                      <a href="{{ route('admin.drivers.show', $profile) }}" class="btn btn--ghost btn--sm">Bax</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    <div class="card">
      <div class="card__head">
        <h2>Açıq şikayətlər</h2>
        <a href="{{ route('admin.reports.index') }}" class="btn btn--ghost btn--sm">Hamısı</a>
      </div>
      <div class="card__body card__body--flush">
        @if ($recentReports->isEmpty())
          <div class="empty"><strong>Təmizdir</strong>Açıq şikayət yoxdur.</div>
        @else
          <div class="table-wrap">
            <table class="tbl">
              <tbody>
                @foreach ($recentReports as $report)
                  <tr>
                    <td>@include('admin.partials.user', ['user' => $report->targetUser])</td>
                    <td>
                      <span class="badge badge--amber">
                        {{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason }}
                      </span>
                    </td>
                    <td class="actions">
                      <a href="{{ route('admin.reports.show', $report) }}" class="btn btn--ghost btn--sm">Bax</a>
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

  <div class="card">
    <div class="card__head">
      <h2>Son admin əməliyyatları</h2>
      <a href="{{ route('admin.logs.index') }}" class="btn btn--ghost btn--sm">Jurnal</a>
    </div>
    <div class="card__body card__body--flush">
      @if ($recentActivity->isEmpty())
        <div class="empty"><strong>Hələ qeyd yoxdur</strong>Panel əməliyyatları burada görünəcək.</div>
      @else
        <div class="table-wrap">
          <table class="tbl">
            <tbody>
              @foreach ($recentActivity as $log)
                <tr>
                  <td class="nowrap"><strong>{{ $log->admin?->name ?? 'Silinmiş admin' }}</strong></td>
                  <td><span class="mono">{{ $log->action }}</span></td>
                  <td class="muted small">
                    @if ($log->subjectLabel()) {{ $log->subjectLabel() }} #{{ $log->subject_id }} @endif
                  </td>
                  <td class="nowrap muted small tar">{{ $log->created_at?->diffForHumans() }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

@endsection
