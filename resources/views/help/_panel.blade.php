@php $hk = \App\Http\Controllers\HelpController::topicForCurrent(); $ht = config('bantuan.topik.'.$hk); @endphp
<div class="help-panel" id="help-panel" aria-hidden="true">
  <div class="help-backdrop" data-help-close></div>
  <aside class="help-sheet" role="dialog" aria-label="Bantuan">
    <div class="help-sheet-h"><b>？ Bantuan · {{ $ht['judul'] }}</b><div class="sp"></div><button type="button" class="btn ghost sm" data-help-close>✕</button></div>
    <div class="help-sheet-b">
      @include('help._topic', ['t' => $ht])
      <hr>
      <p><a class="btn sm" href="{{ route('help.show', $hk) }}">Buka di halaman penuh</a> <a class="btn ghost sm" href="{{ route('help.index') }}">Semua topik panduan</a></p>
    </div>
  </aside>
</div>
