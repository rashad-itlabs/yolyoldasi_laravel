@extends('admin.layout')

@section('title', 'Tələb elanları')
@section('subtitle', $requests->total().' elan')

@section('content')

  <div class="card">
    <div class="tabs">
      @foreach (['' => 'Hamısı'] + $statuses as $key => $label)
        <a class="tab {{ ($filters['status'] ?? '') === $key ? 'is-active' : '' }}"
           href="{{ route('admin.ride-requests.index', array_filter(['status' => $key] + $filters)) }}">
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
               placeholder="Elan ID, sərnişin adı və ya telefonu">
      </div>

      <div class="field">
        <label for="from_city_id">Haradan</label>
        <select class="select" id="from_city_id" name="from_city_id">
          <option value="">Hamısı</option>
          @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) ($filters['from_city_id'] ?? '') === (string) $city->id)>{{ $city->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="field">
        <label for="to_city_id">Haraya</label>
        <select class="select" id="to_city_id" name="to_city_id">
          <option value="">Hamısı</option>
          @foreach ($cities as $city)
            <option value="{{ $city->id }}" @selected((string) ($filters['to_city_id'] ?? '') === (string) $city->id)>{{ $city->name }}</option>
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
      <a class="btn btn--ghost" href="{{ route('admin.ride-requests.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th class="num">#</th>
            <th>Marşrut</th>
            <th>Sərnişin</th>
            <th>İstədiyi tarix</th>
            <th class="num">Yer</th>
            <th>Status</th>
            <th>Matç</th>
            <th>Yaradılıb</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($requests as $req)
            <tr>
              <td class="num muted">{{ $req->id }}</td>
              <td>
                <strong>{{ $req->fromCity?->name }} → {{ $req->toCity?->name }}</strong>
                @if ($req->note)
                  {{-- Qeyd sərnişinin öz mətnidir: baxış səhifəsi olmadığı üçün
                       burada göstərilir, amma sətri dağıtmasın deyə qısaldılır. --}}
                  <div class="small muted">{{ \Illuminate\Support\Str::limit($req->note, 70) }}</div>
                @endif
              </td>
              <td>@include('admin.partials.user', ['user' => $req->user])</td>
              <td class="nowrap small">
                {{ $req->wanted_date?->format('d.m.Y') }}
                @if ($req->flexible_days > 0)
                  {{-- Çeviklik matçın nə qədər geniş axtarıldığını göstərir. --}}
                  <span class="muted">±{{ $req->flexible_days }} gün</span>
                @endif
              </td>
              <td class="num">{{ $req->seats }}</td>
              <td>@include('admin.partials.ride-request-status', ['status' => $req->status])</td>
              <td class="nowrap small">
                @if ($req->matchedRide)
                  <a href="{{ route('admin.rides.show', $req->matched_ride_id) }}">#{{ $req->matched_ride_id }}</a>
                @else
                  <span class="muted">—</span>
                @endif
              </td>
              <td class="nowrap small muted">{{ $req->created_at?->format('d.m.Y H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="8"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $requests])
  </div>

@endsection
