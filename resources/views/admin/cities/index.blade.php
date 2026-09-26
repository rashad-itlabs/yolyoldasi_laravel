@extends('admin.layout')

@section('title', 'Şəhərlər')
@section('subtitle', $cities->total().' şəhər')

@section('actions')
  <button class="btn btn--primary btn--sm" data-modal="#addCity" type="button">Yeni şəhər</button>
@endsection

@section('content')

  <div class="card">
    <form method="GET" class="filters">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Şəhər adı">
      </div>
      <div class="field">
        <label for="status">Vəziyyət</label>
        <select class="select" id="status" name="status">
          <option value="">Hamısı</option>
          <option value="active"   @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
          <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Deaktiv</option>
        </select>
      </div>
      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.cities.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr><th class="num">#</th><th>Ad</th><th class="num">Səfərdə işlənib</th><th>Vəziyyət</th><th></th></tr>
        </thead>
        <tbody>
          @forelse ($cities as $city)
            <tr>
              <td class="num muted">{{ $city->id }}</td>
              <td><strong>{{ $city->name }}</strong></td>
              <td class="num">{{ $usage[$city->id] ?? 0 }}</td>
              <td>
                <span class="badge {{ $city->is_active ? 'badge--green' : 'badge--grey' }}">
                  {{ $city->is_active ? 'Aktiv' : 'Deaktiv' }}
                </span>
              </td>
              <td class="actions">
                <button class="btn btn--ghost btn--sm" type="button" data-modal="#editCity{{ $city->id }}">Redaktə</button>

                <form method="POST" action="{{ route('admin.cities.toggle', $city) }}" class="inline">
                  @csrf
                  <button class="btn btn--ghost btn--sm" type="submit">
                    {{ $city->is_active ? 'Deaktiv et' : 'Aktiv et' }}
                  </button>
                </form>

                @if (($usage[$city->id] ?? 0) === 0)
                  <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" class="inline"
                        data-confirm="«{{ $city->name }}» silinsin?">
                    @csrf @method('DELETE')
                    <button class="btn btn--danger btn--sm" type="submit">Sil</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $cities])
  </div>

  @foreach ($cities as $city)
    <dialog class="modal" id="editCity{{ $city->id }}">
      <form method="POST" action="{{ route('admin.cities.update', $city) }}">
        @csrf @method('PUT')
        <div class="card__head"><h2>Şəhəri redaktə et</h2></div>
        <div class="modal__body">
          <div class="field">
            <label for="cityName{{ $city->id }}">Ad</label>
            <input class="input" type="text" id="cityName{{ $city->id }}" name="name"
                   value="{{ $city->name }}" required maxlength="100">
          </div>
          <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked($city->is_active)>
            Aktiv
          </label>
        </div>
        <div class="modal__foot">
          <button class="btn btn--ghost" type="button" data-close>İmtina</button>
          <button class="btn btn--primary" type="submit">Yadda saxla</button>
        </div>
      </form>
    </dialog>
  @endforeach

  <dialog class="modal" id="addCity">
    <form method="POST" action="{{ route('admin.cities.store') }}">
      @csrf
      <div class="card__head"><h2>Yeni şəhər</h2></div>
      <div class="modal__body">
        <div class="field">
          <label for="newCityName">Ad</label>
          <input class="input" type="text" id="newCityName" name="name" required maxlength="100"
                 placeholder="Məsələn: Şəki">
        </div>
        <label class="check">
          <input type="checkbox" name="is_active" value="1" checked>
          Dərhal aktiv olsun
        </label>
      </div>
      <div class="modal__foot">
        <button class="btn btn--ghost" type="button" data-close>İmtina</button>
        <button class="btn btn--primary" type="submit">Əlavə et</button>
      </div>
    </form>
  </dialog>

@endsection
