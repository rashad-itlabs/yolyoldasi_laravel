@extends('admin.layout')

@section('title', 'Adminlər')
@section('subtitle', $admins->total().' hesab')

@section('actions')
  <a href="{{ route('admin.admins.create') }}" class="btn btn--primary btn--sm">Yeni admin</a>
@endsection

@section('content')

  <div class="card">
    <form method="GET" class="filters">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Ad və ya e-poçt">
      </div>
      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.admins.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Admin</th>
            <th>Rol</th>
            <th class="num">Əməliyyat</th>
            <th>Son giriş</th>
            <th>Vəziyyət</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($admins as $admin)
            <tr>
              <td>
                <div class="u">
                  <span class="u__av">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                  <span class="u__t">
                    <strong>{{ $admin->name }}</strong>
                    <span>{{ $admin->email }}</span>
                  </span>
                  @if ($admin->id === auth('admin')->id())
                    <span class="badge badge--mint">Siz</span>
                  @endif
                </div>
              </td>
              <td>
                <span class="badge {{ $admin->isSuperAdmin() ? 'badge--mint' : 'badge--grey' }}">
                  {{ $admin->roleLabel() }}
                </span>
              </td>
              <td class="num">{{ $admin->activity_logs_count }}</td>
              <td class="nowrap muted small">
                {{ $admin->last_login_at?->format('d.m.Y H:i') ?? 'heç vaxt' }}
                @if ($admin->last_login_ip)
                  <div class="mono">{{ $admin->last_login_ip }}</div>
                @endif
              </td>
              <td>
                <span class="badge {{ $admin->is_active ? 'badge--green' : 'badge--grey' }}">
                  {{ $admin->is_active ? 'Aktiv' : 'Deaktiv' }}
                </span>
              </td>
              <td class="actions">
                <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn--ghost btn--sm">Redaktə</a>

                @if ($admin->id !== auth('admin')->id())
                  <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" class="inline"
                        data-confirm="«{{ $admin->name }}» silinsin?">
                    @csrf @method('DELETE')
                    <button class="btn btn--danger btn--sm" type="submit">Sil</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty"><strong>Nəticə yoxdur</strong>Axtarışa uyğun admin tapılmadı.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $admins])
  </div>

@endsection
