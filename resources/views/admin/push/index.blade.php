@extends('admin.layout')

@section('title', 'Push göndər')
@section('subtitle', 'OneSignal üzərindən əl ilə bildiriş')

@section('content')

  @php
    $selectedType   = old('type', 'adminMessage');
    $selectedTarget = old('target', 'all');
  @endphp

  <div class="stat-grid">
    @foreach ($types as $key => [$label, $note])
      <div class="stat">
        <div class="stat__label">{{ $label }}</div>
        <div class="stat__value">{{ $reach[$key]['aliases'] + $reach[$key]['devices'] }}</div>
        <div class="stat__hint">
          {{ $reach[$key]['aliases'] }} hesab · {{ $reach[$key]['devices'] }} cihaz
        </div>
      </div>
    @endforeach
  </div>

  <div class="split">

    <div>
      <div class="card">
        <div class="card__head"><h2>Yeni bildiriş</h2></div>
        <div class="card__body">

          <form method="POST" action="{{ route('admin.push.store') }}" id="pushForm">
            @csrf

            <div class="field">
              <label>Kimə</label>

              <label class="check mb-16">
                <input type="radio" name="target" value="all" data-target
                       @checked($selectedTarget === 'all')>
                Hamıya
              </label>

              <label class="check">
                <input type="radio" name="target" value="user" data-target
                       @checked($selectedTarget === 'user')>
                Bir istifadəçiyə
              </label>
            </div>

            <div class="field" id="userField">
              <label for="user_id">İstifadəçi</label>
              <select class="select" name="user_id" id="user_id">
                <option value="">— seçin —</option>
                @foreach ($users as $u)
                  <option value="{{ $u->id }}" @selected((string) old('user_id') === (string) $u->id)>
                    {{ $u->full_name ?: 'Adsız' }} · {{ $u->phone }}{{ $u->is_blocked ? ' (bloklu)' : '' }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="field">
              <label for="type">Növ</label>
              <select class="select" name="type" id="type">
                @foreach ($types as $key => [$label, $note])
                  <option value="{{ $key }}" @selected($selectedType === $key)>{{ $label }}</option>
                @endforeach
              </select>
              @foreach ($types as $key => [$label, $note])
                <div class="small muted" data-type-note="{{ $key }}" @style(['display:none' => $selectedType !== $key])>
                  {{ $note }}
                </div>
              @endforeach
            </div>

            <div class="field">
              <label for="heading">Başlıq</label>
              <input class="input" type="text" name="heading" id="heading" maxlength="100" required
                     value="{{ old('heading') }}" placeholder="Məsələn: Texniki fasilə">
              <div class="small muted"><span data-count="heading">0</span>/100 — telefonda təxminən 45 simvol görünür, tətbiqdə hamısı</div>
            </div>

            <div class="field">
              <label for="content">Mətn</label>
              <textarea class="textarea" name="content" id="content" maxlength="1000" required
                        placeholder="Sabah 02:00–04:00 arası tətbiq işləməyəcək.">{{ old('content') }}</textarea>
              <div class="small muted"><span data-count="content">0</span>/1000 — tətbiqdə tam görünür</div>
            </div>

            <button class="btn btn--primary" type="submit">Göndər</button>
          </form>

        </div>
      </div>
    </div>

    <div>
      <div class="card mb-16">
        <div class="card__head"><h2>Önizləmə</h2></div>
        <div class="card__body">
          <div class="push-preview">
            <strong id="previewHeading" class="truncate">Başlıq</strong>
            <span id="previewContent">Mətn burada görünəcək.</span>
          </div>
          <p class="small muted mb-0">
            Bildiriş telefonun kilid ekranında belə görünür. Eyni mətn tətbiqin
            bildiriş siyahısına da yazılır.
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2>Necə işləyir</h2></div>
        <div class="card__body">
          <p class="small muted mt-0 mb-0">
            Göndərilən bildiriş hər alıcı üçün bazaya yazılır və tətbiq onu
            <span class="mono">GET /notifications</span> ilə oxuyur — yəni push
            çatmasa da, istifadəçi tətbiqi açanda siyahıda görür. Oxuyanların
            sayı aşağıdakı cədvəldə görünür.
          </p>
        </div>
      </div>
    </div>

  </div>

  <div class="card">
    <div class="card__head">
      <h2>Göndəriş tarixçəsi</h2>
      <span class="muted">{{ $sent->total() }} göndəriş</span>
    </div>

    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Bildiriş</th><th>Kimə</th><th>Növ</th>
            <th class="num">Alıcı</th><th class="num">Oxuyan</th>
            <th>Push</th><th>Admin</th><th>Tarix</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($sent as $message)
            <tr>
              <td>
                <strong>{{ $message->heading }}</strong>
                <span class="truncate small muted">{{ $message->content }}</span>
              </td>
              <td>
                @if ($message->target === 'all')
                  Hamıya
                @elseif ($message->user)
                  <a href="{{ route('admin.users.show', $message->user->id) }}">{{ $message->user->full_name ?: 'Adsız' }}</a>
                @else
                  <span class="muted">silinmiş istifadəçi</span>
                @endif
              </td>
              <td class="small">{{ $types[$message->type][0] ?? $message->type }}</td>
              <td class="num">{{ $message->recipients }}</td>
              <td class="num">
                {{ $message->read_count }}
                @if ($message->recipients)
                  <span class="muted small">({{ round($message->read_count / $message->recipients * 100) }}%)</span>
                @endif
              </td>
              <td>
                @if ($message->delivered)
                  <span class="badge badge--green">getdi</span>
                @else
                  <span class="badge badge--red">getmədi</span>
                @endif
              </td>
              <td class="small">{{ $message->admin?->name ?? '—' }}</td>
              <td class="small muted">{{ $message->created_at?->format('d.m.Y H:i') }}</td>
            </tr>
            @if ($message->error)
              <tr><td colspan="8" class="small muted">↳ {{ $message->error }}</td></tr>
            @endif
          @empty
            <tr><td colspan="8">
              <div class="empty"><strong>Hələ göndəriş yoxdur</strong>İlk bildirişi yuxarıdakı formadan göndərin.</div>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @include('admin.partials.pager', ['paginator' => $sent])
  </div>

@endsection

@push('scripts')
<style>
  .push-preview {
    background: #0f172a; color: #fff; border-radius: 12px;
    padding: 12px 14px; margin-bottom: 12px;
  }
  .push-preview strong { display: block; font-size: 13.5px; margin-bottom: 2px; }
  .push-preview span { font-size: 12.5px; color: rgba(255, 255, 255, .75); }
</style>
<script>
  (function () {
    var form     = document.getElementById('pushForm');
    var userWrap = document.getElementById('userField');
    var userSel  = document.getElementById('user_id');
    var typeSel  = document.getElementById('type');

    function currentTarget() {
      return form.querySelector('[data-target]:checked')?.value;
    }

    // "Hamıya" seçiləndə istifadəçi siyahısı gizlənir və `required` götürülür,
    // yoxsa gizli sahə formanın göndərilməsini bloklayardı.
    function syncTarget() {
      var single = currentTarget() === 'user';
      userWrap.style.display = single ? '' : 'none';
      userSel.required = single;
    }

    function syncTypeNote() {
      form.querySelectorAll('[data-type-note]').forEach(function (el) {
        el.style.display = el.dataset.typeNote === typeSel.value ? '' : 'none';
      });
    }

    function bindPreview(id, target, fallback) {
      var input = document.getElementById(id);
      var out   = document.getElementById(target);
      var count = form.querySelector('[data-count="' + id + '"]');

      function render() {
        out.textContent = input.value || fallback;
        count.textContent = input.value.length;
      }

      input.addEventListener('input', render);
      render();
    }

    form.querySelectorAll('[data-target]').forEach(function (el) {
      el.addEventListener('change', syncTarget);
    });
    typeSel.addEventListener('change', syncTypeNote);

    bindPreview('heading', 'previewHeading', 'Başlıq');
    bindPreview('content', 'previewContent', 'Mətn burada görünəcək.');
    syncTarget();
    syncTypeNote();

    form.addEventListener('submit', function (e) {
      if (currentTarget() !== 'all') return;

      var label = typeSel.options[typeSel.selectedIndex].text.toLowerCase();
      if (! window.confirm('Bu ' + label + ' bütün istifadəçilərə göndərilsin? Geri qaytarmaq mümkün deyil.')) {
        e.preventDefault();
      }
    });
  })();
</script>
@endpush
