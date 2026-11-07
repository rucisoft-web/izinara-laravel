@extends('layouts.app')
@section('title', 'Beban kerja')
@section('crumb', 'Beban kerja PIC')
@section('content')
@php
  $cls = fn($u) => $u <= 0 ? 'h0' : ($u < 50 ? 'h1' : ($u < 80 ? 'h2' : ($u < 100 ? 'h3' : ($u < 130 ? 'h4' : 'h5'))));
@endphp
<div class="page-head"><div><h1>Beban kerja PIC</h1><p>Hari-orang per minggu dari kegiatan terjadwal dibanding kapasitas. Kegiatan aktif dihitung {{ \App\Services\Forecaster::EFFORT_ACTIVE*100 }}% dari durasinya, kegiatan menunggu instansi {{ \App\Services\Forecaster::EFFORT_WAITING*100 }}% (pemantauan).</p></div>
<div class="sp"></div><div class="pill-nav">@foreach([8,12,26] as $w)<a href="?minggu={{ $w }}" class="{{ $weeks==$w?'on':'' }}">{{ $w }} minggu</a>@endforeach</div></div>

<div class="card flush"><div class="card-h"><h2>Per bagian (% kapasitas)</h2></div><div class="scroll-x">
<table class="tbl heat"><thead><tr><th>Bagian</th><th class="num">Kapasitas/mgg</th>@foreach($data['weeks'] as $w)<th class="num">{{ tgl($w,'d M') }}</th>@endforeach</tr></thead><tbody>
@foreach($data['parties'] as $r)
<tr><td><b>{{ $r['party']->shortName() }}</b><br><small>{{ $r['party']->users->where('active',true)->count() }} orang</small></td><td class="num">{{ $r['capacity'] }} h</td>
@foreach($r['util'] as $i => $u)<td class="h {{ $cls($u) }}" title="{{ $r['load'][$i] }} hari-orang">{{ $u }}%</td>@endforeach</tr>
@endforeach
</tbody></table></div></div>

<div class="card flush"><div class="card-h"><h2>Per orang (% kapasitas)</h2></div><div class="scroll-x">
<table class="tbl heat"><thead><tr><th>PIC</th><th class="num">Kapasitas</th>@foreach($data['weeks'] as $w)<th class="num">{{ tgl($w,'d M') }}</th>@endforeach</tr></thead><tbody>
@foreach($data['users'] as $r)
<tr><td><b>{{ $r['user']->name }}</b><br><small>{{ $r['user']->party?->shortName() ?? $r['user']->roleLabel() }}</small></td><td class="num">{{ $r['capacity'] }} h</td>
@foreach($r['util'] as $i => $u)<td class="h {{ $cls($u) }}" title="{{ $r['load'][$i] }} hari-orang">{{ $u }}%</td>@endforeach</tr>
@endforeach
@if(array_sum($data['unassigned']) > 0)
<tr><td><b>Belum ada PIC</b></td><td></td>@foreach($data['unassigned'] as $l)<td class="h {{ $l>0?'h4':'h0' }}">{{ $l }} h</td>@endforeach</tr>
@endif
</tbody></table></div></div>
<div class="card"><div class="card-h"><h3>Grafik beban per bagian (hari-orang)</h3></div><div class="chart-box tall"><canvas id="cLoad"></canvas></div></div>
<p class="help">Legenda: <span class="tag h1">&lt;50%</span> <span class="tag h2">50–79%</span> <span class="tag h3">80–99%</span> <span class="tag h4">100–129%</span> <span class="tag h5">≥130% (kelebihan beban)</span>. Kapasitas orang diatur di menu Pengguna.</p>
@endsection
@push('scripts')<script>
IZ.chart('cLoad', {type: 'bar', data: {labels: {!! json_encode(collect($data['weeks'])->map(fn($w)=>tgl($w,'d M'))) !!},
  datasets: {!! json_encode($data['parties']->values()->map(fn($r,$i)=>['label'=>$r['party']->shortName(),'data'=>$r['load'],'stack'=>'a'])) !!} .map((d,i)=>Object.assign(d,{backgroundColor:IZ.colors[i]}))},
  options: {scales: {x: {stacked: true}, y: {stacked: true, title: {display: true, text: 'hari-orang'}}}}});
</script>@endpush
