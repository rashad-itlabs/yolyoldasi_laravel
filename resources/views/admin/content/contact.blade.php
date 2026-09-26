@extends('admin.layout')

@section('title', 'Əlaqə məlumatları')
@section('subtitle', 'Saytın altlığı və əlaqə səhifəsi bu dəyərlərdən oxuyur')

@section('actions')
  <a href="{{ route('contact') }}" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">Səhifəyə bax</a>
@endsection

@section('content')

  <form method="POST" action="{{ route('admin.contact.update') }}">
    @csrf

    @php
      $groups = [
        'contact' => ['Əlaqə', 'E-poçt ünvanları və ünvan məlumatı'],
        'site'    => ['Sayt mətnləri', 'Altlıqda göstərilən qısa təsvir'],
        'social'  => ['Sosial şəbəkələr', 'Boş buraxılan ikon saytda gizlədilir'],
      ];
    @endphp

    @foreach ($groups as $groupKey => [$groupTitle, $groupHint])
      <div class="card" style="max-width:760px">
        <div class="card__head">
          <h2>{{ $groupTitle }}</h2>
          <span class="muted">{{ $groupHint }}</span>
        </div>
        <div class="card__body">
          @foreach ($fields as $key => [$label, $group, $rules, $hint])
            @continue($group !== $groupKey)
            @php
              $name = str_replace('.', '_', $key);
              $isLong = in_array($key, ['contact.response_time', 'contact.urgent_note', 'site.footer_about'], true);
            @endphp

            <div class="field">
              <label for="{{ $name }}">{{ $label }}</label>

              @if ($isLong)
                <textarea class="textarea" id="{{ $name }}" name="{{ $name }}"
                          rows="2">{{ old($name, $values[$key] ?? '') }}</textarea>
              @else
                <input class="input" type="text" id="{{ $name }}" name="{{ $name }}"
                       value="{{ old($name, $values[$key] ?? '') }}"
                       placeholder="{{ str_contains($key, 'social.') ? 'https://' : '' }}">
              @endif

              <div class="help">{{ $hint }}</div>
              @error($name)<div class="err">{{ $message }}</div>@enderror
            </div>
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="btn-row" style="max-width:760px">
      <button class="btn btn--primary" type="submit">Yadda saxla</button>
      <a class="btn btn--ghost" href="{{ route('admin.dashboard') }}">İmtina</a>
    </div>
  </form>

@endsection
