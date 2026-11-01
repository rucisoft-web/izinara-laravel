@extends('layouts.app')
@section('title', 'Tugas saya')
@section('crumb', 'Tugas saya')
@section('content')
<div class="page-head"><div><h1>{{ $scope==='bagian' ? 'Tugas bagian saya' : 'Tugas saya' }}</h1><p>Kegiatan terbuka dari semua proyek, urut tanggal mulai.</p></div><div class="sp"></div>
  <div class="pill-nav"><a href="?lingkup=saya" class="{{ $scope==='saya'?'on':'' }}">Saya</a><a href="?lingkup=bagian" class="{{ $scope==='bagian'?'on':'' }}">Bagian saya</a></div></div>
<div class="card flush"><table class="tbl">
<thead><tr><th>Kegiatan</th><th>Proyek</th><th>PIC</th><th>Rencana</th><th>Progres</th><th>Status</th></tr></thead><tbody>
@forelse($tasks as $t)
<tr class="{{ $t->is_critical ? 'crit' : '' }}"><td><a href="{{ route('tasks.show', $t) }}"><b>{{ $t->wbs }}</b> {{ $t->name }}</a><br><small>{{ $t->permitType?->name }}</small></td>
  <td><a href="{{ route('projects.show', $t->project) }}">{{ $t->project->code }}</a><br><small>{{ \Illuminate\Support\Str::limit($t->project->name, 40) }}</small></td>
  <td><small>{{ $t->assignee?->name }}</small></td>
  <td>{{ tgl($t->planned_start,'d M') }} – {{ tgl($t->planned_end) }}@if($t->isOverdue())<br>{!! badge('kedaluwarsa','Lewat jadwal') !!}@endif</td>
  <td style="min-width:90px"><div class="bar"><i style="width:{{ $t->progress }}%"></i></div><small>{{ $t->progress }}%</small></td>
  <td>{!! badge($t->status, $t->statusLabel()) !!}@if($t->is_critical)<br><small style="color:var(--bad)">kritis</small>@endif</td></tr>
@empty <tr><td colspan="6" class="empty">Tidak ada tugas terbuka.</td></tr> @endforelse
</tbody></table></div>
@endsection
