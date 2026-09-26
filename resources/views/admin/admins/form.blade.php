@extends('admin.layout')

@php $editing = (bool) $admin->exists; @endphp

@section('title', $editing ? 'Admini redaktə et' : 'Yeni admin')
@section('subtitle', $editing ? $admin->email : 'Panelə yeni giriş hesabı')

@section('actions')
  <a href="{{ route('admin.admins.index') }}" class="btn btn--ghost btn--sm">Siyahıya qayıt</a>
@endsection

@section('content')

  <div class="card" style="max-width:620px">
    <div class="card__head"><h2>Hesab məlumatları</h2></div>
    <div class="card__body">

      <form method="POST"
            action="{{ $editing ? route('admin.admins.update', $admin) : route('admin.admins.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="field">
          <label for="name">Ad Soyad</label>
          <input class="input" type="text" id="name" name="name" required maxlength="255"
                 value="{{ old('name', $admin->name) }}">
        </div>

        <div class="field">
          <label for="email">E-poçt</label>
          <input class="input" type="email" id="email" name="email" required maxlength="255"
                 value="{{ old('email', $admin->email) }}" autocomplete="off">
        </div>

        <div class="field">
          <label for="password">Şifrə</label>
          <input class="input" type="password" id="password" name="password"
                 autocomplete="new-password" {{ $editing ? '' : 'required' }}>
          <div class="help">
            Ən azı 10 simvol.
            @if ($editing) Boş buraxsanız şifrə dəyişməyəcək. @endif
          </div>
        </div>

        <div class="field">
          <label for="password_confirmation">Şifrənin təkrarı</label>
          <input class="input" type="password" id="password_confirmation" name="password_confirmation"
                 autocomplete="new-password" {{ $editing ? '' : 'required' }}>
        </div>

        <div class="field">
          <label for="role">Rol</label>
          <select class="select" id="role" name="role" required>
            @foreach (\App\Models\Admin::ROLE_LABELS as $key => $label)
              <option value="{{ $key }}" @selected(old('role', $admin->role) === $key)>{{ $label }}</option>
            @endforeach
          </select>
          <div class="help">Baş admin digər adminləri yarada və silə bilir.</div>
        </div>

        <div class="field">
          <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $admin->is_active ?? true))>
            Hesab aktivdir
          </label>
        </div>

        <div class="btn-row">
          <button class="btn btn--primary" type="submit">
            {{ $editing ? 'Yadda saxla' : 'Admin yarat' }}
          </button>
          <a class="btn btn--ghost" href="{{ route('admin.admins.index') }}">İmtina</a>
        </div>
      </form>

    </div>
  </div>

@endsection
