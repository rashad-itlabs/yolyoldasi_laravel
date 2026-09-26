@extends('admin.layout')

@section('title', 'Səhifələr')
@section('subtitle', 'Hüquqi və məlumat səhifələrinin mətni')

@section('content')

  <div class="card">
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr><th>Səhifə</th><th>Ünvan</th><th>Son yenilənmə</th><th>Ölçü</th><th>Vəziyyət</th><th></th></tr>
        </thead>
        <tbody>
          @forelse ($pages as $page)
            <tr>
              <td>
                <strong>{{ $page->title }}</strong>
                <div class="muted small">{{ $page->label() }}</div>
              </td>
              <td class="mono small">
                @if ($page->publicUrl())
                  <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener">{{ parse_url($page->publicUrl(), PHP_URL_PATH) }}</a>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td class="nowrap muted small">{{ $page->content_updated_at?->format('d.m.Y') ?? '—' }}</td>
              <td class="num muted small">{{ number_format(mb_strlen((string) $page->body)) }} simvol</td>
              <td>
                <span class="badge {{ $page->is_published ? 'badge--green' : 'badge--grey' }}">
                  {{ $page->is_published ? 'Yayımda' : 'Gizli' }}
                </span>
              </td>
              <td class="actions">
                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn--primary btn--sm">Redaktə</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty"><strong>Səhifə yoxdur</strong>ContentSeeder işə salınmayıb.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection
