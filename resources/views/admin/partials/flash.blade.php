@if (session('success'))
  <div class="flash flash--ok">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M20 6 9 17l-5-5"/>
    </svg>
    <div>{{ session('success') }}</div>
  </div>
@endif

@if ($errors->any())
  <div class="flash flash--err">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
      <circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/>
    </svg>
    <div>
      @if ($errors->count() === 1)
        {{ $errors->first() }}
      @else
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      @endif
    </div>
  </div>
@endif
