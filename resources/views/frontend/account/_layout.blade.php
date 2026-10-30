{{--
  Hesab bölməsinin yan menyusu və üslubu. $active: profile | rides | edit.

  Üslub burada, style.css-də yox: layout style.css-i versiyasız qoşur və
  brauzerin keşindəki köhnə fayl yeni sinifləri tanımazdı.
--}}
<style>
  .acct{ display:grid; grid-template-columns:250px 1fr; gap:clamp(24px,4vw,48px); align-items:start; }
  .acct-nav{ background:#fff; border:1px solid var(--line); border-radius:var(--r-xl); padding:10px; position:sticky; top:96px; }
  .acct-nav a, .acct-nav button{ display:flex; width:100%; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; font-weight:700; font-size:15px; color:var(--muted); text-align:left; }
  .acct-nav a:hover, .acct-nav button:hover{ background:var(--mist); color:var(--ink); }
  .acct-nav a.is-active{ background:var(--brand-soft); color:#06694F; }
  .acct-nav svg{ width:18px; height:18px; flex:none; }
  .acct-nav hr{ border:0; border-top:1px solid var(--line); margin:8px 4px; }
  .acct-main > * + *{ margin-top:22px; }
  .kv{ display:grid; grid-template-columns:150px 1fr; gap:14px 20px; font-size:15.5px; margin:0; }
  .kv dt{ color:var(--muted); font-weight:600; }
  .kv dd{ margin:0; font-weight:600; overflow-wrap:anywhere; }
  .acct-list{ display:grid; gap:12px; }
  .acct-row{ display:flex; justify-content:space-between; align-items:center; gap:16px; padding:16px 18px; border:1px solid var(--line); border-radius:18px; background:#fff; }
  .acct-row b{ display:block; font-size:16px; }
  .acct-row small{ color:var(--muted); font-size:14px; }
  .pill{ display:inline-block; padding:5px 12px; border-radius:999px; font-size:13px; font-weight:700; background:var(--mist); color:var(--muted); white-space:nowrap; }
  .pill--ok{ background:var(--brand-soft); color:#06694F; }
  .pill--warn{ background:#FFF4E5; color:#8A5200; }
  .pill--err{ background:#FEF1F1; color:#B02A2E; }
  .acct-empty{ text-align:center; padding:28px 10px; color:var(--muted); }
  .acct-empty b{ display:block; color:var(--ink); font-size:17px; margin-bottom:6px; }
  @media (max-width:900px){
    .acct{ grid-template-columns:1fr; }
    .acct-nav{ position:static; display:flex; flex-wrap:wrap; gap:4px; }
    .acct-nav a, .acct-nav button{ width:auto; }
    .acct-nav hr{ display:none; }
    .kv{ grid-template-columns:1fr; gap:4px; }
    .kv dd{ margin-bottom:10px; }
    .acct-row{ flex-direction:column; align-items:flex-start; }
  }
</style>

<nav class="acct-nav" aria-label="Hesab">
  <a href="{{ route('account') }}" class="{{ $active === 'profile' ? 'is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="4"/><path d="M4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1"/></svg>
    Profil
  </a>
  <a href="{{ route('account.rides') }}" class="{{ $active === 'rides' ? 'is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17h14M6.5 17l-1.2-5.4A2 2 0 017.2 9h9.6a2 2 0 011.9 2.6L17.5 17M4 17v2M20 17v2M8 9l1-3h6l1 3"/></svg>
    Səfərlərim
  </a>
  <a href="{{ route('account.edit') }}" class="{{ $active === 'edit' ? 'is-active' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
    Redaktə və şifrə
  </a>
  <hr>
  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      Çıxış
    </button>
  </form>
</nav>
