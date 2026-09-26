@extends('admin.layout')

@section('title', 'Şikayət #'.$report->id)
@section('subtitle', \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason)

@section('actions')
  <a href="{{ route('admin.reports.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>

  @if ($report->status !== 'open')
    <form method="POST" action="{{ route('admin.reports.reopen', $report) }}" class="inline">
      @csrf
      <button class="btn btn--ghost btn--sm" type="submit">Yenidən aç</button>
    </form>
  @endif
@endsection

@section('content')

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head">
          <h2>Şikayət</h2>
          @php $map = ['open' => 'badge--red', 'resolved' => 'badge--green', 'dismissed' => 'badge--grey']; @endphp
          <span class="badge {{ $map[$report->status] ?? 'badge--grey' }}">
            {{ \App\Http\Controllers\Admin\ReportController::STATUS_LABELS[$report->status] ?? $report->status }}
          </span>
        </div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Səbəb</dt>
              <dd><span class="badge badge--amber">
                {{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason }}
              </span></dd>
            </div>
            <div class="row"><dt>Təfərrüat</dt><dd>{{ $report->details ?: '—' }}</dd></div>
            <div class="row"><dt>Tarix</dt><dd>{{ $report->created_at?->format('d.m.Y H:i') }}</dd></div>
            @if ($report->ride_id)
              <div class="row"><dt>Səfər</dt><dd><a href="{{ route('admin.rides.show', $report->ride_id) }}">#{{ $report->ride_id }}</a></dd></div>
            @endif
            @if ($report->booking_id)
              <div class="row"><dt>Rezervasiya</dt><dd><a href="{{ route('admin.bookings.show', $report->booking_id) }}">#{{ $report->booking_id }}</a></dd></div>
            @endif
            @if ($report->status !== 'open')
              <div class="row"><dt>Qərar tarixi</dt><dd>{{ $report->resolved_at?->format('d.m.Y H:i') }}</dd></div>
              <div class="row"><dt>Admin qeydi</dt><dd>{{ $report->admin_note ?: '—' }}</dd></div>
            @endif
          </dl>
        </div>
      </div>

      @if ($report->status === 'open')
        <div class="card">
          <div class="card__head"><h2>Qərar</h2></div>
          <div class="card__body">
            <form method="POST" id="decisionForm">
              @csrf
              <div class="field">
                <label for="admin_note">Qeyd (könüllü)</label>
                <textarea class="textarea" id="admin_note" name="admin_note" maxlength="1000"
                          placeholder="Qərarın səbəbi — jurnalda saxlanılır">{{ old('admin_note') }}</textarea>
              </div>
              <div class="btn-row">
                <button class="btn btn--primary" type="submit"
                        formaction="{{ route('admin.reports.resolve', $report) }}">Həll edildi</button>
                <button class="btn btn--ghost" type="submit"
                        formaction="{{ route('admin.reports.dismiss', $report) }}">Əsassızdır</button>
              </div>
            </form>
          </div>
        </div>
      @endif

      @if ($otherReports->isNotEmpty())
        <div class="card">
          <div class="card__head">
            <h2>Eyni şəxs haqqında digər şikayətlər</h2>
            <span class="badge badge--amber">{{ $otherReports->count() }}</span>
          </div>
          <div class="card__body card__body--flush">
            <div class="table-wrap">
              <table class="tbl">
                <thead><tr><th>Şikayətçi</th><th>Səbəb</th><th>Status</th><th>Tarix</th><th></th></tr></thead>
                <tbody>
                  @foreach ($otherReports as $other)
                    <tr>
                      <td>@include('admin.partials.user', ['user' => $other->reporter])</td>
                      <td class="small">{{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$other->reason] ?? $other->reason }}</td>
                      <td>
                        <span class="badge {{ $map[$other->status] ?? 'badge--grey' }}">
                          {{ \App\Http\Controllers\Admin\ReportController::STATUS_LABELS[$other->status] ?? $other->status }}
                        </span>
                      </td>
                      <td class="nowrap muted small">{{ $other->created_at?->format('d.m.Y') }}</td>
                      <td class="actions"><a href="{{ route('admin.reports.show', $other) }}" class="btn btn--ghost btn--sm">Bax</a></td>
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
        <div class="card__head">
          <h2>Şikayət edilən</h2>
          @if ($report->targetUser && ! $report->targetUser->is_blocked)
            <button class="btn btn--warn btn--sm" data-modal="#blockTarget" type="button">Blokla</button>
          @endif
        </div>
        <div class="card__body">
          @include('admin.partials.user', ['user' => $report->targetUser])

          @if ($report->targetUser)
            <dl class="rows" style="margin-top:14px">
              <div class="row"><dt>Sürücü reytinqi</dt><dd>
                @include('admin.partials.stars', ['rating' => round($report->targetUser->stats?->driver_rating ?? 0)])
                <span class="muted small">{{ $report->targetUser->stats?->driver_review_count ?? 0 }} rəy</span>
              </dd></div>
              <div class="row"><dt>Sərnişin reytinqi</dt><dd>
                @include('admin.partials.stars', ['rating' => round($report->targetUser->stats?->passenger_rating ?? 0)])
                <span class="muted small">{{ $report->targetUser->stats?->passenger_review_count ?? 0 }} rəy</span>
              </dd></div>
              <div class="row"><dt>Vəziyyət</dt><dd>
                @if ($report->targetUser->is_blocked)
                  <span class="badge badge--red">Bloklu</span>
                  <div class="small muted">{{ $report->targetUser->block_reason }}</div>
                @else
                  <span class="badge badge--green">Aktiv</span>
                @endif
              </dd></div>
            </dl>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Şikayətçi</h2></div>
        <div class="card__body">@include('admin.partials.user', ['user' => $report->reporter])</div>
      </div>
    </div>

  </div>

  @if ($report->targetUser && ! $report->targetUser->is_blocked)
    <dialog class="modal" id="blockTarget">
      <form method="POST" action="{{ route('admin.users.block', $report->targetUser) }}">
        @csrf
        <div class="card__head"><h2>Hesabı blokla</h2></div>
        <div class="modal__body">
          <p class="muted small mt-0">
            {{ $report->targetUser->full_name ?: 'Adsız istifadəçi' }} tətbiqə girə bilməyəcək.
          </p>
          <div class="field">
            <label for="targetReason">Səbəb</label>
            <textarea class="textarea" id="targetReason" name="block_reason" required maxlength="255"
                      >Şikayət #{{ $report->id }} — {{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason }}</textarea>
          </div>
        </div>
        <div class="modal__foot">
          <button class="btn btn--ghost" type="button" data-close>İmtina</button>
          <button class="btn btn--danger" type="submit">Blokla</button>
        </div>
      </form>
    </dialog>
  @endif

@endsection
