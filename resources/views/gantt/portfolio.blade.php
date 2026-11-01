@extends('layouts.app')
@section('title', 'Gantt portofolio')
@section('crumb', 'Gantt portofolio')
@section('content')
<div class="page-head"><div><h1>Gantt portofolio</h1><p>Semua proyek perizinan dan milestone-nya dalam satu linimasa. Warna batang = kesehatan proyek.</p></div></div>
<form class="filters">
  <div class="field"><label>Kelompok</label><select name="kelompok"><option value="">Semua</option>@foreach($groups as $k=>$v)<option value="{{ $k }}" @selected(request('kelompok')===$k)>{{ $v }}</option>@endforeach</select></div>
  <label class="check"><input type="checkbox" name="semua" value="1" @checked(request('semua'))> Termasuk proyek selesai</label>
  <button class="btn ghost">Terapkan</button>
</form>
<div class="card">
  <div class="gantt-toolbar">
    <button class="btn sm ghost" data-gantt-mode="Week">Minggu</button><button class="btn sm" data-gantt-mode="Month">Bulan</button>
    <div style="flex:1"></div>
    <div class="legend"><span><i style="background:var(--brand)"></i>Sesuai jadwal</span><span><i style="background:#c88a00"></i>Perlu perhatian</span><span><i style="background:var(--bad)"></i>Terlambat/terhambat</span><span><i style="background:var(--accent)"></i>Milestone</span></div>
  </div>
  <div class="gantt-wrap"><div id="gantt"></div></div>
</div>
@endsection
@push('scripts')<script>IZ.gantt('#gantt', @json($rows), {mode: 'Month', editable: false});</script>@endpush
