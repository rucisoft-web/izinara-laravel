@extends('layouts.app')
@section('title', 'Proyek perizinan')
@section('crumb', 'Proyek perizinan')
@section('content')
<div class="page-head">
  <div><h1>Proyek perizinan</h1><p>Setiap proyek dimulai dari kebutuhan izin dan selesai saat semua izin terbit.</p></div>
  <div class="sp"></div>
  @if(auth()->user()->isManager())<a class="btn" href="{{ route('projects.create') }}">＋ Proyek baru</a>@endif
</div>
<form class="filters">
  <div class="field"><label>Cari</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Kode atau nama"></div>
  <div class="field"><label>Status</label><select name="status"><option value="">Semua</option>@foreach(\App\Models\Project::STATUSES as $k=>$v)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Kelompok</label><select name="kelompok"><option value="">Semua</option>@foreach(\App\Models\Template::GROUPS as $k=>$v)<option value="{{ $k }}" @selected(request('kelompok')===$k)>{{ $v }}</option>@endforeach</select></div>
  <button class="btn ghost">Terapkan</button>
</form>
<div class="card flush"><div class="scroll-x">
<table class="tbl">
  <thead><tr><th></th><th>Kode</th><th>Proyek</th><th>Pemicu</th><th>Kelompok</th><th>Manajer</th><th>Mulai</th><th>Tenggat</th><th>Rencana selesai</th><th>Progres</th><th>Status</th></tr></thead>
  <tbody>
  @forelse($projects as $p)
    <tr>
      <td>{!! health_dot($p->health()) !!}</td>
      <td><a href="{{ route('projects.show', $p) }}"><b>{{ $p->code }}</b></a></td>
      <td><a href="{{ route('projects.show', $p) }}">{{ $p->name }}</a><br><small>{{ $p->supplier?->name }} · {{ $p->work_tasks_count }} kegiatan</small></td>
      <td><small>{{ $p->triggerLabel() }}</small></td>
      <td><small>{{ $p->template?->name ?? '–' }}</small></td>
      <td><small>{{ $p->manager?->name }}</small></td>
      <td>{{ tgl($p->start_date) }}</td>
      <td>{{ tgl($p->target_date) }}</td>
      <td>{{ tgl($p->actual_end ?? $p->planned_end) }}@if($p->delayDays()>0)<br><small style="color:var(--bad)">+{{ $p->delayDays() }} hari</small>@endif</td>
      <td style="min-width:110px"><div class="bar"><i style="width:{{ $p->progress }}%"></i></div><small>{{ $p->progress }}%</small></td>
      <td>{!! badge($p->status, $p->statusLabel()) !!} {!! $p->priority !== 'normal' ? badge($p->priority, $p->priorityLabel()) : '' !!}</td>
    </tr>
  @empty
    <tr><td colspan="11" class="empty">Belum ada proyek.</td></tr>
  @endforelse
  </tbody>
</table></div></div>
@endsection
