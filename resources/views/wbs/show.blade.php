@extends('layouts.app')
@section('title', $tpl->name)
@section('crumb')<a href="{{ route('wbs-templates.index') }}">Template WBS</a> / {{ $tpl->name }} @endsection
@section('content')
@php $byKey = collect($tpl->payload)->keyBy('key'); @endphp
<div class="page-head"><div><h1>{{ $tpl->name }}</h1><p>{{ $tpl->groupLabel() }} · {{ $tpl->taskCount() }} kegiatan · ±{{ $tpl->totalDays() }} hari · dipakai {{ $tpl->times_used }}×</p></div>
<div class="sp"></div>@if(auth()->user()->isManager())<a class="btn" href="{{ route('projects.create', ['wbs' => $tpl->id]) }}">⧉ Salin ke proyek baru</a>@endif</div>
<div class="card flush"><table class="tbl">
<thead><tr><th>WBS</th><th>Kegiatan</th><th>Bagian</th><th class="num">Durasi</th><th class="num">Mulai hari ke-</th><th>Pendahulu</th><th>Peraturan</th><th>Dokumen wajib</th></tr></thead><tbody>
@foreach($tpl->payload as $n)
<tr class="{{ $n['kind']==='package'?'pkg':($n['kind']==='milestone'?'ms':'') }}"><td>{{ $n['wbs'] }}</td><td class="{{ $n['kind']==='task'?'wbs-indent-1':'' }}">{{ $n['kind']==='milestone'?'◆ ':'' }}{{ $n['name'] }}</td>
  <td><small>{{ $n['party'] }}</small></td><td class="num">{{ $n['kind']==='milestone' ? '–' : $n['duration_days'] }}</td><td class="num">{{ $n['offset'] }}</td>
  <td><small>{{ collect($n['deps'])->map(fn($k)=>$byKey[$k]['wbs'] ?? '')->implode(', ') }}</small></td>
  <td><small>{{ collect($n['regulations'])->pluck('code')->implode(', ') }}</small></td><td><small>{{ collect($n['docs'])->pluck('name')->implode('; ') }}</small></td></tr>
@endforeach
</tbody></table></div>
@if(auth()->user()->isManager())
<div class="grid g2">
<form method="post" action="{{ route('wbs-templates.update', $tpl) }}" class="card">@csrf @method('PUT')<h3>Ubah keterangan</h3>
  <div class="field"><label>Nama</label><input type="text" name="name" value="{{ $tpl->name }}"></div>
  <div class="field"><label>Kelompok</label><select name="food_group"><option value="">–</option>@foreach($groups as $k=>$v)<option value="{{ $k }}" @selected($tpl->food_group===$k)>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Keterangan</label><textarea name="description">{{ $tpl->description }}</textarea></div><button class="btn sm">Simpan</button></form>
<div class="card"><h3>Mengubah isi template</h3><p class="help">Isi template diambil dari proyek. Untuk mengubah kegiatan/durasi: buat proyek dari template ini, ubah WBS-nya, lalu "Simpan sebagai template" dengan nama baru.</p>
  <form method="post" action="{{ route('wbs-templates.destroy', $tpl) }}" data-confirm="Hapus template ini? Proyek yang sudah dibuat tidak terpengaruh.">@csrf @method('DELETE')<button class="btn warn sm">Hapus template</button></form></div>
</div>
@endif
@endsection
