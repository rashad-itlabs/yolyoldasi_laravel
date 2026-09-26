@extends('admin.layout')

@section('title', 'Fəaliyyət jurnalı')
@section('subtitle', $logs->total().' qeyd')

@section('content')

  <div class="card">
    <form method="GET" class="filters">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Əməliyyat, obyekt ID və ya IP">
      </div>

      <div class="field">
        <label for="admin_id">Admin</label>
        <select class="select" id="admin_id" name="admin_id">
          <option value="">Hamısı</option>
          @foreach ($admins as $admin)
            <option value="{{ $admin->id }}" @selected((string) ($filters['admin_id'] ?? '') === (string) $admin->id)>
              {{ $admin->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="action">Əməliyyat</label>
        <select class="select" id="action" name="action">
          <option value="">Hamısı</option>
          @foreach ($actions as $action)
            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
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
      <a class="btn btn--ghost" href="{{ route('admin.logs.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Vaxt</th>
            <th>Admin</th>
            <th>Əməliyyat</th>
            <th>Obyekt</th>
            <th>Təfərrüat</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td class="nowrap small">
                {{ $log->created_at?->format('d.m.Y H:i:s') }}
                <div class="muted">{{ $log->created_at?->diffForHumans() }}</div>
              </td>
              <td class="nowrap">
                <strong>{{ $log->admin?->name ?? 'Silinmiş admin' }}</strong>
              </td>
              <td><span class="mono">{{ $log->action }}</span></td>
              <td class="nowrap small">
                @if ($log->subjectLabel())
                  {{ $log->subjectLabel() }} <span class="muted">#{{ $log->subject_id }}</span>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td>
                @if ($log->meta)
                  <pre class="pre">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td class="mono small muted nowrap">{{ $log->ip ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty"><strong>Qeyd yoxdur</strong>Panel əməliyyatları burada görünəcək.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $logs])
  </div>

@endsection
