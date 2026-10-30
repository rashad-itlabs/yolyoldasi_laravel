@extends('layout.app')

@section('meta_title', 'Profili redaktə et | Yol Yoldaşı')

@section('content')
<main>

@include('frontend.auth._head', [
  'crumb' => 'Hesabım',
  'title' => 'Profili <span class="grad-text">redaktə et</span>',
  'lead'  => null,
])

<section class="section section--tight">
  <div class="container acct">

    @include('frontend.account._layout', ['active' => 'edit'])

    <div class="acct-main">

      {{-- Profil --}}
      <div class="form-card">
        @include('frontend.auth._alerts')

        <form method="POST" action="{{ route('account.update') }}" novalidate>
          @csrf @method('PUT')
          <div class="form-card__head"><h2>Profil</h2></div>

          <div class="field">
            <label for="full_name">Ad, soyad</label>
            <input class="input" type="text" id="full_name" name="full_name"
                   value="{{ old('full_name', $user->full_name) }}" autocomplete="name" maxlength="100" required>
          </div>

          <div class="field">
            <label for="city_id">Şəhər</label>
            <select class="select" id="city_id" name="city_id">
              <option value="">Seçilməyib</option>
              @foreach ($cities as $city)
                <option value="{{ $city->id }}" @selected((string) old('city_id', $user->city_id) === (string) $city->id)>{{ $city->name }}</option>
              @endforeach
            </select>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="field">
              <label for="gender">Cins</label>
              <select class="select" id="gender" name="gender">
                @foreach (['unspecified' => 'Göstərilməyib', 'male' => 'Kişi', 'female' => 'Qadın'] as $value => $label)
                  <option value="{{ $value }}" @selected(old('gender', $user->gender) === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label for="birth_year">Doğum ili</label>
              <input class="input" type="text" id="birth_year" name="birth_year" inputmode="numeric" maxlength="4"
                     value="{{ old('birth_year', $user->birth_year) }}" placeholder="1995">
            </div>
          </div>

          <div class="field">
            <label for="about">Haqqında</label>
            <textarea class="textarea" id="about" name="about" maxlength="300"
                      placeholder="Məsələn: həftəsonları Bakı–Gəncə yolundayam.">{{ old('about', $user->about) }}</textarea>
          </div>

          <button type="submit" class="btn btn--brand btn--lg">Yadda saxla</button>
        </form>
      </div>

      {{-- Şifrə — ayrıca xəta çantası, mesajlar yuxarıdakı forma ilə qarışmasın. --}}
      <div class="form-card" id="sifre">
        @include('frontend.auth._alerts', ['bag' => 'password'])

        <form method="POST" action="{{ route('account.password') }}" novalidate>
          @csrf @method('PUT')
          <div class="form-card__head"><h2>Şifrəni dəyiş</h2></div>

          <div class="field">
            <label for="current_password">Hazırkı şifrə</label>
            <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
          </div>

          <div class="field">
            <label for="new_password">Yeni şifrə</label>
            <input class="input" type="password" id="new_password" name="password" autocomplete="new-password" minlength="8" required>
            <p class="field__hint">Ən az 8 simvol.</p>
          </div>

          <div class="field">
            <label for="new_password_confirmation">Yeni şifrənin təkrarı</label>
            <input class="input" type="password" id="new_password_confirmation" name="password_confirmation" autocomplete="new-password" required>
          </div>

          <button type="submit" class="btn btn--ghost btn--lg">Şifrəni dəyiş</button>
        </form>
      </div>

    </div>
  </div>
</section>
</main>
@endsection
