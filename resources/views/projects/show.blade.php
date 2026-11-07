@extends('layouts.app')
@section('title', $project->code)
@section('crumb')<a href="{{ route('projects.index') }}">Proyek</a> / {{ $project->code }} @endsection
@section('content')
@php
  $isMgr = auth()->user()->isManager();
  $work = $tasks->where('kind', 'task');
  $tabs = ['ringkasan' => 'Ringkasan', 'gantt' => 'Gantt chart', 'wbs' => 'WBS & kegiatan', 'pic' => 'PIC & tim', 'dokumen' => 'Dokumen & lampiran',
    'diskusi' => 'Diskusi & memo', 'peraturan' => 'Peraturan', 'riwayat' => 'Riwayat'];
  $counts = ['wbs' => $work->count(), 'dokumen' => $docs->count() + $attachments->whereNull('task_document_id')->count(), 'diskusi' => $comments->count() + $memos->count()];
@endphp
<div class="page-head">
  <div>
    <h1>{!! health_dot($project->health()) !!} {{ $project->code }} · {{ $project->name }}</h1>
    <p>{{ $project->triggerLabel() }} · {{ $project->template?->name ?? 'Kelompok belum dipilih' }} · Manajer {{ $project->manager?->name }}
      @if($project->wbsTemplate) · WBS dari template <a href="{{ route('wbs-templates.show', $project->wbsTemplate) }}">{{ $project->wbsTemplate->name }}</a>@endif</p>
  </div>
  <div class="sp"></div>
  {!! badge($project->status, $project->statusLabel()) !!} {!! badge($project->priority, 'Prioritas '.strtolower($project->priorityLabel())) !!}
  @if($isMgr)
    <a class="btn ghost sm" href="{{ route('projects.edit', $project) }}">Ubah</a>
    <form method="post" action="{{ route('projects.reschedule', $project) }}" class="inline-form">@csrf<button class="btn ghost sm" title="Hitung ulang dari hari ini berdasarkan progres & dependensi">↻ Jadwal ulang</button></form>
    <form method="post" action="{{ route('projects.baseline', $project) }}" class="inline-form" data-confirm="Simpan jadwal saat ini sebagai baseline baru?">@csrf<button class="btn ghost sm">⚑ Simpan baseline</button></form>
  @endif
</div>

<div class="grid g4" style="margin-bottom:14px">
  <div class="kpi"><div class="v">{{ $project->progress }}%</div><div class="l">Progres (bobot durasi)</div><div class="bar" style="margin-top:6px"><i style="width:{{ $project->progress }}%"></i></div></div>
  <div class="kpi"><div class="v" style="font-size:18px">{{ tgl($project->target_date) }}</div><div class="l">Tenggat izin terbit{{ $project->eta ? ' · ETA '.tgl($project->eta) : '' }}</div></div>
  <div class="kpi {{ $project->delayDays() > 0 ? 'bad' : 'ok' }}"><div class="v" style="font-size:18px">{{ tgl($project->actual_end ?? $project->planned_end) }}</div><div class="l">{{ $project->actual_end ? 'Selesai aktual' : 'Rencana selesai' }} · baseline {{ tgl($project->baseline_end) }} {{ $project->delayDays() > 0 ? '· terlambat '.$project->delayDays().' hari' : '' }}</div></div>
  <div class="kpi {{ $forecast['risk']==='tinggi' ? 'bad' : ($forecast['risk']==='sedang' ? 'warn' : 'ok') }}"><div class="v" style="font-size:18px">{{ $project->status==='selesai' ? 'Selesai' : tgl($forecast['p50']) }}</div><div class="l">Proyeksi P50 · P80 {{ $project->status==='selesai' ? '–' : tgl($forecast['p80']) }} · risiko {{ $forecast['risk'] }}</div></div>
</div>

<nav class="tabs">
  @foreach($tabs as $k => $l)<a href="{{ route('projects.show', [$project, 'tab' => $k]) }}" class="{{ $tab===$k ? 'on' : '' }}">{{ $l }}@isset($counts[$k])<span class="cnt">{{ $counts[$k] }}</span>@endisset</a>@endforeach
</nav>

@if($tab === 'ringkasan')
<div class="grid g-side">
  <div>
    <div class="card"><h2>Kebutuhan izin</h2><p style="white-space:pre-line;margin-top:0">{{ $project->need_description ?: '–' }}</p>
      <dl class="dl">
        <dt>Pemicu</dt><dd>{{ $project->triggerLabel() }}</dd>
        <dt>Eksportir / unit usaha</dt><dd>{{ $project->supplier?->name ?? '–' }}</dd>
        <dt>PO</dt><dd>{{ $project->po_ref ?? '–' }}</dd>
        <dt>ETD / ETA</dt><dd>{{ tgl($project->etd) }} / {{ tgl($project->eta) }}</dd>
        <dt>Mulai</dt><dd>{{ tgl($project->start_date) }}</dd>
      </dl>
    </div>
    <div class="card flush"><div class="card-h"><h2>Item code</h2></div>
      <table class="tbl"><thead><tr><th>Item code</th><th>Nama</th><th>HS</th><th>Kelompok</th><th class="num">Qty (kg)</th></tr></thead><tbody>
      @forelse($project->items as $i)<tr><td><code>{{ $i->code }}</code></td><td>{{ $i->name }}@if($i->non_halal) {!! badge('ditolak','Non-halal') !!}@endif</td><td>{{ $i->hs_code }}</td><td><small>{{ $i->template?->name }}</small></td><td class="num">{{ number_format($i->pivot->qty_kg, 0, ',', '.') }}</td></tr>
      @empty<tr><td colspan="5" class="empty">Tidak ada item.</td></tr>@endforelse
      </tbody></table></div>
    <div class="card flush"><div class="card-h"><h2>Paket izin</h2></div>
      <table class="tbl"><thead><tr><th>WBS</th><th>Izin</th><th>Bagian / PIC</th><th>Rencana</th><th>Progres</th><th>Status</th></tr></thead><tbody>
      @foreach($tasks->where('kind','package') as $pkg)
        <tr class="{{ $pkg->is_critical && $pkg->status!=='selesai' ? 'crit' : '' }}"><td>{{ $pkg->wbs }}</td><td><b>{{ $pkg->name }}</b><br><small>{{ $pkg->permitType?->authority }}</small></td>
          <td><small>{{ $pkg->party?->shortName() }}<br>{{ $pkg->assignee?->name }}</small></td><td><small>{{ tgl($pkg->planned_start,'d M') }} – {{ tgl($pkg->planned_end) }}</small></td>
          <td style="min-width:90px"><div class="bar"><i style="width:{{ $pkg->progress }}%"></i></div><small>{{ $pkg->progress }}%</small></td><td>{!! badge($pkg->status, $pkg->statusLabel()) !!}</td></tr>
      @endforeach
      </tbody></table></div>
  </div>
  <div>
    <div class="card"><h3>Milestone</h3>
      @foreach($tasks->where('kind','milestone') as $m)
        <div style="padding:6px 0;border-bottom:1px solid var(--line)"><b style="color:{{ $m->status==='selesai' ? 'var(--ok)' : 'var(--accent)' }}">◆</b> <a href="{{ route('tasks.show', $m) }}">{{ $m->name }}</a><br>
          <small>{{ $m->status==='selesai' ? 'Tercapai '.tgl($m->actual_end) : 'Rencana '.tgl($m->planned_end) }} · baseline {{ tgl($m->baseline_end) }}</small></div>
      @endforeach
    </div>
    <div class="card"><h3>Izin yang dipakai ulang</h3>
      @forelse($project->covered_permits ?? [] as $c)
        <div style="padding:5px 0;border-bottom:1px solid var(--line)">✓ {{ $c['type'] }}<br><small>{{ $c['license'] }} · s/d {{ $c['expires'] ? tgl($c['expires']) : 'tanpa batas' }}</small></div>
      @empty <small class="muted">Tidak ada; semua izin diurus di proyek ini.</small> @endforelse
    </div>
    <div class="card"><h3>Izin terbit dari proyek ini</h3>
      @forelse($project->licenses as $l)<div style="padding:5px 0;border-bottom:1px solid var(--line)">▣ {{ $l->permitType->name }}<br><small>{{ $l->number }} · {{ tgl($l->issued_at) }}</small></div>
      @empty <small class="muted">Belum ada.</small> @endforelse
    </div>
    @if($isMgr)
    <div class="card"><h3>Template</h3>
      <form method="post" action="{{ route('projects.save-template', $project) }}">@csrf
        <div class="field"><label>Simpan struktur proyek ini sebagai template WBS</label><input type="text" name="name" value="WBS {{ $project->template?->name ?? $project->name }}" required></div>
        <button class="btn ghost sm">⧉ Simpan sebagai template</button></form>
      @if($work->where('status','!=','belum')->isEmpty())
      <hr><form method="post" action="{{ route('projects.copy-template', $project) }}" data-confirm="WBS proyek ini akan diganti dengan isi template. Lanjutkan?">@csrf
        <div class="field"><label>Salin WBS dari template</label><select name="wbs_template_id">@foreach($wbsTemplates as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
        <label class="check"><input type="checkbox" name="skip_covered" value="1" checked> Lewati izin yang sudah dimiliki</label>
        <button class="btn sm" style="margin-top:8px">⧉ Salin dari template</button></form>
      @endif
    </div>
    @endif
  </div>
</div>
@endif

@if($tab === 'gantt')
<div class="card">
  <div class="gantt-toolbar">
    <button class="btn sm ghost" data-gantt-mode="Day">Hari</button>
    <button class="btn sm" data-gantt-mode="Week">Minggu</button>
    <button class="btn sm ghost" data-gantt-mode="Month">Bulan</button>
    <div class="sp" style="flex:1"></div>
    <div class="legend"><span><i style="background:#4a5a78"></i>Paket izin</span><span><i style="background:var(--brand)"></i>Kegiatan</span><span><i style="background:#f0b3ab"></i>Jalur kritis</span><span><i style="background:#6f88b3"></i>Selesai</span><span><i style="background:var(--accent)"></i>Milestone</span><span><i style="border:2px dashed #e5a50a;background:#fff"></i>Lewat jadwal</span></div>
  </div>
  <div class="gantt-wrap"><div id="gantt"></div></div>
  <p class="help">Klik batang untuk membuka kegiatan. {{ $isMgr ? 'Seret batang untuk menggeser jadwal; kegiatan sesudahnya ikut bergeser mengikuti dependensi.' : 'Hanya manajer yang dapat menggeser jadwal.' }} Garis panah = dependensi (selesai → mulai).</p>
</div>
@push('scripts')<script>IZ.gantt('#gantt', @json(route('projects.gantt-data', $project)), {editable: @json($isMgr), datesUrl: @json(route('tasks.dates', '__ID__')), clickOpens: false});</script>@endpush
@endif

@if($tab === 'wbs')
<div class="card flush"><div class="scroll-x">
<table class="tbl">
  <thead><tr><th>WBS</th><th>Kegiatan</th><th>Bagian / PIC</th><th class="num">Durasi</th><th>Rencana</th><th>Baseline</th><th>Aktual</th><th>Pendahulu</th><th>Peraturan</th><th>Dok.</th><th>Progres</th><th>Status</th></tr></thead>
  <tbody>
  @foreach($tasks as $t)
    @php $docOk = $t->documents->where('status','terverifikasi')->count(); @endphp
    <tr class="{{ $t->kind==='package' ? 'pkg' : ($t->kind==='milestone' ? 'ms' : '') }} {{ $t->is_critical && $t->status!=='selesai' ? 'crit' : '' }}">
      <td>{{ $t->wbs }}</td>
      <td class="{{ $t->kind==='task' ? 'wbs-indent-1' : '' }}">@if($t->kind==='milestone')<b style="color:var(--accent)">◆</b> @endif<a href="{{ route('tasks.show', $t) }}">{{ $t->name }}</a>
        @if($t->blocker_note)<br><small style="color:var(--bad)">⚠ {{ $t->blocker_note }}</small>@endif
        @if($t->comments_count || $t->attachments_count)<br><small class="muted">💬 {{ $t->comments_count }} · 📎 {{ $t->attachments_count }}</small>@endif</td>
      <td><small>{{ $t->party?->shortName() }}<br>{{ $t->assignee?->name }}</small></td>
      <td class="num">{{ $t->kind==='milestone' ? '◆' : $t->duration_days.' h' }}</td>
      <td><small>{{ tgl($t->planned_start,'d/m') }}–{{ tgl($t->planned_end,'d/m/y') }}</small>@if($t->isOverdue())<br>{!! badge('kedaluwarsa','lewat') !!}@endif</td>
      <td><small>{{ tgl($t->baseline_start,'d/m') }}–{{ tgl($t->baseline_end,'d/m/y') }}</small></td>
      <td><small>{{ $t->actual_start ? tgl($t->actual_start,'d/m').'–'.($t->actual_end ? tgl($t->actual_end,'d/m/y') : '…') : '–' }}</small></td>
      <td><small>{{ $t->predecessors->pluck('wbs')->implode(', ') ?: '–' }}</small></td>
      <td style="max-width:220px">@if($t->kind!=='milestone')@include('partials.regs', ['regs' => $t->regulations->take(3)])@if($t->regulations->count()>3)<small class="muted">+{{ $t->regulations->count()-3 }}</small>@endif @endif</td>
      <td><small>{{ $t->documents->count() ? $docOk.'/'.$t->documents->count() : '' }}</small></td>
      <td style="min-width:80px">@if($t->kind!=='milestone')<div class="bar"><i style="width:{{ $t->progress }}%"></i></div><small>{{ $t->progress }}%</small>@endif</td>
      <td>{!! badge($t->status, $t->statusLabel()) !!}@if($t->is_critical && $t->status!=='selesai')<br><small style="color:var(--bad)">kritis</small>@elseif($t->slack_days && $t->status!=='selesai')<br><small class="muted">slack {{ $t->slack_days }} h</small>@endif</td>
    </tr>
  @endforeach
  </tbody>
</table></div></div>
@if($isMgr)
<details class="box"><summary>＋ Tambah kegiatan / milestone</summary><div>
<form method="post" action="{{ route('tasks.store', $project) }}">@csrf
  <div class="grid g3">
    <div class="field"><label>Nama</label><input type="text" name="name" required></div>
    <div class="field"><label>Jenis</label><select name="kind"><option value="task">Kegiatan</option><option value="milestone">Milestone</option><option value="package">Paket izin</option></select></div>
    <div class="field"><label>Di dalam paket</label><select name="parent_id"><option value="">–</option>@foreach($tasks->where('kind','package') as $p)<option value="{{ $p->id }}">{{ $p->wbs }} {{ $p->name }}</option>@endforeach</select></div>
    <div class="field"><label>Bagian</label><select name="party_id"><option value="">–</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->shortName() }}</option>@endforeach</select></div>
    <div class="field"><label>PIC</label><select name="assignee_id"><option value="">(PIC bagian)</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
    <div class="field"><label>Durasi (hari)</label><input type="number" name="duration_days" value="3" min="0"></div>
    <div class="field"><label>Pendahulu (selesai → mulai)</label><select name="depends_on[]" multiple>@foreach($tasks->whereIn('kind',['task','milestone']) as $p)<option value="{{ $p->id }}">{{ $p->wbs }} {{ \Illuminate\Support\Str::limit($p->name, 50) }}</option>@endforeach</select></div>
    <div class="field"><label>Referensi peraturan</label><select name="regulations[]" multiple>@foreach($regulations as $r)<option value="{{ $r->id }}">{{ $r->name }} – {{ \Illuminate\Support\Str::limit($r->title, 50) }}</option>@endforeach</select></div>
    <div class="field"><label>Petunjuk</label><textarea name="instructions"></textarea></div>
  </div>
  <button class="btn sm">Tambah & jadwalkan</button>
</form></div></details>
@endif
@endif

@if($tab === 'pic')
<div class="grid g2">
<div class="card"><h2>PIC per bagian</h2>
  <form method="post" action="{{ route('projects.pics', $project) }}">@csrf
  <table class="tbl"><thead><tr><th>Bagian</th><th>PIC utama</th><th>Cadangan</th></tr></thead><tbody>
  @foreach($parties as $party)
    @php $pic = $project->pics->firstWhere('party_id', $party->id); @endphp
    <tr><td><b>{{ $party->shortName() }}</b><br><small>{{ \Illuminate\Support\Str::limit($party->responsibility, 80) }}</small></td>
      <td><select name="pic[{{ $party->id }}]" @disabled(!$isMgr)><option value="">–</option>@foreach($users->where('party_id',$party->id) as $u)<option value="{{ $u->id }}" @selected($pic?->user_id===$u->id)>{{ $u->name }}</option>@endforeach</select></td>
      <td><select name="backup[{{ $party->id }}]" @disabled(!$isMgr)><option value="">–</option>@foreach($users->where('party_id',$party->id) as $u)<option value="{{ $u->id }}" @selected($pic?->backup_user_id===$u->id)>{{ $u->name }}</option>@endforeach</select></td></tr>
  @endforeach
  </tbody></table>
  @if($isMgr)<label class="check" style="margin:10px 0"><input type="checkbox" name="reassign" value="1"> Alihkan kegiatan yang belum selesai ke PIC baru</label><button class="btn sm">Simpan PIC</button>@endif
  </form>
</div>
<div class="card"><h2>Beban tim di proyek ini</h2>
  <table class="tbl"><thead><tr><th>PIC</th><th class="num">Kegiatan</th><th class="num">Selesai</th><th class="num">Terbuka</th><th class="num">Lewat jadwal</th><th class="num">Hari kerja</th></tr></thead><tbody>
  @foreach($work->groupBy('assignee_id') as $uid => $list)
    <tr><td>{{ $list->first()->assignee?->name ?? 'Belum ditunjuk' }}<br><small>{{ $list->first()->party?->shortName() }}</small></td><td class="num">{{ $list->count() }}</td>
      <td class="num">{{ $list->where('status','selesai')->count() }}</td><td class="num">{{ $list->where('status','!=','selesai')->count() }}</td>
      <td class="num">{{ $list->filter->isOverdue()->count() }}</td><td class="num">{{ $list->sum('duration_days') }}</td></tr>
  @endforeach
  </tbody></table>
  <p class="help">Lihat beban lintas proyek di <a href="{{ route('workload') }}">Beban kerja PIC</a>.</p>
</div>
</div>
@endif

@if($tab === 'dokumen')
<div class="card flush"><div class="card-h"><h2>Dokumen wajib per kegiatan</h2></div>
<table class="tbl"><thead><tr><th>Dokumen</th><th>Kegiatan</th><th>Verifikator</th><th>File</th><th>Masa berlaku</th><th>Status</th></tr></thead><tbody>
@forelse($docs as $d)
  @php $t = $tasks->firstWhere('id', $d->task_id); @endphp
  <tr><td><a href="{{ route('tasks.show', $d->task_id) }}#doc-{{ $d->id }}">{{ $d->name }}</a>@if($d->note)<br><small style="color:var(--bad)">{{ $d->note }}</small>@endif</td>
    <td><small>{{ $t?->wbs }} {{ $t?->name }}</small></td><td><small>{{ $d->spec?->verifier?->shortName() ?? $t?->party?->shortName() }}</small></td>
    <td><small>{{ $attachments->where('task_document_id', $d->id)->count() }} file</small></td><td>{{ tgl($d->expiry_date) }}</td>
    <td>{!! badge($d->status, \App\Models\TaskDocument::STATUSES[$d->status]) !!}</td></tr>
@empty<tr><td colspan="6" class="empty">Belum ada dokumen.</td></tr>@endforelse
</tbody></table></div>
<div class="card"><h2>Semua lampiran proyek ({{ $attachments->count() }})</h2>
  <div class="files">@forelse($attachments as $a)@include('partials.file', ['a' => $a])@empty<div class="empty">Belum ada lampiran.</div>@endforelse</div>
</div>
@endif

@if($tab === 'diskusi')
<div class="grid g2">
  <div class="card" id="diskusi"><h2>Diskusi proyek</h2>
    @include('partials.comment-form', ['action' => route('projects.comment', $project), 'parties' => $parties, 'users' => $users])
    <hr>@include('partials.comments', ['comments' => $comments, 'showContext' => true])
  </div>
  <div class="card"><div class="card-h"><h2>Memo antar bagian</h2><div class="sp"></div><a class="btn ghost sm" href="{{ route('memos.index', ['proyek' => $project->id]) }}#baru">＋ Memo</a></div>
    @forelse($memos as $m)
      <div style="padding:8px 0;border-bottom:1px solid var(--line)"><a href="{{ route('memos.show', $m) }}"><b>{{ $m->code }}</b> {{ $m->subject }}</a> {!! badge($m->status, $m->statusLabel()) !!} @if($m->isOverdue()){!! badge('kedaluwarsa','Lewat tenggat') !!}@endif<br>
        <small>{{ $m->fromParty?->shortName() }} → {{ $m->toParty->shortName() }}{{ $m->toUser ? ' ('.$m->toUser->name.')' : '' }} · tenggat {{ tgl($m->due_date) }}</small></div>
    @empty <div class="empty">Belum ada memo.</div> @endforelse
  </div>
</div>
@endif

@if($tab === 'peraturan')
@php $regs = $tasks->flatMap->regulations->unique('id')->sortBy('name'); @endphp
<div class="card flush"><div class="card-h"><h2>Peraturan yang dirujuk proyek ini ({{ $regs->count() }})</h2></div>
<table class="tbl"><thead><tr><th>Peraturan</th><th>Tentang</th><th>Status</th><th>Dipakai di kegiatan</th></tr></thead><tbody>
@foreach($regs as $r)
  <tr><td><b>{{ $r->name }}</b><br><small>{{ $r->issuer }}</small></td><td>{{ $r->title }}@if($r->note)<br><small class="muted">{{ $r->note }}</small>@endif</td><td>{!! badge($r->status, $r->statusLabel()) !!}</td>
    <td><small>{{ $tasks->filter(fn($t) => $t->regulations->contains('id', $r->id))->pluck('wbs')->implode(', ') }}</small></td></tr>
@endforeach
</tbody></table></div>
@endif

@if($tab === 'riwayat')
<div class="card"><h2>Riwayat aktivitas</h2>
<ul class="feed">@forelse($logs as $l)<li><span class="avatar">{{ $l->user?->initials() ?? '⚙' }}</span><div><div class="meta">{{ $l->user?->name ?? 'Sistem' }} · {{ $l->created_at->locale('id')->diffForHumans() }}</div><div class="body"><b>{{ str_replace('_',' ', $l->action) }}</b> {{ $l->detail }}</div></div></li>@empty<li class="empty">Belum ada.</li>@endforelse</ul>
</div>
@endif
@endsection
