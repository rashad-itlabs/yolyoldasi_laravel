@extends('admin.layout')

@section('title', 'Şikayətlər')
@section('subtitle', ($counts['open'] ?? 0).' açıq')

@section('content')

  <div class="card">
    <div class="tabs">
      @foreach (['open' => 'Açıq', 'resolved' => 'Həll edilib', 'dismissed' => 'Rədd edilib', 'all' => 'Hamısı'] as $key => $label)
        <a class="tab {{ $status === $key ? 'is-active' : '' }}"
           href="{{ route('admin.reports.index', array_filter(['status' => $key, 'q' => $filters['q'] ?? null, 'reason' => $filters['reason'] ?? null])) }}">
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
               placeholder="Şikayət ID, ad və ya telefon">
      </div>

      <div class="field">
        <label for="reason">Səbəb</label>
        <select class="select" id="reason" name="reason">
          <option value="">Hamısı</option>
          @foreach (\App\Http\Controllers\Admin\ReportController::REASON_LABELS as $key => $label)
            <option value="{{ $key }}" @selected(($filters['reason'] ?? '') === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.reports.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th class="num">#</th>
            <th>Şikayət edilən</th>
            <th>Şikayətçi</th>
            <th>Səbəb</th>
            <th>Tarix</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($reports as $report)
            <tr>
              <td class="num muted">{{ $report->id }}</td>
              <td>@include('admin.partials.user', ['user' => $report->targetUser])</td>
              <td>@include('admin.partials.user', ['user' => $report->reporter])</td>
              <td>
                <span class="badge badge--amber">
                  {{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason }}
                </span>
              </td>
              <td class="nowrap muted small">{{ $report->created_at?->format('d.m.Y H:i') }}</td>
              <td>
                @php
                  $map = ['open' => 'badge--red', 'resolved' => 'badge--green', 'dismissed' => 'badge--grey'];
                @endphp
                <span class="badge {{ $map[$report->status] ?? 'badge--grey' }}">
                  {{ \App\Http\Controllers\Admin\ReportController::STATUS_LABELS[$report->status] ?? $report->status }}
                </span>
              </td>
              <td class="actions">
                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn--ghost btn--sm">Bax</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7"><div class="empty"><strong>Nəticə yoxdur</strong>Bu vəziyyətdə şikayət yoxdur.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $reports])
  </div>

@endsection
