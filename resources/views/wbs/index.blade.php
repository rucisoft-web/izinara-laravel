@extends('layouts.app')
@section('title', 'Template WBS')
@section('crumb', 'Template WBS')
@section('content')
<div class="page-head"><div><h1>Template WBS</h1><p>Struktur kerja siap pakai: paket izin, kegiatan, durasi, dependensi, milestone, PIC per bagian, peraturan dan dokumen wajib. Pilih saat membuat proyek baru dengan "Salin dari template", atau simpan proyek yang sudah jalan sebagai template.</p></div></div>
<div class="grid g3">
@forelse($templates as $t)
  <div class="card">
    <div class="card-h"><h3><a href="{{ route('wbs-templates.show', $t) }}">{{ $t->name }}</a></h3></div>
    <small class="muted">{{ $t->groupLabel() }}</small>
    <p style="margin:6px 0">{{ $t->description }}</p>
    <div class="row"><span class="tag">{{ count($t->packages()) }} paket izin</span><span class="tag">{{ $t->taskCount() }} kegiatan</span><span class="tag">{{ $t->milestoneCount() }} milestone</span><span class="tag">±{{ $t->totalDays() }} hari</span><span class="tag">dipakai {{ $t->times_used }}×</span></div>
    @if(auth()->user()->isManager())<a class="btn sm" style="margin-top:10px" href="{{ route('projects.create', ['wbs' => $t->id]) }}">⧉ Pakai untuk proyek baru</a>@endif
  </div>
@empty <div class="card empty">Belum ada template. Buka proyek lalu pilih "Simpan sebagai template".</div> @endforelse
</div>
@endsection
