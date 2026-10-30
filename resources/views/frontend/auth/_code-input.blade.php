{{-- 6 rəqəmli kod sahəsi. Tək sahədir: telefonun "koddan doldur" təklifi (one-time-code) və yapışdırma belə işləyir. --}}
<div class="field">
  <label for="code">Təsdiq kodu</label>
  <input class="input" type="text" id="code" name="code" value="{{ old('code') }}"
         inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
         placeholder="••••••" required autofocus
         style="font-size:26px;font-weight:800;letter-spacing:.5em;text-align:center;padding-left:.5em">
</div>
