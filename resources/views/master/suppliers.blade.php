@extends('layouts.app')
@section('title', 'Pemasok')
@section('crumb', 'Pemasok & unit usaha asal')
@section('content')
<div class="page-head"><div><h1>Pemasok & unit usaha asal</h1><p>Eksportir luar negeri, establishment (unit usaha asal yang perlu disetujui Kementan) dan pemasok/unit lokal. Hanya Purchasing yang berhubungan langsung dengan prinsipal.</p></div></div>
<div class="card flush"><table class="tbl"><thead><tr><th>Nama</th><th>Jenis</th><th>Negara</th><th>No. establishment</th><th>Induk</th><th class="num">Proyek</th></tr></thead><tbody>
@foreach($suppliers as $s)<tr><td>{{ $s->name }}</td><td>{{ ['exporter'=>'Eksportir','establishment'=>'Unit usaha asal','local'=>'Lokal'][$s->kind] }}</td><td>{{ $s->country }}</td><td>{{ $s->establishment_no ?? '–' }}</td><td><small>{{ $s->parent?->name }}</small></td><td class="num">{{ $s->projects_count }}</td></tr>@endforeach
</tbody></table></div>
@if(auth()->user()->isManager())
<details class="box"><summary>＋ Tambah pemasok</summary><div><form method="post" action="{{ route('suppliers.store') }}">@csrf<div class="grid g3">
  <div class="field"><label>Nama</label><input type="text" name="name" required></div>
  <div class="field"><label>Jenis</label><select name="kind"><option value="exporter">Eksportir</option><option value="establishment">Unit usaha asal</option><option value="local">Lokal</option></select></div>
  <div class="field"><label>Negara</label><input type="text" name="country"></div>
  <div class="field"><label>No. establishment</label><input type="text" name="establishment_no"></div>
  <div class="field"><label>Induk (eksportir)</label><select name="parent_id"><option value="">–</option>@foreach($suppliers->where('kind','exporter') as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
</div><button class="btn sm">Simpan</button></form></div></details>
@endif
@endsection
