@extends('layouts.app')
@section('title', 'Memo antar bagian')
@section('crumb', 'Memo antar bagian')
@section('content')
<div class="page-head"><div><h1>Memo antar bagian</h1><p>Permintaan resmi ke bagian lain dengan tenggat jawab. Waktu respons dan ketepatan waktu tercatat per bagian. Contoh: Bagian Perizinan meminta Purchasing mengambil revisi health certificate dari prinsipal.</p></div>
<div class="sp"></div><div class="pill-nav"><a href="?kotak=masuk" class="{{ $box==='masuk'?'on':'' }}">Masuk</a><a href="?kotak=keluar" class="{{ $box==='keluar'?'on':'' }}">Keluar</a><a href="?kotak=semua" class="{{ $box==='semua'?'on':'' }}">Semua</a></div></div>
<div class="grid g-side">
<div class="card flush"><table class="tbl">
<thead><tr><th>Memo</th><th>Dari → Kepada</th><th>Proyek</th><th>Tenggat</th><th>Respons</th><th>Status</th></tr></thead><tbody>
@forelse($memos as $m)
<tr><td><a href="{{ route('memos.show', $m) }}"><b>{{ $m->code }}</b> {{ $m->subject }}</a><br><small>{{ $m->replies_count }} balasan · {{ $m->created_at->locale('id')->diffForHumans() }}</small></td>
  <td><small>{{ $m->fromParty?->shortName() ?? $m->fromUser?->name }}<br>→ {{ $m->toParty->shortName() }}{{ $m->toUser ? ' ('.$m->toUser->name.')' : '' }}</small></td>
  <td><small>{{ $m->project?->code }}{{ $m->task ? ' · '.$m->task->wbs : '' }}</small></td>
  <td>{{ tgl($m->due_date) }}@if($m->isOverdue())<br>{!! badge('kedaluwarsa','Lewat') !!}@endif</td>
  <td><small>{{ $m->responseHours() !== null ? $m->responseHours().' jam' : '–' }}</small></td>
  <td>{!! badge($m->status, $m->statusLabel()) !!} {!! $m->priority !== 'normal' ? badge($m->priority, ucfirst($m->priority)) : '' !!}</td></tr>
@empty <tr><td colspan="6" class="empty">Tidak ada memo.</td></tr> @endforelse
</tbody></table></div>
@if(auth()->user()->role !== 'viewer')
<div class="card" id="baru"><h2>Memo baru</h2>
<form method="post" action="{{ route('memos.store') }}">@csrf
  <div class="field"><label>Kepada bagian</label><select name="to_party_id" required>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->shortName() }}</option>@endforeach</select></div>
  <div class="field"><label>Orang (opsional)</label><select name="to_user_id"><option value="">Semua anggota bagian</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} – {{ $u->party?->code }}</option>@endforeach</select></div>
  <div class="field"><label>Proyek</label><select name="project_id"><option value="">–</option>@foreach($projects as $p)<option value="{{ $p->id }}" @selected(request('proyek')==$p->id)>{{ $p->code }} {{ \Illuminate\Support\Str::limit($p->name, 40) }}</option>@endforeach</select></div>
  <div class="field"><label>Perihal</label><input type="text" name="subject" required></div>
  <div class="field"><label>Isi</label><textarea name="body" required></textarea></div>
  <div class="grid g2"><div class="field"><label>Prioritas</label><select name="priority">@foreach(\App\Models\Project::PRIORITIES as $k=>$v)<option value="{{ $k }}" @selected($k==='normal')>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Tenggat jawab</label><input type="date" name="due_date" value="{{ today()->addDays(2)->toDateString() }}"></div></div>
  <button class="btn">Kirim</button>
</form></div>
@endif
</div>
@endsection
