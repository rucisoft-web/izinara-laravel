@extends('layouts.app')
@section('title', 'Peraturan')
@section('crumb', 'Peraturan perundang-undangan')
@section('content')
<div class="page-head"><div><h1>Peraturan perundang-undangan</h1><p>Dasar hukum yang dirujuk setiap jenis izin, langkah standar dan kegiatan proyek. Peraturan yang dicabut bisa diarahkan ke penggantinya; referensi di kegiatan yang belum selesai ikut diganti.</p></div></div>
<div class="alert info">Status per {{ tgl($regulations->max('checked_at')) }}. Peraturan berstatus <b>perlu konfirmasi</b> mohon dicek ke JDIH instansi penerbit (tautan JDIH di tiap baris) sebelum dipakai sebagai acuan resmi. Lartas per kode HS selalu dicek ulang di INSW untuk setiap pengapalan.</div>
<div class="pill-nav" style="margin-bottom:12px"><a href="?" class="{{ !request('status')?'on':'' }}">Semua</a>@foreach(\App\Models\Regulation::STATUSES as $k=>$v)<a href="?status={{ $k }}" class="{{ request('status')===$k?'on':'' }}">{{ $v }}</a>@endforeach</div>
<div class="card flush"><div class="scroll-x"><table class="tbl">
<thead><tr><th>Peraturan</th><th>Tentang</th><th>Penerbit</th><th>Status</th><th class="num">Jenis izin</th><th class="num">Langkah</th><th class="num">Kegiatan</th><th></th></tr></thead><tbody>
@foreach($regulations as $r)
<tr id="reg-{{ $r->id }}"><td><b>{{ $r->name }}</b><br><small><code>{{ $r->code }}</code></small></td><td>{{ $r->title }}@if($r->note)<br><small class="muted">{{ $r->note }}</small>@endif @if($r->replacedBy)<br><small>Diganti: <a href="#reg-{{ $r->replaced_by_id }}">{{ $r->replacedBy->name }}</a></small>@endif</td>
  <td><small>{{ $r->issuer }}<br>{{ $r->year }}</small></td><td>{!! badge($r->status, $r->statusLabel()) !!}<br><small class="muted">dicek {{ tgl($r->checked_at) }}</small></td>
  <td class="num">{{ $r->permit_types_count }}</td><td class="num">{{ $r->steps_count }}</td><td class="num">{{ $r->tasks_count }}</td>
  <td>@if($r->url)<a href="{{ $r->url }}" target="_blank" rel="noopener" class="btn ghost sm">JDIH ↗</a>@endif
    @if(auth()->user()->isManager())
    <details><summary class="btn ghost sm" style="margin-top:4px">Ubah</summary>
      <form method="post" action="{{ route('regulations.update', $r) }}" style="min-width:320px;margin-top:6px">@csrf @method('PUT')
        <input type="hidden" name="code" value="{{ $r->code }}">
        <div class="field"><label>Nomor</label><input type="text" name="name" value="{{ $r->name }}"></div>
        <div class="field"><label>Tentang</label><input type="text" name="title" value="{{ $r->title }}"></div>
        <div class="field"><label>Penerbit</label><input type="text" name="issuer" value="{{ $r->issuer }}"></div>
        <div class="field"><label>Tahun</label><input type="text" name="year" value="{{ $r->year }}"></div>
        <div class="field"><label>Status</label><select name="status">@foreach(\App\Models\Regulation::STATUSES as $k=>$v)<option value="{{ $k }}" @selected($r->status===$k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label>Diganti oleh</label><select name="replaced_by_id"><option value="">–</option>@foreach($all as $o)@if($o->id!==$r->id)<option value="{{ $o->id }}" @selected($r->replaced_by_id===$o->id)>{{ $o->name }}</option>@endif @endforeach</select></div>
        <label class="check"><input type="checkbox" name="replace_refs" value="1"> Ganti referensi di kegiatan terbuka, langkah & jenis izin ke peraturan pengganti</label>
        <div class="field"><label>Catatan</label><textarea name="note">{{ $r->note }}</textarea></div>
        <div class="field"><label>Tautan</label><input type="url" name="url" value="{{ $r->url }}"></div>
        <div class="field"><label>Tanggal dicek</label><input type="date" name="checked_at" value="{{ today()->toDateString() }}"></div>
        <button class="btn sm">Simpan</button></form></details>
    @endif</td></tr>
@endforeach
</tbody></table></div></div>
@if(auth()->user()->isManager())
<details class="box"><summary>＋ Tambah peraturan baru</summary><div>
<form method="post" action="{{ route('regulations.store') }}">@csrf<div class="grid g3">
  <div class="field"><label>Kode unik</label><input type="text" name="code" required placeholder="mis. PERBPOM_2026_X"></div>
  <div class="field"><label>Nomor</label><input type="text" name="name" required placeholder="Per BPOM 5/2026"></div>
  <div class="field"><label>Tentang</label><input type="text" name="title" required></div>
  <div class="field"><label>Penerbit</label><input type="text" name="issuer"></div>
  <div class="field"><label>Tahun</label><input type="text" name="year"></div>
  <div class="field"><label>Status</label><select name="status">@foreach(\App\Models\Regulation::STATUSES as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Tautan</label><input type="url" name="url"></div>
  <div class="field"><label>Tanggal dicek</label><input type="date" name="checked_at" value="{{ today()->toDateString() }}"></div>
  <div class="field"><label>Catatan</label><input type="text" name="note"></div>
</div><button class="btn sm">Tambah</button></form></div></details>
@endif
@endsection
