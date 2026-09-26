@extends('admin.layout')

@section('title', $page->title)
@section('subtitle', $page->label().' · /'.$page->slug)

@section('actions')
  <a href="{{ route('admin.pages.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>
  @if ($page->publicUrl())
    <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">Səhifəyə bax</a>
  @endif
@endsection

@section('content')

  <form method="POST" action="{{ route('admin.pages.update', $page) }}" id="pageForm">
    @csrf @method('PUT')

    <div class="card">
      <div class="card__head"><h2>Başlıq və giriş</h2></div>
      <div class="card__body">
        <div class="field">
          <label for="title">Başlıq</label>
          <input class="input" type="text" id="title" name="title" required maxlength="190"
                 value="{{ old('title', $page->title) }}">
          @error('title')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <label for="lead">Giriş mətni</label>
          <textarea class="textarea" id="lead" name="lead" rows="2"
                    maxlength="1000">{{ old('lead', $page->lead) }}</textarea>
          <div class="help">Başlığın altındakı böyük mətn.</div>
          @error('lead')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <label for="meta_description">Meta təsvir (SEO)</label>
          <textarea class="textarea" id="meta_description" name="meta_description" rows="2"
                    maxlength="300">{{ old('meta_description', $page->meta_description) }}</textarea>
          <div class="help">Google nəticələrində görünən təsvir. Boş buraxsanız giriş mətni işlədilir.</div>
          @error('meta_description')<div class="err">{{ $message }}</div>@enderror
        </div>

        <div class="split--even" style="display:grid;gap:18px">
          <div class="field">
            <label for="content_updated_at">Son yenilənmə tarixi</label>
            <input class="input" type="date" id="content_updated_at" name="content_updated_at"
                   value="{{ old('content_updated_at', $page->content_updated_at?->format('Y-m-d')) }}">
            <div class="help">Səhifədə nişan kimi göstərilir.</div>
            @error('content_updated_at')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label>Vəziyyət</label>
            <label class="check">
              <input type="checkbox" name="is_published" value="1"
                     @checked(old('is_published', $page->is_published))>
              Saytda yayımlansın
            </label>
            <div class="help">Söndürsəniz səhifə 404 verir.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card__head">
        <h2>Məzmun (HTML)</h2>
        <span class="muted">Mündəricat <span class="mono">&lt;h2 id="..."&gt;</span> başlıqlarından avtomatik qurulur</span>
        <button class="btn btn--ghost btn--sm" type="button" id="togglePreview">Önizləməni gizlət</button>
      </div>

      <div class="editor" id="editor">
        <div class="editor__pane">
          <div class="editor__bar">
            <span class="muted small">HTML</span>
            <div class="btn-row">
              <button class="btn btn--ghost btn--sm" type="button" data-snippet="h2">Başlıq</button>
              <button class="btn btn--ghost btn--sm" type="button" data-snippet="p">Abzas</button>
              <button class="btn btn--ghost btn--sm" type="button" data-snippet="ul">Siyahı</button>
              <button class="btn btn--ghost btn--sm" type="button" data-snippet="table">Cədvəl</button>
              <button class="btn btn--ghost btn--sm" type="button" data-snippet="callout">Qeyd</button>
            </div>
          </div>
          <textarea id="body" name="body" class="editor__code" spellcheck="false"
                    required>{{ old('body', $page->body) }}</textarea>
        </div>

        <div class="editor__pane editor__pane--preview">
          <div class="editor__bar">
            <span class="muted small">Önizləmə</span>
            <span class="muted small" id="tocCount"></span>
          </div>
          <div class="editor__preview">
            <nav class="prose-toc" id="tocPreview" hidden>
              <h4>Bölmələr</h4>
              <ul></ul>
            </nav>
            <article class="prose" id="preview"></article>
          </div>
        </div>
      </div>

      @error('body')<div class="card__body"><div class="err">{{ $message }}</div></div>@enderror
    </div>

    <div class="btn-row">
      <button class="btn btn--primary" type="submit">Yadda saxla</button>
      <a class="btn btn--ghost" href="{{ route('admin.pages.index') }}">İmtina</a>
    </div>
  </form>

@endsection

@push('scripts')
<script>
(function () {
  var body    = document.getElementById('body');
  var preview = document.getElementById('preview');
  var toc     = document.getElementById('tocPreview');
  var tocList = toc.querySelector('ul');
  var count   = document.getElementById('tocCount');

  function render() {
    // Önizləmə admin sessiyasında, öz məzmunumuzla işləyir — burada yazılan
    // HTML saytda da eyni şəkildə göstəriləcək, ona görə olduğu kimi qurulur.
    preview.innerHTML = body.value;

    var heads = preview.querySelectorAll('h2[id]');
    tocList.innerHTML = '';

    heads.forEach(function (h) {
      var li = document.createElement('li');
      var a  = document.createElement('a');
      a.textContent = h.textContent.trim();
      a.href = '#' + h.id;
      a.addEventListener('click', function (e) {
        e.preventDefault();
        h.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
      li.appendChild(a);
      tocList.appendChild(li);
    });

    toc.hidden = heads.length === 0;
    count.textContent = heads.length ? heads.length + ' bölmə' : 'id-li başlıq yoxdur';
  }

  var timer;
  body.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(render, 180);
  });
  render();

  // Tez əlavə üçün hazır bloklar.
  var snippets = {
    h2:      '\n<h2 id="yeni-bolme">Yeni bölmə</h2>\n',
    p:       '\n<p>Mətn...</p>\n',
    ul:      '\n<ul>\n  <li>Birinci</li>\n  <li>İkinci</li>\n</ul>\n',
    table:   '\n<table>\n  <tr><th>Sütun</th><th>Sütun</th></tr>\n  <tr><td>Dəyər</td><td>Dəyər</td></tr>\n</table>\n',
    callout: '\n<div class="callout">\n  <p>Vacib qeyd...</p>\n</div>\n'
  };

  document.querySelectorAll('[data-snippet]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = snippets[btn.dataset.snippet] || '';
      var at   = body.selectionStart;
      body.value = body.value.slice(0, at) + text + body.value.slice(body.selectionEnd);
      body.focus();
      body.selectionStart = body.selectionEnd = at + text.length;
      render();
    });
  });

  document.getElementById('togglePreview').addEventListener('click', function () {
    var ed = document.getElementById('editor');
    var off = ed.classList.toggle('editor--solo');
    this.textContent = off ? 'Önizləməni göstər' : 'Önizləməni gizlət';
  });

  // Tab düyməsi textarea-dan çıxmasın.
  body.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab') return;
    e.preventDefault();
    var at = body.selectionStart;
    body.value = body.value.slice(0, at) + '  ' + body.value.slice(body.selectionEnd);
    body.selectionStart = body.selectionEnd = at + 2;
  });
})();
</script>
@endpush
