@extends('admin.layout')

@section('title', 'Sənəd yoxlaması')
@section('subtitle', ($driver->user->full_name ?: 'Adsız').' · '.$driver->user->phone)

@section('actions')
  <a href="{{ route('admin.drivers.index') }}" class="btn btn--ghost btn--sm">Növbəyə qayıt</a>
  <a href="{{ route('admin.users.show', $driver->user_id) }}" class="btn btn--ghost btn--sm">Profil</a>

  <form method="POST" action="{{ route('admin.drivers.approve', $driver) }}" class="inline"
        data-confirm="Bütün yüklənmiş sənədlər təsdiqlənsin?">
    @csrf
    <button class="btn btn--primary btn--sm" type="submit">Hamısını təsdiqlə</button>
  </form>

  <button class="btn btn--danger btn--sm" data-modal="#rejectProfile" type="button">Profili rədd et</button>
@endsection

@section('content')

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head">
          <h2>Sənədlər</h2>
          @include('admin.partials.driver-status', ['status' => $driver->status])
        </div>
        <div class="card__body">
          <div class="doc-grid">
            @foreach ($documents as $doc)
              @php $model = $doc['model']; @endphp
              <div class="doc">
                <div class="doc__head">
                  <strong>{{ $doc['label'] }}</strong>
                  @include('admin.partials.driver-status', ['status' => $model->status ?? 'not_uploaded'])
                </div>

                <div class="doc__body">
                  @if (! $model || ! $model->file_url)
                    <div class="doc__thumb"><div class="doc__none">Yüklənməyib</div></div>
                  @else
                    <div class="doc__links">
                      <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener"
                         href="{{ route('admin.documents.file', [$model, 'front']) }}">Ön üz</a>
                      @if ($doc['two_sided'] && $model->back_file_url)
                        <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener"
                           href="{{ route('admin.documents.file', [$model, 'back']) }}">Arxa üz</a>
                      @elseif ($doc['two_sided'])
                        <span class="badge badge--amber">Arxa üz yoxdur</span>
                      @endif
                    </div>

                    <a class="doc__thumb" target="_blank" rel="noopener"
                       href="{{ route('admin.documents.file', [$model, 'front']) }}">
                      <img src="{{ route('admin.documents.file', [$model, 'front']) }}" alt="{{ $doc['label'] }}"
                           loading="lazy" onerror="this.parentNode.innerHTML='<div class=&quot;doc__none&quot;>Önizləmə yoxdur — açmaq üçün klikləyin</div>'">
                    </a>

                    <div class="small muted" style="margin-top:9px">
                      Yükləndi: {{ $model->uploaded_at?->format('d.m.Y H:i') ?? '—' }}
                    </div>

                    @if ($model->rejection_reason)
                      <div class="err">{{ $model->rejection_reason }}</div>
                    @endif

                    <div class="btn-row" style="margin-top:11px">
                      <form method="POST" action="{{ route('admin.documents.approve', $model) }}" class="inline">
                        @csrf
                        <button class="btn btn--primary btn--sm" type="submit"
                                @disabled($model->status === 'approved')>Təsdiqlə</button>
                      </form>
                      <button class="btn btn--ghost btn--sm" type="button"
                              data-modal="#rejectDoc{{ $model->id }}">Rədd et</button>
                    </div>
                  @endif
                </div>
              </div>

              @if ($model)
                <dialog class="modal" id="rejectDoc{{ $model->id }}">
                  <form method="POST" action="{{ route('admin.documents.reject', $model) }}">
                    @csrf
                    <div class="card__head"><h2>{{ $doc['label'] }} — rədd</h2></div>
                    <div class="modal__body">
                      <p class="muted small mt-0">Səbəb sürücüyə bildiriş kimi göndərilir.</p>
                      <div class="field">
                        <label for="reason{{ $model->id }}">Səbəb</label>
                        <textarea class="textarea" id="reason{{ $model->id }}" name="rejection_reason"
                                  required maxlength="500" placeholder="Məsələn: şəkil aydın deyil"></textarea>
                      </div>
                    </div>
                    <div class="modal__foot">
                      <button class="btn btn--ghost" type="button" data-close>İmtina</button>
                      <button class="btn btn--danger" type="submit">Rədd et</button>
                    </div>
                  </form>
                </dialog>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card__head"><h2>Sürücü</h2></div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Ad</dt><dd>
              <a href="{{ route('admin.users.show', $driver->user_id) }}">{{ $driver->user->full_name ?: 'Adsız' }}</a>
            </dd></div>
            <div class="row"><dt>Telefon</dt><dd class="mono">{{ $driver->user->phone }}</dd></div>
            <div class="row"><dt>Şəhər</dt><dd>{{ $driver->user->city?->name ?? '—' }}</dd></div>
            <div class="row"><dt>Doğum ili</dt><dd>{{ $driver->user->birth_year ?? '—' }}</dd></div>
            <div class="row"><dt>Reytinq</dt><dd>
              @include('admin.partials.stars', ['rating' => round($driver->user->stats?->driver_rating ?? 0)])
              <span class="muted small">{{ $driver->user->stats?->driver_review_count ?? 0 }} rəy</span>
            </dd></div>
            <div class="row"><dt>Qeydiyyat</dt><dd>{{ $driver->user->created_at?->format('d.m.Y') }}</dd></div>
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Avtomobil</h2></div>
        <div class="card__body">
          @if (! $driver->vehicle)
            <p class="muted mb-0">Avtomobil seçilməyib.</p>
          @else
            <dl class="rows">
              <div class="row"><dt>Marka / Model</dt><dd>{{ $driver->vehicle->brand }} {{ $driver->vehicle->model }}</dd></div>
              <div class="row"><dt>Nömrə</dt><dd class="mono">{{ $driver->vehicle->plate ?: '—' }}</dd></div>
              <div class="row"><dt>İl</dt><dd>{{ $driver->vehicle->year ?: '—' }}</dd></div>
              <div class="row"><dt>Rəng</dt><dd>{{ $driver->vehicle->color ?: '—' }}</dd></div>
              <div class="row"><dt>Yer</dt><dd>{{ $driver->vehicle->seats }}</dd></div>
            </dl>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Yoxlama</h2></div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Göndərilib</dt><dd>{{ $driver->submitted_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            <div class="row"><dt>Son baxış</dt><dd>{{ $driver->reviewed_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
            <div class="row"><dt>Ani rezervasiya</dt>
              <dd>{{ $driver->instant_booking_default ? 'Açıq' : 'Bağlı' }}</dd>
            </div>
            @if ($driver->rejection_reason)
              <div class="row"><dt>Rədd səbəbi</dt><dd class="err">{{ $driver->rejection_reason }}</dd></div>
            @endif
          </dl>
        </div>
      </div>

      {{-- PRO: ödəniş qatı yoxdur — sınaqdan başqa yeganə yol bu kartdır. --}}
      @php $isPro = $driver->isPro(); @endphp
      <div class="card">
        <div class="card__head">
          <h2>PRO</h2>
          <span class="badge {{ $isPro ? 'badge--mint' : 'badge--grey' }}">{{ $isPro ? 'Aktiv' : 'Aktiv deyil' }}</span>
        </div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Bitmə</dt>
              <dd>
                @if ($isPro)
                  {{ $driver->pro_until->format('d.m.Y H:i') }}
                @elseif ($driver->pro_until)
                  <span class="muted">Bitib · {{ $driver->pro_until->format('d.m.Y') }}</span>
                @else
                  —
                @endif
              </dd>
            </div>
            <div class="row"><dt>Pulsuz sınaq</dt>
              <dd>
                @if ($driver->pro_trial_used_at)
                  İstifadə olunub · {{ $driver->pro_trial_used_at->format('d.m.Y') }}
                @else
                  <span class="muted">İstifadə olunmayıb</span>
                @endif
              </dd>
            </div>
          </dl>

          <div class="btn-row" style="margin-top:11px">
            <form method="POST" action="{{ route('admin.drivers.pro.grant', $driver) }}" class="inline">
              @csrf
              <button class="btn btn--primary btn--sm" type="submit">+{{ \App\Services\ProService::GRANT_DAYS }} gün PRO</button>
            </form>

            @if ($isPro)
              <form method="POST" action="{{ route('admin.drivers.pro.revoke', $driver) }}" class="inline"
                    data-confirm="PRO dərhal ləğv edilsin? Sürücünün elanları axtarışın başından düşəcək (dəvət mükafatı varsa, o qalır).">
                @csrf
                <button class="btn btn--danger btn--sm" type="submit">PRO-nu ləğv et</button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>

  </div>

  <dialog class="modal" id="rejectProfile">
    <form method="POST" action="{{ route('admin.drivers.reject', $driver) }}">
      @csrf
      <div class="card__head"><h2>Profili rədd et</h2></div>
      <div class="modal__body">
        <p class="muted small mt-0">Sürücü bildiriş alacaq və sənədləri yenidən yükləyə biləcək.</p>
        <div class="field">
          <label for="profileReason">Səbəb</label>
          <textarea class="textarea" id="profileReason" name="rejection_reason" required maxlength="500"
                    placeholder="Məsələn: sənədlərdəki ad uyğun gəlmir"></textarea>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--ghost" type="button" data-close>İmtina</button>
        <button class="btn btn--danger" type="submit">Rədd et</button>
      </div>
    </form>
  </dialog>

@endsection
