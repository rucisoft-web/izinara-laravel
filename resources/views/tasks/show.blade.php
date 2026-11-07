@extends('layouts.app')
@section('title', $task->name)
@section('crumb')<a href="{{ route('projects.index') }}">Proyek</a> / <a href="{{ route('projects.show', [$task->project, 'tab' => 'wbs']) }}">{{ $task->project->code }}</a> / {{ $task->wbs }} @endsection
@section('content')
@php
  $me = auth()->user();
  $canEdit = $me->canEditTask($task);
  $isMgr = $me->isManager();
  $regs = $task->effectiveRegulations();
  $pkg = $task->kind === 'package' ? $task : $task->parent;
@endphp
<div class="page-head">
  <div><h1>@if($task->kind==='milestone')<span style="color:var(--accent)">◆</span>@endif {{ $task->wbs }} · {{ $task->name }}</h1>
    <p>{{ \App\Models\Task::KINDS[$task->kind] }} · <a href="{{ route('projects.show', $task->project) }}">{{ $task->project->code }} {{ $task->project->name }}</a>{{ $task->parent ? ' · paket '.$task->parent->name : '' }}</p></div>
  <div class="sp"></div>
  {!! badge($task->status, $task->statusLabel()) !!} @if($task->is_critical && $task->status!=='selesai'){!! badge('kritis','Jalur kritis') !!}@endif @if($task->isOverdue()){!! badge('kedaluwarsa','Lewat jadwal') !!}@endif
</div>

<div class="grid g-side">
<div>
  @if($task->blocker_note)<div class="alert bad"><b>Hambatan:</b> {{ $task->blocker_note }}</div>@endif

  <div class="card">
    <h2>Petunjuk & hasil</h2>
    <p style="white-space:pre-line;margin-top:0">{{ $task->instructions ?: '–' }}</p>
    @if($task->deliverable)<p><b>Dokumen hasil:</b> {{ $task->deliverable }}</p>@endif
    <h3 style="margin-top:14px">Referensi peraturan perundang-undangan</h3>
    <div>@include('partials.regs', ['regs' => $regs])</div>
    @if($regs->contains(fn($r) => in_array($r->status, ['perlu_konfirmasi','dicabut'])))<p class="help">Peraturan berwarna kuning perlu dikonfirmasi versi terbarunya ke JDIH instansi sebelum dipakai sebagai acuan.</p>@endif
    @if($regs->isNotEmpty())
    <table class="tbl small-table" style="margin-top:8px"><tbody>
      @foreach($regs as $r)<tr id="reg-{{ $r->id }}"><td style="width:190px"><b>{{ $r->name }}</b>{{ ($r->pivot->article ?? null) ? ' · '.$r->pivot->article : '' }}</td><td>{{ $r->title }}<br><small class="muted">{{ $r->issuer }}{{ $r->note ? ' · '.$r->note : '' }}</small></td><td>{!! badge($r->status, $r->statusLabel()) !!}</td><td>@if($r->url)<a href="{{ $r->url }}" target="_blank" rel="noopener">JDIH ↗</a>@endif</td></tr>@endforeach
    </tbody></table>
    @endif
    @if($canEdit)
    <details class="box" style="margin-top:10px"><summary>Ubah referensi peraturan kegiatan ini</summary><div>
      <form method="post" action="{{ route('tasks.regulations', $task) }}">@csrf
        <div style="max-height:260px;overflow:auto">
        @foreach($regulations as $r)
          @php $sel = $task->regulations->firstWhere('id', $r->id); @endphp
          <div class="row" style="padding:3px 0"><label class="check" style="flex:1"><input type="checkbox" name="regulations[]" value="{{ $r->id }}" @checked($sel)> {{ $r->name }} – {{ \Illuminate\Support\Str::limit($r->title, 60) }} @if($r->status==='dicabut')<small style="color:var(--bad)">(dicabut)</small>@endif</label>
            <input type="text" name="article[{{ $r->id }}]" value="{{ $sel?->pivot->article }}" placeholder="Pasal" style="width:110px"></div>
        @endforeach
        </div>
        <button class="btn sm" style="margin-top:8px">Simpan peraturan</button>
      </form></div></details>
    @endif
  </div>

  @if($task->kind !== 'milestone')
  <div class="card" id="dokumen">
    <div class="card-h"><h2>Dokumen wajib & verifikasi</h2></div>
    @forelse($task->documents as $d)
      @php $canVerify = $isMgr || ($d->spec?->verifier_party_id ? $me->party_id === $d->spec->verifier_party_id : $me->party_id === $task->party_id); @endphp
      <div class="doc st-{{ $d->status }}" id="doc-{{ $d->id }}">
        <div class="row"><b class="grow">📄 {{ $d->name }}</b> {!! badge($d->status, \App\Models\TaskDocument::STATUSES[$d->status]) !!}</div>
        @if($d->spec?->criteria)<div class="crit"><b>Kriteria verifikasi:</b>
{{ $d->spec->criteria }}</div>@endif
        <small class="muted">Verifikator: {{ $d->spec?->verifier?->shortName() ?? $task->party?->shortName() }}{{ $d->spec?->check_expiry ? ' · masa berlaku dicek terhadap ETA/tenggat' : '' }}{{ $d->expiry_date ? ' · berlaku s/d '.tgl($d->expiry_date) : '' }}{{ $d->verifier ? ' · diperiksa '.$d->verifier->name.' '.tgl($d->verified_at) : '' }}</small>
        @if($d->note)<div class="alert bad" style="margin:8px 0 0">Catatan verifikator: {{ $d->note }}</div>@endif
        @if($d->attachments->isNotEmpty())<div class="files" style="margin-top:8px">@foreach($d->attachments as $a)@include('partials.file', ['a' => $a, 'canEdit' => $canEdit])@endforeach</div>@endif
        <div class="row" style="margin-top:8px">
          @if($canEdit && in_array($d->status, ['belum','ditolak']))
            <details class="grow"><summary class="btn ghost sm" style="display:inline-flex">📎 Unggah file</summary><div style="margin-top:8px">@include('partials.upload', ['task' => $task, 'doc' => $d])</div></details>
            <form method="post" action="{{ route('documents.submit', $d) }}">@csrf<button class="btn sm">Ajukan verifikasi</button></form>
          @endif
          @if($canVerify && $d->status === 'diajukan')
            <form method="post" action="{{ route('documents.verify', $d) }}" class="row grow">@csrf
              <input type="text" name="note" placeholder="Catatan (wajib bila ditolak), mis. halaman 2 belum ditandatangani" class="grow" style="flex:1">
              <button class="btn sm" name="decision" value="terverifikasi">✓ Terverifikasi</button>
              <button class="btn warn sm" name="decision" value="ditolak">✕ Tolak</button>
            </form>
          @endif
        </div>
        <details style="margin-top:8px" {{ $d->comments->isNotEmpty() ? 'open' : '' }}><summary class="muted" style="cursor:pointer">💬 Komentar dokumen ({{ $d->comments->count() }})</summary>
          <div style="margin-top:6px">
            @include('partials.comments', ['comments' => $d->comments->load('mentions.user','mentions.party')])
            @include('partials.comment-form', ['action' => route('documents.comment', $d), 'parties' => $parties, 'users' => $users, 'placeholder' => 'Komentari dokumen ini, mis. "@purchasing mohon minta revisi HC: nomor kontainer belum ada"'])
          </div></details>
      </div>
    @empty <p class="muted">Tidak ada dokumen wajib untuk kegiatan ini.</p> @endforelse
    @if($canEdit)
    <details class="box"><summary>＋ Tambah dokumen lain</summary><div>
      <form method="post" action="{{ route('documents.store', $task) }}" class="row">@csrf<input type="text" name="name" placeholder="Nama dokumen" required class="grow" style="flex:1"><input type="date" name="expiry_date" style="width:auto" title="Masa berlaku"><button class="btn sm">Tambah</button></form>
    </div></details>
    @endif
  </div>

  <div class="card" id="lampiran">
    <h2>Lampiran kegiatan ({{ $task->attachments->whereNull('task_document_id')->count() }})</h2>
    <p class="help" style="margin-top:-6px">File pendukung di luar dokumen wajib: draf, korespondensi, foto, bukti pengajuan, tangkapan layar portal, dll.</p>
    <div class="files" style="margin-bottom:10px">@foreach($task->attachments->whereNull('task_document_id') as $a)@include('partials.file', ['a' => $a, 'canEdit' => $canEdit])@endforeach</div>
    @if($canEdit)@include('partials.upload', ['task' => $task])@endif
  </div>
  @endif

  <div class="card" id="diskusi">
    <h2>Diskusi kegiatan</h2>
    @include('partials.comment-form', ['action' => route('tasks.comment', $task), 'parties' => $parties, 'users' => $users])
    <hr>
    @include('partials.comments', ['comments' => $task->comments])
  </div>
</div>

<div>
  <div class="card">
    <h3>Jadwal</h3>
    <dl class="dl">
      <dt>Rencana</dt><dd>{{ tgl($task->planned_start) }} – {{ tgl($task->planned_end) }}</dd>
      <dt>Baseline</dt><dd>{{ tgl($task->baseline_start) }} – {{ tgl($task->baseline_end) }}</dd>
      <dt>Aktual</dt><dd>{{ tgl($task->actual_start) }} – {{ tgl($task->actual_end) }}</dd>
      <dt>Durasi</dt><dd>{{ $task->duration_days }} hari</dd>
      <dt>Slack</dt><dd>{{ $task->slack_days }} hari {{ $task->is_critical ? '(jalur kritis)' : '' }}</dd>
      @if($task->not_before)<dt>Tidak sebelum</dt><dd>{{ tgl($task->not_before) }}</dd>@endif
      <dt>Bagian</dt><dd>{{ $task->party?->shortName() ?? '–' }}</dd>
      <dt>PIC</dt><dd>{{ $task->assignee?->name ?? '–' }}</dd>
      <dt>Pendahulu</dt><dd>@forelse($task->predecessors as $p)<a href="{{ route('tasks.show', $p) }}">{{ $p->wbs }}</a> @empty – @endforelse</dd>
      <dt>Penerus</dt><dd>@forelse($task->successors as $p)<a href="{{ route('tasks.show', $p) }}">{{ $p->wbs }}</a> @empty – @endforelse</dd>
    </dl>
  </div>

  @if($canEdit && $task->kind !== 'package')
  <div class="card">
    <h3>Perbarui progres</h3>
    <form method="post" action="{{ route('tasks.update', $task) }}">@csrf @method('PUT')
      <div class="grid g2">
        <div class="field"><label>Status</label><select name="status">@foreach(\App\Models\Task::STATUSES as $k=>$v)<option value="{{ $k }}" @selected($task->status===$k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label>Progres %</label><input type="number" name="progress" min="0" max="100" value="{{ $task->progress }}"></div>
        <div class="field"><label>Mulai aktual</label><input type="date" name="actual_start" value="{{ $task->actual_start?->toDateString() }}"></div>
        <div class="field"><label>Selesai aktual</label><input type="date" name="actual_end" value="{{ $task->actual_end?->toDateString() }}"></div>
      </div>
      <div class="field"><label>Hambatan (bila terhambat)</label><textarea name="blocker_note" rows="2">{{ $task->blocker_note }}</textarea></div>
      @if($isMgr)
      <hr>
      <div class="field"><label>Nama kegiatan</label><input type="text" name="name" value="{{ $task->name }}"></div>
      <div class="grid g2">
        <div class="field"><label>Bagian</label><select name="party_id"><option value="">–</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected($task->party_id===$p->id)>{{ $p->shortName() }}</option>@endforeach</select></div>
        <div class="field"><label>PIC</label><select name="assignee_id"><option value="">–</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($task->assignee_id===$u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <div class="field"><label>Durasi (hari)</label><input type="number" name="duration_days" min="0" value="{{ $task->duration_days }}"></div>
        <div class="field"><label>Dokumen hasil</label><input type="text" name="deliverable" value="{{ $task->deliverable }}"></div>
      </div>
      <div class="field"><label>Pendahulu</label><select name="depends_on[]" multiple>@foreach($siblings as $s)<option value="{{ $s->id }}" @selected($task->predecessors->contains('id',$s->id))>{{ $s->wbs }} {{ \Illuminate\Support\Str::limit($s->name, 45) }}</option>@endforeach</select></div>
      <div class="field"><label>Petunjuk</label><textarea name="instructions">{{ $task->instructions }}</textarea></div>
      @endif
      <button class="btn">Simpan & hitung jadwal</button>
    </form>
    @if($isMgr)<form method="post" action="{{ route('tasks.destroy', $task) }}" data-confirm="Hapus kegiatan ini?" style="margin-top:8px">@csrf @method('DELETE')<button class="btn warn sm">Hapus kegiatan</button></form>@endif
  </div>
  @endif

  @if($pkg && $pkg->permitType && $pkg->permitType->scope !== 'shipment' && $canEdit)
  <div class="card">
    <h3>Izin terbit → register</h3>
    <p class="help">{{ $pkg->permitType->name }}{{ $pkg->permitType->validity_months ? ' · berlaku '.$pkg->permitType->validity_months.' bulan' : '' }}</p>
    <form method="post" action="{{ route('tasks.issue', $task) }}">@csrf
      <div class="field"><label>Nomor izin</label><input type="text" name="number" required></div>
      <div class="grid g2"><div class="field"><label>Tanggal terbit</label><input type="date" name="issued_at" value="{{ today()->toDateString() }}" required></div>
        <div class="field"><label>Berlaku s/d</label><input type="date" name="expires_at"></div></div>
      <div class="field"><label>Kuota (kg, bila ada)</label><input type="number" name="quota_kg" step="0.01"></div>
      <button class="btn sm">Catat izin terbit</button>
    </form>
  </div>
  @endif

  <div class="card"><h3>Memo terkait</h3>
    @forelse($task->memos as $m)<div style="padding:5px 0;border-bottom:1px solid var(--line)"><a href="{{ route('memos.show', $m) }}">{{ $m->code }} {{ $m->subject }}</a> {!! badge($m->status, $m->statusLabel()) !!}<br><small>{{ $m->fromParty?->shortName() }} → {{ $m->toParty->shortName() }}</small></div>
    @empty <small class="muted">Belum ada.</small> @endforelse
    @if($me->role !== 'viewer')
    <details class="box" style="margin-top:10px"><summary>✉ Kirim memo ke bagian lain</summary><div>
      <form method="post" action="{{ route('memos.store') }}">@csrf
        <input type="hidden" name="project_id" value="{{ $task->project_id }}"><input type="hidden" name="task_id" value="{{ $task->id }}">
        <div class="field"><label>Kepada bagian</label><select name="to_party_id" required>@foreach($parties as $p)<option value="{{ $p->id }}" @selected($p->code==='purchasing')>{{ $p->shortName() }}</option>@endforeach</select></div>
        <div class="field"><label>Orang (opsional)</label><select name="to_user_id"><option value="">Semua anggota bagian</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
        <div class="field"><label>Dokumen terkait</label><select name="task_document_id"><option value="">–</option>@foreach($task->documents as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="field"><label>Perihal</label><input type="text" name="subject" required></div>
        <div class="field"><label>Isi</label><textarea name="body" required></textarea></div>
        <div class="grid g2"><div class="field"><label>Prioritas</label><select name="priority">@foreach(\App\Models\Project::PRIORITIES as $k=>$v)<option value="{{ $k }}" @selected($k==='normal')>{{ $v }}</option>@endforeach</select></div>
          <div class="field"><label>Tenggat jawab</label><input type="date" name="due_date" value="{{ today()->addDays(2)->toDateString() }}"></div></div>
        <button class="btn sm">Kirim memo</button>
      </form></div></details>
    @endif
  </div>
</div>
</div>
@endsection
