@extends('admin.layout')

@section('title', 'Yeni istifadəçi')
@section('subtitle', 'Panel üzərindən hesab aç')

@section('actions')
  <a href="{{ route('admin.users.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>
@endsection

@section('content')

  <div class="card" style="max-width:620px">
    <div class="card__head"><h2>Hesab məlumatları</h2></div>
    <div class="card__body">

      <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        <div class="field">
          <label for="phone">Telefon</label>
          <input class="input" type="tel" id="phone" name="phone" required maxlength="20"
                 value="{{ old('phone') }}" placeholder="+994501234567">
          <div class="help">Beynəlxalq format. «0501234567» yazsanız avtomatik «+994…» olur.</div>
        </div>

        <div class="field">
          <label for="full_name">Ad Soyad</label>
          <input class="input" type="text" id="full_name" name="full_name" required minlength="3" maxlength="255"
                 value="{{ old('full_name') }}">
        </div>

        <div class="field">
          <label for="city_id">Şəhər</label>
          <select class="select" id="city_id" name="city_id">
            <option value="">Seçilməyib</option>
            @foreach ($cities as $city)
              <option value="{{ $city->id }}" @selected((string) old('city_id') === (string) $city->id)>
                {{ $city->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="active_mode">Rejim</label>
          <select class="select" id="active_mode" name="active_mode" required>
            <option value="passenger" @selected(old('active_mode', 'passenger') === 'passenger')>Sərnişin</option>
            <option value="driver"    @selected(old('active_mode') === 'driver')>Sürücü</option>
          </select>
          <div class="help">«Sürücü» seçilsə, boş sürücü profili də açılır (sənədlər yüklənməmiş sayılır).</div>
        </div>

        <div class="field">
          <label for="gender">Cins</label>
          <select class="select" id="gender" name="gender" required>
            <option value="unspecified" @selected(old('gender', 'unspecified') === 'unspecified')>Göstərilməyib</option>
            <option value="male"        @selected(old('gender') === 'male')>Kişi</option>
            <option value="female"      @selected(old('gender') === 'female')>Qadın</option>
          </select>
        </div>

        <div class="field">
          <label for="birth_year">Doğum ili</label>
          <input class="input" type="number" id="birth_year" name="birth_year"
                 min="1930" max="{{ date('Y') - 16 }}" value="{{ old('birth_year') }}" placeholder="—">
        </div>

        <div class="field">
          <label for="language_code">Dil</label>
          <select class="select" id="language_code" name="language_code" required>
            <option value="az" @selected(old('language_code', 'az') === 'az')>AZ</option>
            <option value="en" @selected(old('language_code') === 'en')>EN</option>
            <option value="ru" @selected(old('language_code') === 'ru')>RU</option>
          </select>
        </div>

        <div class="field">
          <label class="check">
            <input type="checkbox" name="verified" value="1" @checked(old('verified', true))>
            Nömrə təsdiqlənmiş sayılsın
          </label>
          <div class="help">Firebase SMS axını keçilmir — hesab dərhal işlək olur.</div>
        </div>

        <div class="btn-row">
          <button class="btn btn--primary" type="submit">İstifadəçi yarat</button>
          <a class="btn btn--ghost" href="{{ route('admin.users.index') }}">İmtina</a>
        </div>
      </form>

    </div>
  </div>

@endsection
