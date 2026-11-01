@extends('layouts.app')
@section('title', 'Proyeksi')
@section('crumb', 'Proyeksi')
@section('content')
<div class="page-head"><div><h1>Proyeksi perizinan</h1><p>Perkiraan tanggal izin terbit, kebutuhan perpanjangan dan beban kerja, berdasarkan realisasi proyek yang sudah selesai.</p></div></div>

<form class="card filters" style="align-items:end">
  <div class="field"><label>Skenario: durasi tambahan (%)</label><input type="number" name="tambahan" value="{{ round($extra*100) }}" step="5" min="-50" max="200" style="width:120px"></div>
  <div class="field"><label>Tambah PIC per bagian</label><input type="number" name="tambah_pic" value="{{ $extraCap }}" min="0" max="10" style="width:120px"></div>
  <button class="btn">Hitung skenario</button>
  <div class="help" style="flex:1">Faktor realisasi dari {{ $factors['overall']['n'] }} paket izin selesai: median <b>{{ $factors['overall']['p50'] }}×</b> rencana, P80 <b>{{ $factors['overall']['p80'] }}×</b>. Skenario menambah durasi semua sisa kegiatan (mis. antrean instansi) dan kapasitas tiap bagian.</div>
</form>

<div class="card flush"><div class="card-h"><h2>Proyeksi tanggal semua izin terbit per proyek</h2></div><div class="scroll-x">
<table class="tbl"><thead><tr><th>Proyek</th><th>Status</th><th>Tenggat</th><th>Baseline</th><th>Rencana kini</th><th>Proyeksi P50</th><th>Proyeksi P80</th><th class="num">Selisih P50</th><th>Risiko</th></tr></thead><tbody>
@foreach($portfolio as $f)
<tr><td><a href="{{ route('projects.show', $f['project']) }}"><b>{{ $f['project']->code }}</b></a><br><small>{{ $f['project']->name }}</small></td><td>{!! badge($f['project']->status, $f['project']->statusLabel()) !!}</td>
  <td>{{ tgl($f['target']) }}</td><td>{{ tgl($f['baseline']) }}</td><td>{{ tgl($f['plan']) }}</td><td><b>{{ tgl($f['p50']) }}</b></td><td>{{ tgl($f['p80']) }}</td>
  <td class="num" style="color:{{ $f['late_p50']>0?'var(--bad)':'var(--ok)' }}">{{ $f['late_p50']>0?'+':'' }}{{ $f['late_p50'] }} h</td><td>{!! badge($f['risk'], ucfirst($f['risk'])) !!}</td></tr>
@endforeach
</tbody></table></div></div>

<div class="grid g2">
  <div class="card"><div class="card-h"><h3>Proyeksi bulanan 12 bulan</h3></div><div class="chart-box tall"><canvas id="cMonth"></canvas></div>
    <p class="help">Izin terbit = paket izin terbuka pada proyek yang diproyeksi selesai (P50) di bulan itu. Mulai perpanjangan = tanggal habis − waktu proses − 30 hari cadangan.</p></div>
  <div class="card"><div class="card-h"><h3>Beban kerja skenario (% kapasitas bagian)</h3></div><div class="chart-box tall"><canvas id="cUtil"></canvas></div>
    <p class="help">Garis 100% = kapasitas penuh. Di atasnya, kegiatan berisiko mundur kecuali ada tambahan PIC atau prioritas diatur ulang.</p></div>
</div>

<div class="grid g2">
<div class="card flush"><div class="card-h"><h2>Faktor realisasi per jenis izin</h2></div>
<table class="tbl small-table"><thead><tr><th>Jenis izin</th><th class="num">Sampel</th><th class="num">P50</th><th class="num">P80</th><th class="num">Rencana (hari)</th><th class="num">Proyeksi P50 (hari)</th></tr></thead><tbody>
@foreach($factors['types'] as $tid => $f)@php $t = $types[$tid] ?? null; @endphp @if($t)
<tr><td>{{ $t->name }}</td><td class="num">{{ $f['n'] }}</td><td class="num" style="color:{{ $f['p50']>1.15?'var(--bad)':'inherit' }}">{{ $f['p50'] }}×</td><td class="num">{{ $f['p80'] }}×</td>
  <td class="num">{{ $t->standardDays() ?: $t->lead_days }}</td><td class="num">{{ (int) round(($t->standardDays() ?: $t->lead_days) * $f['p50']) }}</td></tr>@endif
@endforeach
</tbody></table></div>
<div class="card flush"><div class="card-h"><h2>Perpanjangan izin yang perlu dimulai</h2></div>
<table class="tbl small-table"><thead><tr><th>Izin</th><th>Habis</th><th>Mulai paling lambat</th><th></th></tr></thead><tbody>
@forelse($monthly['renewals'] as $r)
<tr><td>{{ $r['license']->permitType->name }}<br><small>{{ $r['license']->number }}</small></td><td>{{ tgl($r['license']->expires_at) }}</td>
  <td>{!! $r['start_by']->lt(today()) ? badge('kedaluwarsa', tgl($r['start_by']).' (terlewat)') : tgl($r['start_by']) !!}</td>
  <td>@if(auth()->user()->isManager())<form method="post" action="{{ route('licenses.renew', $r['license']) }}">@csrf<button class="btn ghost sm">Buat proyek</button></form>@endif</td></tr>
@empty <tr><td colspan="4" class="empty">Tidak ada dalam 12 bulan.</td></tr> @endforelse
</tbody></table></div>
</div>
@endsection
@push('scripts')<script>
IZ.chart('cMonth', {type: 'bar', data: {labels: @json($monthly['labels']), datasets: [
  {label: 'Izin terbit (proyeksi)', data: @json($monthly['issued']), backgroundColor: '#0f2c59'},
  {label: 'Izin habis masa berlaku', data: @json($monthly['expiring']), backgroundColor: '#c0392b'},
  {label: 'Mulai perpanjangan', data: @json($monthly['renewalStarts']), backgroundColor: '#d98e04'},
  {label: 'Proyek baru dimulai', data: @json($monthly['newProjects']), type: 'line', borderColor: '#2563a8', backgroundColor: '#2563a8'}]},
  options: {scales: {y: {ticks: {precision: 0}}}}});
IZ.chart('cUtil', {type: 'line', data: {labels: {!! json_encode(collect($workload['weeks'])->map(fn($w)=>tgl($w,'d M'))) !!},
  datasets: {!! json_encode($workload['parties']->values()->map(fn($r)=>['label'=>$r['party']->shortName(),'data'=>$r['util']])) !!}.map((d,i)=>Object.assign(d,{borderColor:IZ.colors[i],backgroundColor:IZ.colors[i],tension:.3}))
  .concat([{label:'Kapasitas 100%',data:Array(12).fill(100),borderColor:'#999',borderDash:[6,4],pointRadius:0}])},
  options: {scales: {y: {title: {display: true, text: '% kapasitas'}}}}});
</script>@endpush
