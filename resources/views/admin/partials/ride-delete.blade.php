{{-- Səfəri tamamilə silmək. Açıq rezervasiyası olan qarşıdakı səfər əvvəlcə ləğv edilməlidir. --}}
@if ($ride->mustBeCancelledBeforeDelete())
  <button class="btn btn--danger btn--sm" type="button" disabled
          title="Açıq rezervasiya var — əvvəlcə səfəri ləğv edin">Sil</button>
@else
  <form method="POST" action="{{ route('admin.rides.destroy', $ride) }}" class="inline"
        data-confirm="Səfər #{{ $ride->id }} tamamilə silinsin? Rezervasiyaları, yazışmaları və rəyləri də silinəcək. Geri qaytarmaq mümkün deyil.">
    @csrf @method('DELETE')
    <button class="btn btn--danger btn--sm" type="submit">Sil</button>
  </form>
@endif
