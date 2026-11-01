@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb', 'Dashboard')
@section('content')
<div class="page-head">
  <div><h1>Dashboard perizinan</h1><p>Semua proyek pengurusan izin dari kebutuhan sampai izin terbit · per {{ tgl(today(), 'l, d F Y') }}</p></div>
  <div class="sp"></div>
  @if(auth()->user()->isManager())<a class="btn" href="{{ route('projects.create') }}">＋ Proyek perizinan baru</a>@endif
</div>

@php
  $late = $forecast->where('risk', 'tinggi')->count();
  $running = $open->where('status', 'berjalan')->count();
@endphp
<div class="grid g4" style="margin-bottom:16px">
  <div class="kpi"><div class="v">{{ $open->count() }}</div><div class="l">Proyek aktif ({{ $running }} berjalan, {{ $open->where('status','rencana')->count() }} rencana, {{ $open->where('status','tertahan')->count() }} tertahan)</div></div>
  <div class="kpi {{ $late ? 'bad' : 'ok' }}"><div class="v">{{ $late }}</div><div class="l">Proyek diproyeksi melewati tenggat (P50)</div></div>
  <div class="kpi {{ $overdue->count() ? 'warn' : 'ok' }}"><div class="v">{{ $overdue->count() }}</div><div class="l">Kegiatan lewat jadwal · {{ $blocked->count() }} terhambat</div></div>
  <div class="kpi {{ $licenses->count() ? 'warn' : 'ok' }}"><div class="v">{{ $licenses->count() }}</div><div class="l">Izin habis ≤ 90 hari / sudah kedaluwarsa</div></div>
</div>

<div class="grid g-side">
  <div>
    <div class="card flush">
      <div class="card-h"><h2>Kesehatan proyek</h2><div class="sp"></div><a href="{{ route('gantt.portfolio') }}" class="btn ghost sm">Gantt portofolio</a></div>
      <div class="scroll-x">
      <table class="tbl">
        <thead><tr><th></th><th>Proyek</th><th>Kelompok</th><th>Progres</th><th>Tenggat</th><th>Rencana selesai</th><th>Proyeksi P50 / P80</th><th>Risiko</th></tr></thead>
        <tbody>
        @foreach($forecast as $f)
          @php $p = $f['project']; @endphp
          <tr>
            <td>{!! health_dot($p->health()) !!}</td>
            <td style="min-width:220px"><a href="{{ route('projects.show', $p) }}"><b>{{ $p->code }}</b></a><br><small>{{ $p->name }}</small></td>
            <td><small>{{ $p->template ? explode(' – ', $p->template->groupLabel())[0] : '–' }}</small></td>
            <td style="min-width:110px"><div class="bar {{ $p->health()==='merah'?'bad':($p->health()==='kuning'?'warn':'') }}"><i style="width:{{ $p->progress }}%"></i></div><small>{{ $p->progress }}% · {{ $p->statusLabel() }}</small></td>
            <td>{{ tgl($p->target_date) }}</td>
            <td>{{ tgl($p->planned_end) }}@if($p->delayDays()>0)<br><small style="color:var(--bad)">+{{ $p->delayDays() }} hari</small>@endif</td>
            <td>{{ tgl($f['p50']) }}<br><small>{{ tgl($f['p80']) }}</small></td>
            <td>{!! badge($f['risk'], ucfirst($f['risk'])) !!}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
      </div>
    </div>

    <div class="grid g2">
      <div class="card"><div class="card-h"><h3>Proyek per kelompok izin</h3></div><div class="chart-box"><canvas id="cGroup"></canvas></div></div>
      <div class="card"><div class="card-h"><h3>Kegiatan terbuka per bagian</h3></div><div class="chart-box"><canvas id="cParty"></canvas></div></div>
    </div>

    <div class="card flush">
      <div class="card-h"><h2>Kegiatan lewat jadwal & terhambat</h2></div>
      @if($overdue->isEmpty() && $blocked->isEmpty())<div class="empty">Tidak ada. 👍</div>@else
      <table class="tbl">
        <thead><tr><th>Kegiatan</th><th>Proyek</th><th>PIC</th><th>Rencana selesai</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($blocked->merge($overdue)->unique('id')->take(12) as $t)
          <tr><td><a href="{{ route('tasks.show', $t) }}">{{ $t->wbs }} {{ $t->name }}</a>@if($t->blocker_note)<br><small style="color:var(--bad)">{{ $t->blocker_note }}</small>@endif</td>
            <td><small>{{ $t->project->code }}</small></td><td><small>{{ $t->assignee?->name ?? '–' }}<br>{{ $t->party?->shortName() }}</small></td>
            <td>{{ tgl($t->planned_end) }}</td><td>{!! badge($t->status, $t->statusLabel()) !!}</td></tr>
        @endforeach
        </tbody>
      </table>@endif
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-h"><h3>Milestone 45 hari ke depan</h3></div>
      @forelse($milestones as $m)
        <div style="padding:6px 0;border-bottom:1px solid var(--line)"><b style="color:var(--accent)">◆</b> <a href="{{ route('tasks.show', $m) }}">{{ $m->name }}</a><br><small>{{ $m->project->code }} · {{ tgl($m->planned_end, 'D, d M') }}</small></div>
      @empty <div class="empty">Tidak ada milestone dekat.</div> @endforelse
    </div>
    <div class="card">
      <div class="card-h"><h3>Izin segera habis</h3><div class="sp"></div><a href="{{ route('licenses.index') }}" class="btn ghost sm">Register</a></div>
      @forelse($licenses as $l)
        <div style="padding:6px 0;border-bottom:1px solid var(--line)">{!! badge($l->state(), $l->daysLeft() < 0 ? 'Kedaluwarsa' : $l->daysLeft().' hari') !!} <b>{{ $l->permitType->name }}</b><br><small>{{ $l->number }} · s/d {{ tgl($l->expires_at) }}</small></div>
      @empty <div class="empty">Aman.</div> @endforelse
    </div>
    <div class="card">
      <div class="card-h"><h3>Memo antar bagian terbuka</h3><div class="sp"></div><a href="{{ route('memos.index', ['kotak'=>'semua']) }}" class="btn ghost sm">Semua</a></div>
      @forelse($memosOpen->take(6) as $m)
        <div style="padding:6px 0;border-bottom:1px solid var(--line)"><a href="{{ route('memos.show', $m) }}">{{ $m->subject }}</a><br><small>{{ $m->fromParty?->shortName() }} → {{ $m->toParty->shortName() }} · tenggat {{ tgl($m->due_date) }}</small> @if($m->isOverdue()){!! badge('kedaluwarsa','Lewat tenggat') !!}@endif</div>
      @empty <div class="empty">Tidak ada memo terbuka.</div> @endforelse
    </div>
    <div class="card">
      <div class="card-h"><h3>Respons memo per bagian</h3></div>
      <table class="tbl small-table"><thead><tr><th>Bagian</th><th class="num">Memo</th><th class="num">Rata² jam</th><th class="num">Tepat waktu</th></tr></thead><tbody>
      @foreach($memoStats as $s)<tr><td>{{ $s['party']->shortName() }}</td><td class="num">{{ $s['count'] }}</td><td class="num">{{ $s['avg_hours'] ?? '–' }}</td><td class="num">{{ $s['on_time'] !== null ? $s['on_time'].'%' : '–' }}</td></tr>@endforeach
      </tbody></table>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
IZ.chart('cGroup', {type: 'bar', data: {labels: @json($byGroup->pluck('label')->values()), datasets: [
  {label: 'Aktif', data: @json($byGroup->pluck('open')->values()), backgroundColor: '#0f6b5c'},
  {label: 'Selesai', data: @json($byGroup->pluck('done')->values()), backgroundColor: '#9bd0b1'}]},
  options: {scales: {x: {stacked: true}, y: {stacked: true, ticks: {precision: 0}}}}});
IZ.chart('cParty', {type: 'bar', data: {labels: @json($byParty->pluck('label')), datasets: [
  {label: 'Terbuka', data: @json($byParty->pluck('open')), backgroundColor: '#2563a8'},
  {label: 'Lewat jadwal', data: @json($byParty->pluck('overdue')), backgroundColor: '#c0392b'}]},
  options: {indexAxis: 'y', scales: {x: {ticks: {precision: 0}}}}});
</script>
@endpush
