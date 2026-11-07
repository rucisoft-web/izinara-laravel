@extends('layouts.app')
@section('title', 'Item code')
@section('crumb', 'Item code')
@section('content')
<div class="page-head"><div><h1>Item code</h1><p>Item master dengan kelompok izinnya. Kelompok menentukan izin yang dibutuhkan saat item masuk proyek.</p></div></div>
<form class="filters"><div class="field"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Kode, nama atau HS"></div>
  <div class="field"><label>Kelompok</label><select name="template"><option value="">Semua</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected(request('template')==$t->id)>{{ $t->name }}</option>@endforeach</select></div><button class="btn ghost">Cari</button></form>
<div class="card flush"><table class="tbl"><thead><tr><th>Item code</th><th>Nama</th><th>Asal</th><th>Kategori</th><th>HS</th><th>Jenis produk</th><th>Kelompok izin</th></tr></thead><tbody>
@forelse($items as $i)<tr><td><code>{{ $i->code }}</code></td><td>{{ $i->name }}@if($i->non_halal) {!! badge('ditolak','Non-halal') !!}@endif</td><td><small>{{ $i->origin }}</small></td><td><small>{{ $i->category }} · {{ $i->subgroup }}</small></td><td>{{ $i->hs_code ?? '–' }}</td><td><small>{{ $i->product_kind }}</small></td><td><small>{{ $i->template?->name }}</small></td></tr>
@empty<tr><td colspan="7" class="empty">Tidak ada item.</td></tr>@endforelse
</tbody></table></div>
{{ $items->links('pagination::simple-default') }}
@if(auth()->user()->isManager())
<div class="card"><h3>Impor item code</h3>
<form method="post" action="{{ route('items.import') }}" enctype="multipart/form-data" class="row">@csrf
  <input type="file" name="file" accept=".csv,.txt"><button class="btn sm">Impor CSV</button>
  <span class="help">Kolom: ITEMCODE; ITEMNAME; Country; Category; Group; SubGroup; HS; Non-halal (ya/tidak); Jenis produk; Kode kelompok (mis. T_MEAT_HALAL).</span></form>
<form method="post" action="{{ route('items.import') }}" style="margin-top:10px" data-confirm="Muat 2.864 item code dari item master bawaan (Oktober 2026)?">@csrf<input type="hidden" name="bawaan" value="1"><button class="btn ghost sm">Muat item master bawaan lengkap (2.864 item)</button></form>
</div>
@endif
@endsection
