@extends('layouts.app')
@section('title', 'Register izin')
@section('crumb', 'Register izin')
@section('content')
<div class="page-head"><div><h1>Register izin</h1><p>Izin yang dimiliki Indoguna. Izin yang masih berlaku pada ETA/tenggat dipakai ulang otomatis saat menyusun proyek baru.</p></div></div>
<form class="filters">
  <div class="field"><label>Jenis izin</label><select name="jenis"><option value="">Semua</option>@foreach($types as $t)<option value="{{ $t->id }}" @selected(request('jenis')==$t->id)>{{ $t->name }}</option>@endforeach</select></div>
  <div class="field"><label>Status</label><select name="status"><option value="">Semua</option><option value="aktif" @selected(request('status')==='aktif')>Aktif</option><option value="segera_habis" @selected(request('status')==='segera_habis')>Segera habis (≤90 hari)</option><option value="kedaluwarsa" @selected(request('status')==='kedaluwarsa')>Kedaluwarsa</option></select></div>
  <button class="btn ghost">Terapkan</button>
</form>
<div class="card flush"><div class="scroll-x"><table class="tbl">
<thead><tr><th>Jenis izin</th><th>Nomor</th><th>Cakupan</th><th>Terbit</th><th>Berlaku s/d</th><th>Kuota</th><th>Asal proyek</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($licenses as $l)
<tr><td><b>{{ $l->permitType->name }}</b><br><small>{{ $l->permitType->authority }}</small></td><td><code>{{ $l->number }}</code></td>
  <td><small>{{ $l->supplier?->name ?? ($l->items->isNotEmpty() ? $l->items->pluck('code')->implode(', ') : $l->permitType->scopeLabel()) }}</small></td>
  <td>{{ tgl($l->issued_at) }}</td><td>{{ $l->expires_at ? tgl($l->expires_at) : 'Tanpa batas' }}@if($l->daysLeft()!==null)<br><small>{{ $l->daysLeft() }} hari</small>@endif</td>
  <td>{{ $l->quota_kg ? number_format($l->quota_kg,0,',','.').' kg' : '–' }}</td>
  <td>@if($l->project)<a href="{{ route('projects.show', $l->project) }}">{{ $l->project->code }}</a>@else <small>–</small>@endif</td>
  <td>{!! badge($l->state(), ['aktif'=>'Aktif','segera_habis'=>'Segera habis','kedaluwarsa'=>'Kedaluwarsa'][$l->state()]) !!}</td>
  <td>@if($l->state()!=='aktif' && auth()->user()->isManager())<form method="post" action="{{ route('licenses.renew', $l) }}">@csrf<button class="btn ghost sm">Proyek perpanjangan</button></form>@endif</td></tr>
@empty <tr><td colspan="9" class="empty">Belum ada izin.</td></tr> @endforelse
</tbody></table></div></div>
@if(auth()->user()->role !== 'viewer')
<details class="box"><summary>＋ Catat izin yang sudah dimiliki</summary><div>
<form method="post" action="{{ route('licenses.store') }}">@csrf
<div class="grid g3">
  <div class="field"><label>Jenis izin</label><select name="permit_type_id" required>@foreach($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
  <div class="field"><label>Nomor</label><input type="text" name="number" required></div>
  <div class="field"><label>Pemasok / unit usaha (izin per pemasok)</label><select name="supplier_id"><option value="">–</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
  <div class="field"><label>Terbit</label><input type="date" name="issued_at"></div>
  <div class="field"><label>Berlaku s/d</label><input type="date" name="expires_at"></div>
  <div class="field"><label>Kuota (kg)</label><input type="number" name="quota_kg"></div>
  <div class="field"><label>Item code (izin per barang)</label><select name="items[]" multiple>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->code }} {{ $i->name }}</option>@endforeach</select></div>
  <div class="field"><label>Catatan</label><input type="text" name="note"></div>
</div><button class="btn sm">Simpan</button></form></div></details>
@endif
@endsection
