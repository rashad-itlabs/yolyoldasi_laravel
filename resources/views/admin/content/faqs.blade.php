@extends('admin.layout')

@section('title', 'Ən çox verilən suallar')
@section('subtitle', $faqs->count().' sual · '.$faqs->where('is_active', true)->count().' aktiv')

@section('actions')
  <a href="{{ url('/') }}#faq" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">Saytda bax</a>
  <button class="btn btn--primary btn--sm" data-modal="#addFaq" type="button">Yeni sual</button>
@endsection

@section('content')

  <div class="card">
    <div class="card__head">
      <h2>Sıra</h2>
      <span class="muted">Ana səhifədəki FAQ bölməsi bu sıra ilə göstərilir</span>
    </div>
    <div class="card__body card__body--flush">
      @if ($faqs->isEmpty())
        <div class="empty"><strong>Sual yoxdur</strong>İlk sualı əlavə edin.</div>
      @else
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th style="width:74px">Sıra</th><th>Sual</th><th>Cavab</th><th>Vəziyyət</th><th></th></tr>
            </thead>
            <tbody>
              @foreach ($faqs as $faq)
                <tr>
                  <td class="nowrap">
                    <div class="btn-row" style="gap:3px;flex-wrap:nowrap">
                      <form method="POST" action="{{ route('admin.faqs.move', $faq) }}" class="inline">
                        @csrf <input type="hidden" name="direction" value="up">
                        <button class="btn btn--ghost btn--sm" type="submit" aria-label="Yuxarı"
                                @disabled($loop->first)>↑</button>
                      </form>
                      <form method="POST" action="{{ route('admin.faqs.move', $faq) }}" class="inline">
                        @csrf <input type="hidden" name="direction" value="down">
                        <button class="btn btn--ghost btn--sm" type="submit" aria-label="Aşağı"
                                @disabled($loop->last)>↓</button>
                      </form>
                    </div>
                  </td>
                  <td><strong>{{ $faq->question }}</strong></td>
                  <td><span class="truncate">{{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 90) }}</span></td>
                  <td>
                    <span class="badge {{ $faq->is_active ? 'badge--green' : 'badge--grey' }}">
                      {{ $faq->is_active ? 'Göstərilir' : 'Gizli' }}
                    </span>
                  </td>
                  <td class="actions">
                    <button class="btn btn--ghost btn--sm" type="button" data-modal="#editFaq{{ $faq->id }}">Redaktə</button>

                    <form method="POST" action="{{ route('admin.faqs.toggle', $faq) }}" class="inline">
                      @csrf
                      <button class="btn btn--ghost btn--sm" type="submit">
                        {{ $faq->is_active ? 'Gizlət' : 'Göstər' }}
                      </button>
                    </form>

                    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="inline"
                          data-confirm="Bu sual silinsin?">
                      @csrf @method('DELETE')
                      <button class="btn btn--danger btn--sm" type="submit">Sil</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>

  @foreach ($faqs as $faq)
    <dialog class="modal" id="editFaq{{ $faq->id }}">
      <form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
        @csrf @method('PUT')
        <div class="card__head"><h2>Sualı redaktə et</h2></div>
        <div class="modal__body">
          <div class="field">
            <label for="q{{ $faq->id }}">Sual</label>
            <input class="input" type="text" id="q{{ $faq->id }}" name="question" required
                   maxlength="300" value="{{ $faq->question }}">
          </div>
          <div class="field">
            <label for="a{{ $faq->id }}">Cavab</label>
            <textarea class="textarea" id="a{{ $faq->id }}" name="answer" required rows="5"
                      maxlength="5000">{{ $faq->answer }}</textarea>
            <div class="help">Sadə HTML işlədə bilərsiniz: &lt;strong&gt;, &lt;a href&gt;, &lt;ul&gt;.</div>
          </div>
          <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked($faq->is_active)>
            Saytda göstərilsin
          </label>
        </div>
        <div class="modal__foot">
          <button class="btn btn--ghost" type="button" data-close>İmtina</button>
          <button class="btn btn--primary" type="submit">Yadda saxla</button>
        </div>
      </form>
    </dialog>
  @endforeach

  <dialog class="modal" id="addFaq">
    <form method="POST" action="{{ route('admin.faqs.store') }}">
      @csrf
      <div class="card__head"><h2>Yeni sual</h2></div>
      <div class="modal__body">
        <div class="field">
          <label for="newQ">Sual</label>
          <input class="input" type="text" id="newQ" name="question" required maxlength="300"
                 placeholder="Məsələn: Səfəri necə ləğv edim?">
        </div>
        <div class="field">
          <label for="newA">Cavab</label>
          <textarea class="textarea" id="newA" name="answer" required rows="5" maxlength="5000"
                    placeholder="Cavabı qısa və aydın yazın..."></textarea>
          <div class="help">Sadə HTML işlədə bilərsiniz: &lt;strong&gt;, &lt;a href&gt;, &lt;ul&gt;.</div>
        </div>
        <label class="check">
          <input type="checkbox" name="is_active" value="1" checked>
          Dərhal göstərilsin
        </label>
      </div>
      <div class="modal__foot">
        <button class="btn btn--ghost" type="button" data-close>İmtina</button>
        <button class="btn btn--primary" type="submit">Əlavə et</button>
      </div>
    </form>
  </dialog>

@endsection
