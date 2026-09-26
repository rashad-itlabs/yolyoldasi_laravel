@extends('admin.layout')

@section('title', 'Tətbiq versiyası')
@section('subtitle', 'Mobil tətbiqdə məcburi və könüllü yeniləmə xəbərdarlığı')

@section('content')

  <div class="card" style="max-width:760px">
    <div class="card__body">
      <p class="muted" style="margin:0">
        Tətbiq hər açılışda <code>GET /api/v1/app-version</code> sorğusu atır və cavaba görə davranır:
      </p>
      <ul class="muted" style="margin:.6rem 0 0; padding-left:1.1rem">
        <li><b>Build &lt; minimum</b> — ekran tam bloklanır, yalnız mağazaya keçid düyməsi qalır.</li>
        <li><b>minimum ≤ build &lt; sonuncu</b> — keçiləbilən xəbərdarlıq (“Yenilə” / “Sonra”).</li>
        <li><b>build ≥ sonuncu</b> — heç nə göstərilmir.</li>
      </ul>
      <p class="muted" style="margin:.6rem 0 0">
        Müqayisə <b>build nömrəsi</b> ilə aparılır (Android <code>versionCode</code>, iOS
        <code>CFBundleVersion</code>). Versiya sətri yalnız ekranda göstərilir.
      </p>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.app-version.update') }}">
    @csrf
    @method('PUT')

    @foreach ($platforms as $platform => [$label, $hint])
      @php $row = $rows[$platform]; @endphp

      <div class="card" style="max-width:760px">
        <div class="card__head">
          <h2>{{ $label }}</h2>
          <span class="muted">{{ $hint }}</span>
        </div>
        <div class="card__body">

          <div class="field">
            <label class="check">
              {{-- İşarəsiz checkbox sorğuya düşmür, ona görə `old()` doğrulama
                   səhvindən sonra köhnə dəyərə qayıtmamalıdır. --}}
              <input type="checkbox" name="{{ $platform }}[is_enabled]" value="1"
                     @checked($errors->any() ? old($platform.'.is_enabled') : $row->is_enabled)>
              Yoxlama işləsin
            </label>
            <div class="help">
              Söndürsəniz endpoint bu platforma üçün həmişə “heç nə lazım deyil” cavabı verir.
              Səhv rəqəm bütün istifadəçiləri kənarda qoyduqda geri dönüş yolu budur.
            </div>
          </div>

          <div class="field">
            <label for="{{ $platform }}_min_build">Minimum build</label>
            <input class="input" type="number" min="1" step="1"
                   id="{{ $platform }}_min_build" name="{{ $platform }}[min_build]"
                   value="{{ old($platform.'.min_build', $row->min_build) }}" required>
            <div class="help">
              Bundan aşağı build bloklanır. <b>1</b> — heç kim bloklanmır.
              Qaldırmazdan əvvəl yeni buraxılışın mağazada göründüyünə əmin olun.
            </div>
            @error($platform.'.min_build')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="{{ $platform }}_min_version">Minimum versiya (mətn)</label>
            <input class="input" type="text" maxlength="20"
                   id="{{ $platform }}_min_version" name="{{ $platform }}[min_version]"
                   value="{{ old($platform.'.min_version', $row->min_version) }}"
                   placeholder="1.1.0" required>
            <div class="help">Bloklama ekranında göstərilir. Qərara təsir etmir.</div>
            @error($platform.'.min_version')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="{{ $platform }}_latest_build">Mağazadakı sonuncu build</label>
            <input class="input" type="number" min="1" step="1"
                   id="{{ $platform }}_latest_build" name="{{ $platform }}[latest_build]"
                   value="{{ old($platform.'.latest_build', $row->latest_build) }}" required>
            <div class="help">
              Minimumdan yuxarı, bundan aşağı olan istifadəçilər keçiləbilən xəbərdarlıq görür.
              Minimumla bərabər yazsanız könüllü xəbərdarlıq olmur.
            </div>
            @error($platform.'.latest_build')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="{{ $platform }}_latest_version">Sonuncu versiya (mətn)</label>
            <input class="input" type="text" maxlength="20"
                   id="{{ $platform }}_latest_version" name="{{ $platform }}[latest_version]"
                   value="{{ old($platform.'.latest_version', $row->latest_version) }}"
                   placeholder="1.2.0" required>
            <div class="help">Xəbərdarlıqda “yeni versiya” kimi göstərilir.</div>
            @error($platform.'.latest_version')<div class="err">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="{{ $platform }}_store_url">Mağaza linki</label>
            <input class="input" type="text" maxlength="300"
                   id="{{ $platform }}_store_url" name="{{ $platform }}[store_url]"
                   value="{{ old($platform.'.store_url', $row->store_url) }}"
                   placeholder="https://">
            <div class="help">
              @if ($platform === 'ios')
                App Store linki: <code>https://apps.apple.com/az/app/id&lt;ID&gt;</code>.
                Tətbiq dərc olunandan sonra buranı mütləq doldurun — boş qalsa
                tətbiq axtarış səhifəsinə yönləndirir.
              @else
                Boş buraxsanız tətbiq öz daxilindəki Google Play linkindən istifadə edir.
              @endif
            </div>
            @error($platform.'.store_url')<div class="err">{{ $message }}</div>@enderror
          </div>

          @foreach (['az' => 'Azərbaycanca', 'ru' => 'Rusca', 'en' => 'İngiliscə'] as $lang => $langLabel)
            <div class="field">
              <label for="{{ $platform }}_message_{{ $lang }}">İzah mətni — {{ $langLabel }}</label>
              <textarea class="textarea" rows="2" maxlength="300"
                        id="{{ $platform }}_message_{{ $lang }}"
                        name="{{ $platform }}[message_{{ $lang }}]">{{ old($platform.'.message_'.$lang, $row->{'message_'.$lang}) }}</textarea>
              <div class="help">
                @if ($lang === 'az')
                  Ekranda başlığın altında görünür. Boş buraxsanız tətbiqin öz standart mətni işlənir.
                @else
                  Boş buraxsanız azərbaycanca mətn göstərilir.
                @endif
              </div>
              @error($platform.'.message_'.$lang)<div class="err">{{ $message }}</div>@enderror
            </div>
          @endforeach

          <div class="small muted">
            Son dəyişiklik: {{ $row->updated_at?->format('d.m.Y H:i') ?? '—' }}
          </div>

        </div>
      </div>
    @endforeach

    <div class="btn-row" style="max-width:760px">
      <button class="btn btn--primary" type="submit">Yadda saxla</button>
      <a class="btn btn--ghost" href="{{ route('admin.dashboard') }}">İmtina</a>
    </div>
  </form>

@endsection
