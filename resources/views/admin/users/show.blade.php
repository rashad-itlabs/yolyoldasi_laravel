@extends('admin.layout')

@section('title', $user->full_name ?: 'Adsız istifadəçi')
@section('subtitle', '#'.$user->id.' · '.$user->phone)

@section('actions')
  <a href="{{ route('admin.users.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>

  @unless ($user->trashed())
    <button class="btn btn--ghost btn--sm" data-modal="#pushModal" type="button">Push göndər</button>
  @endunless

  @if ($user->trashed())
    <form method="POST" action="{{ route('admin.users.restore', $user->id) }}" class="inline">
      @csrf
      <button class="btn btn--primary btn--sm" type="submit">Bərpa et</button>
    </form>
  @elseif ($user->is_blocked)
    <form method="POST" action="{{ route('admin.users.unblock', $user) }}" class="inline">
      @csrf
      <button class="btn btn--primary btn--sm" type="submit">Blokdan çıxar</button>
    </form>
  @else
    <button class="btn btn--warn btn--sm" data-modal="#blockModal" type="button">Blokla</button>
  @endif

  @unless ($user->trashed())
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
          data-confirm="Bu hesab silinsin? Tokenləri ləğv olunacaq.">
      @csrf @method('DELETE')
      <button class="btn btn--danger btn--sm" type="submit">Sil</button>
    </form>
  @endunless
@endsection

@section('content')

  @if (session('new_token'))
    <div class="card" style="border-color:var(--mint-dark)">
      <div class="card__head"><h2>Yeni token</h2><span class="badge badge--amber">yalnız bir dəfə görünür</span></div>
      <div class="card__body">
        <p class="muted small mt-0">
          Bu dəyər bazada saxlanılmır — səhifəni yeniləsəniz bir daha göstərilməyəcək.
          Sorğularda <span class="mono">Authorization: Bearer &lt;token&gt;</span> başlığı ilə göndərin.
        </p>
        <p class="pre" id="newToken">{{ session('new_token') }}</p>
        <div class="btn-row">
          <button class="btn btn--dark btn--sm" type="button" data-copy="#newToken">Kopyala</button>
        </div>
      </div>
    </div>
  @endif

  @if ($user->trashed())
    <div class="flash flash--err">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M9 9l6 6m0-6-6 6"/></svg>
      <div>Bu hesab {{ $user->deleted_at->format('d.m.Y H:i') }} tarixində silinib.</div>
    </div>
  @elseif ($user->is_blocked)
    <div class="flash flash--err">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>
      <div>
        <strong>Bloklanıb</strong> — {{ $user->block_reason }}
        <div class="small">{{ $user->blocked_at?->format('d.m.Y H:i') }}</div>
      </div>
    </div>
  @endif

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head"><h2>Profil</h2></div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Telefon</dt><dd class="mono">{{ $user->phone }}</dd></div>
            <div class="row"><dt>Telefon təsdiqi</dt>
              <dd>
                @if ($user->phone_verified_at)
                  <span class="badge badge--green">{{ $user->phone_verified_at->format('d.m.Y H:i') }}</span>
                @else
                  <span class="badge badge--amber">Təsdiqlənməyib</span>
                @endif
              </dd>
            </div>
            <div class="row"><dt>Firebase UID</dt><dd class="mono small">{{ $user->firebase_uid ?? '—' }}</dd></div>
            <div class="row"><dt>Şəhər</dt><dd>{{ $user->city?->name ?? '—' }}</dd></div>
            <div class="row"><dt>Cins</dt>
              <dd>{{ ['male' => 'Kişi', 'female' => 'Qadın', 'unspecified' => 'Göstərilməyib'][$user->gender] }}</dd>
            </div>
            <div class="row"><dt>Doğum ili</dt><dd>{{ $user->birth_year ?? '—' }}</dd></div>
            <div class="row"><dt>Aktiv rejim</dt>
              <dd><span class="badge {{ $user->active_mode === 'driver' ? 'badge--blue' : 'badge--grey' }}">
                {{ $user->active_mode === 'driver' ? 'Sürücü' : 'Sərnişin' }}
              </span></dd>
            </div>
            <div class="row"><dt>Dil</dt><dd>{{ mb_strtoupper($user->language_code) }}</dd></div>
            <div class="row"><dt>Haqqında</dt><dd>{{ $user->about ?: '—' }}</dd></div>
            <div class="row"><dt>Qeydiyyat</dt><dd>{{ $user->created_at?->format('d.m.Y H:i') }}</dd></div>
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card__head">
          <h2>API tokenləri</h2>
          @if (auth('admin')->user()?->isSuperAdmin() && ! $user->trashed())
            <button class="btn btn--dark btn--sm" data-modal="#tokenModal" type="button">Yeni token</button>
          @endif
        </div>
        <div class="card__body card__body--flush">
          @if ($tokens->isEmpty())
            <div class="empty">
              <strong>Token yoxdur</strong>
              Bu hesab heç bir cihazdan API-yə qoşulmayıb.
            </div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <thead>
                  <tr>
                    <th>Ad</th>
                    <th>Səlahiyyət</th>
                    <th>Yaradılıb</th>
                    <th>Son istifadə</th>
                    <th>Bitmə</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($tokens as $token)
                    @php $expired = $token->expires_at && $token->expires_at->isPast(); @endphp
                    <tr>
                      <td>
                        <strong>{{ $token->name }}</strong>
                        <div class="muted small mono">#{{ $token->id }}</div>
                      </td>
                      <td class="small mono">{{ implode(', ', $token->abilities ?? []) ?: '—' }}</td>
                      <td class="nowrap muted small">{{ $token->created_at?->format('d.m.Y H:i') }}</td>
                      <td class="nowrap muted small">{{ $token->last_used_at?->format('d.m.Y H:i') ?? 'heç vaxt' }}</td>
                      <td class="nowrap small">
                        @if (! $token->expires_at)
                          <span class="badge badge--grey">Müddətsiz</span>
                        @elseif ($expired)
                          <span class="badge badge--red">{{ $token->expires_at->format('d.m.Y H:i') }}</span>
                        @else
                          <span class="badge badge--green">{{ $token->expires_at->format('d.m.Y H:i') }}</span>
                        @endif
                      </td>
                      <td class="actions">
                        <form method="POST" action="{{ route('admin.users.tokens.destroy', [$user->id, $token->id]) }}"
                              class="inline" data-confirm="Token #{{ $token->id }} ləğv edilsin? Həmin cihaz sistemdən çıxacaq.">
                          @csrf @method('DELETE')
                          <button class="btn btn--ghost btn--sm" type="submit">Ləğv et</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
        <div class="card__body" style="border-top:1px solid var(--line)">
          <p class="muted small mt-0 mb-0">
            Tokenin açıq dəyəri bazada saxlanmır (yalnız hash) — mövcud tokeni geri oxumaq mümkün deyil.
            Lazım olsa yenisini yaradın.
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card__head">
          <h2>Səfərləri</h2>
          <span class="muted">sürücü kimi son 10</span>
        </div>
        <div class="card__body card__body--flush">
          @if ($rides->isEmpty())
            <div class="empty">Səfər elanı yoxdur.</div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <thead><tr><th>Marşrut</th><th>Vaxt</th><th class="num">Yer</th><th>Status</th><th></th></tr></thead>
                <tbody>
                  @foreach ($rides as $ride)
                    <tr>
                      <td><strong>{{ $ride->fromCity?->name }} → {{ $ride->toCity?->name }}</strong></td>
                      <td class="nowrap small">{{ $ride->departure_at?->format('d.m.Y H:i') }}</td>
                      <td class="num">{{ $ride->booked_seats }}/{{ $ride->total_seats }}</td>
                      <td>@include('admin.partials.ride-status', ['status' => $ride->status])</td>
                      <td class="actions"><a href="{{ route('admin.rides.show', $ride) }}" class="btn btn--ghost btn--sm">Bax</a></td>
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
          <h2>Rezervasiyaları</h2>
          <span class="muted">sərnişin kimi son 10</span>
        </div>
        <div class="card__body card__body--flush">
          @if ($bookings->isEmpty())
            <div class="empty">Rezervasiya yoxdur.</div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <thead><tr><th>Marşrut</th><th>Vaxt</th><th class="num">Yer</th><th>Status</th><th></th></tr></thead>
                <tbody>
                  @foreach ($bookings as $booking)
                    <tr>
                      <td><strong>{{ $booking->ride?->fromCity?->name }} → {{ $booking->ride?->toCity?->name }}</strong></td>
                      <td class="nowrap small">{{ $booking->ride?->departure_at?->format('d.m.Y H:i') }}</td>
                      <td class="num">{{ $booking->seats }}</td>
                      <td>@include('admin.partials.booking-status', ['status' => $booking->status])</td>
                      <td class="actions"><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn--ghost btn--sm">Bax</a></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Aldığı rəylər</h2></div>
        <div class="card__body card__body--flush">
          @if ($reviews->isEmpty())
            <div class="empty">Rəy yoxdur.</div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <thead><tr><th>Müəllif</th><th>Qiymət</th><th>Şərh</th><th>Tarix</th></tr></thead>
                <tbody>
                  @foreach ($reviews as $review)
                    <tr>
                      <td>@include('admin.partials.user', ['user' => $review->author])</td>
                      <td>@include('admin.partials.stars', ['rating' => $review->rating])</td>
                      <td class="small">{{ $review->comment ?: '—' }}</td>
                      <td class="nowrap muted small">{{ $review->created_at?->format('d.m.Y') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card__head"><h2>Reytinq</h2></div>
        <div class="card__body">
          <dl class="rows">
            <div class="row"><dt>Sürücü</dt>
              <dd>
                @include('admin.partials.stars', ['rating' => round($user->stats?->driver_rating ?? 0)])
                <span class="muted small">{{ number_format((float) ($user->stats?->driver_rating ?? 0), 2) }} · {{ $user->stats?->driver_review_count ?? 0 }} rəy</span>
              </dd>
            </div>
            <div class="row"><dt>Sərnişin</dt>
              <dd>
                @include('admin.partials.stars', ['rating' => round($user->stats?->passenger_rating ?? 0)])
                <span class="muted small">{{ number_format((float) ($user->stats?->passenger_rating ?? 0), 2) }} · {{ $user->stats?->passenger_review_count ?? 0 }} rəy</span>
              </dd>
            </div>
            <div class="row"><dt>Sürücü səfəri</dt><dd>{{ $user->stats?->driver_trip_count ?? 0 }}</dd></div>
            <div class="row"><dt>Sərnişin səfəri</dt><dd>{{ $user->stats?->passenger_trip_count ?? 0 }}</dd></div>
          </dl>
        </div>
      </div>

      <div class="card">
        <div class="card__head">
          <h2>Sürücü statusu</h2>
          @if ($user->driverProfile)
            <a href="{{ route('admin.drivers.show', $user->driverProfile) }}" class="btn btn--ghost btn--sm">Sənədlər</a>
          @endif
        </div>
        <div class="card__body">
          @if (! $user->driverProfile)
            <p class="muted mb-0">Sürücü profili yaradılmayıb.</p>
          @else
            <dl class="rows">
              <div class="row"><dt>Status</dt>
                <dd>@include('admin.partials.driver-status', ['status' => $user->driverProfile->status])</dd>
              </div>
              <div class="row"><dt>Göndərilib</dt><dd>{{ $user->driverProfile->submitted_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
              <div class="row"><dt>Baxılıb</dt><dd>{{ $user->driverProfile->reviewed_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
              @if ($user->driverProfile->rejection_reason)
                <div class="row"><dt>Rədd səbəbi</dt><dd>{{ $user->driverProfile->rejection_reason }}</dd></div>
              @endif
            </dl>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Avtomobillər</h2></div>
        <div class="card__body">
          @if ($user->vehicles->isEmpty())
            <p class="muted mb-0">Avtomobil əlavə edilməyib.</p>
          @else
            <dl class="rows">
              @foreach ($user->vehicles as $vehicle)
                <div class="row">
                  <dt>{{ $vehicle->brand }} {{ $vehicle->model }}</dt>
                  <dd>
                    <span class="mono">{{ $vehicle->plate ?: '—' }}</span>
                    <span class="muted small">· {{ $vehicle->year ?: '—' }} · {{ $vehicle->seats }} yer · {{ $vehicle->color ?: '—' }}</span>
                  </dd>
                </div>
              @endforeach
            </dl>
          @endif
        </div>
      </div>

      <div class="card">
        <div class="card__head">
          <h2>Haqqında şikayətlər</h2>
          <span class="badge {{ $reportsAgainst->where('status', 'open')->count() ? 'badge--red' : 'badge--grey' }}">
            {{ $reportsAgainst->count() }}
          </span>
        </div>
        <div class="card__body card__body--flush">
          @if ($reportsAgainst->isEmpty())
            <div class="empty">Şikayət yoxdur.</div>
          @else
            <div class="table-wrap">
              <table class="tbl">
                <tbody>
                  @foreach ($reportsAgainst as $report)
                    <tr>
                      <td>
                        <span class="badge badge--amber">
                          {{ \App\Http\Controllers\Admin\ReportController::REASON_LABELS[$report->reason] ?? $report->reason }}
                        </span>
                      </td>
                      <td class="muted small nowrap">{{ $report->created_at?->format('d.m.Y') }}</td>
                      <td class="actions"><a href="{{ route('admin.reports.show', $report) }}" class="btn btn--ghost btn--sm">Bax</a></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

  </div>

  @unless ($user->trashed())
    <dialog class="modal" id="pushModal">
      <form method="POST" action="{{ route('admin.push.store') }}">
        @csrf
        <input type="hidden" name="target" value="user">
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <div class="card__head"><h2>Push bildirişi</h2></div>
        <div class="modal__body">
          <p class="muted small mt-0">
            Yalnız bu hesaba gedir: push kimi cihaza, həm də tətbiqin bildiriş
            siyahısına yazılır.
          </p>
          <div class="field">
            <label for="push_type">Növ</label>
            <select class="select" id="push_type" name="type" required>
              @foreach (\App\Http\Controllers\Admin\PushController::TYPES as $key => [$label, $note])
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="field">
            <label for="push_heading">Başlıq</label>
            <input class="input" type="text" id="push_heading" name="heading" required maxlength="60"
                   placeholder="Məsələn: Sənədləriniz barədə">
          </div>
          <div class="field">
            <label for="push_content">Mətn</label>
            <textarea class="textarea" id="push_content" name="content" required maxlength="240"
                      placeholder="Qısa mətn — telefonda iki sətir görünür."></textarea>
          </div>
        </div>
        <div class="modal__foot">
          <button class="btn btn--ghost" type="button" data-close>İmtina</button>
          <button class="btn btn--primary" type="submit">Göndər</button>
        </div>
      </form>
    </dialog>
  @endunless

  <dialog class="modal" id="blockModal">
    <form method="POST" action="{{ route('admin.users.block', $user) }}">
      @csrf
      <div class="card__head"><h2>Hesabı blokla</h2></div>
      <div class="modal__body">
        <p class="muted small mt-0">Bloklanan istifadəçi tətbiqə girə bilməyəcək. Səbəb jurnalda saxlanılır.</p>
        <div class="field">
          <label for="block_reason">Səbəb</label>
          <textarea class="textarea" id="block_reason" name="block_reason" required maxlength="255"
                    placeholder="Məsələn: təkrarlanan şikayətlər"></textarea>
        </div>
      </div>
      <div class="modal__foot">
        <button class="btn btn--ghost" type="button" data-close>İmtina</button>
        <button class="btn btn--danger" type="submit">Blokla</button>
      </div>
    </form>
  </dialog>

  @if (auth('admin')->user()?->isSuperAdmin() && ! $user->trashed())
    <dialog class="modal" id="tokenModal">
      <form method="POST" action="{{ route('admin.users.tokens.store', $user->id) }}">
        @csrf
        <div class="card__head"><h2>Yeni API tokeni</h2></div>
        <div class="modal__body">
          <p class="muted small mt-0">
            Token bu hesabın adından API-yə tam giriş verir. Açıq dəyər yalnız bir dəfə —
            yaradılan anda göstərilir. Əməliyyat jurnala yazılır.
          </p>
          <div class="field">
            <label for="token_name">Ad</label>
            <input class="input" type="text" id="token_name" name="name" required maxlength="60"
                   value="admin-panel" placeholder="Məsələn: postman">
            <div class="help">Siyahıda tokeni tanımaq üçündür.</div>
          </div>
          <div class="field">
            <label for="token_expires">Müddət</label>
            <select class="select" id="token_expires" name="expires" required>
              @foreach (\App\Http\Controllers\Admin\UserController::TOKEN_LIFETIMES as $key => $label)
                <option value="{{ $key }}" @selected($key === '7')>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal__foot">
          <button class="btn btn--ghost" type="button" data-close>İmtina</button>
          <button class="btn btn--primary" type="submit">Token yarat</button>
        </div>
      </form>
    </dialog>
  @endif

@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = document.querySelector(btn.dataset.copy)?.textContent.trim() || '';
      navigator.clipboard?.writeText(text).then(function () {
        var old = btn.textContent;
        btn.textContent = 'Kopyalandı';
        setTimeout(function () { btn.textContent = old; }, 1500);
      });
    });
  });
</script>
@endpush
