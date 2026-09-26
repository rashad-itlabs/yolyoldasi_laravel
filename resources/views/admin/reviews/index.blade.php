@extends('admin.layout')

@section('title', 'Rəylər')
@section('subtitle', $reviews->total().' rəy')

@section('content')

  <div class="card">
    <form method="GET" class="filters">
      <div class="field grow">
        <label for="q">Axtarış</label>
        <input class="input" type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
               placeholder="Şərh mətni, ad və ya telefon">
      </div>

      <div class="field">
        <label for="rating">Qiymət</label>
        <select class="select" id="rating" name="rating">
          <option value="">Hamısı</option>
          @for ($i = 5; $i >= 1; $i--)
            <option value="{{ $i }}" @selected((string) ($filters['rating'] ?? '') === (string) $i)>{{ $i }} ulduz</option>
          @endfor
        </select>
      </div>

      <div class="field">
        <label for="role">Müəllif</label>
        <select class="select" id="role" name="role">
          <option value="">Hamısı</option>
          <option value="driver"    @selected(($filters['role'] ?? '') === 'driver')>Sürücü yazıb</option>
          <option value="passenger" @selected(($filters['role'] ?? '') === 'passenger')>Sərnişin yazıb</option>
        </select>
      </div>

      <button class="btn btn--dark" type="submit">Süz</button>
      <a class="btn btn--ghost" href="{{ route('admin.reviews.index') }}">Sıfırla</a>
    </form>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Müəllif</th>
            <th>Hədəf</th>
            <th>Qiymət</th>
            <th>Şərh</th>
            <th>Tarix</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($reviews as $review)
            <tr>
              <td>
                @include('admin.partials.user', ['user' => $review->author])
                <span class="badge badge--grey">{{ $review->author_was_driver ? 'Sürücü' : 'Sərnişin' }}</span>
              </td>
              <td>@include('admin.partials.user', ['user' => $review->target])</td>
              <td>@include('admin.partials.stars', ['rating' => $review->rating])</td>
              <td><span class="truncate">{{ $review->comment ?: '—' }}</span></td>
              <td class="nowrap muted small">{{ $review->created_at?->format('d.m.Y') }}</td>
              <td class="actions">
                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="inline"
                      data-confirm="Bu rəy silinsin? Hədəfin reytinqi yenidən hesablanacaq.">
                  @csrf @method('DELETE')
                  <button class="btn btn--danger btn--sm" type="submit">Sil</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6"><div class="empty"><strong>Nəticə yoxdur</strong>Süzgəci dəyişib yenidən yoxlayın.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $reviews])
  </div>

@endsection
