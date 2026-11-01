@extends('layouts.app')
@section('title', $memo->code)
@section('crumb')<a href="{{ route('memos.index') }}">Memo</a> / {{ $memo->code }} @endsection
@section('content')
<div class="page-head"><div><h1>{{ $memo->code }} · {{ $memo->subject }}</h1>
<p>{{ $memo->fromUser?->name }} ({{ $memo->fromParty?->shortName() }}) → {{ $memo->toParty->shortName() }}{{ $memo->toUser ? ' · '.$memo->toUser->name : '' }} · {{ $memo->created_at->locale('id')->translatedFormat('d M Y H:i') }}</p></div>
<div class="sp"></div>{!! badge($memo->status, $memo->statusLabel()) !!} @if($memo->isOverdue()){!! badge('kedaluwarsa','Lewat tenggat') !!}@endif</div>
<div class="grid g-side">
<div class="card">
  <div style="white-space:pre-line">{{ $memo->body }}</div>
  <hr>
  <ul class="feed">@foreach($memo->replies as $r)<li><span class="avatar">{{ $r->user?->initials() }}</span><div><div class="meta"><b>{{ $r->user?->name }}</b> · {{ $r->user?->party?->shortName() }} · {{ $r->created_at->locale('id')->diffForHumans() }}</div><div class="body">{!! $r->bodyHtml() !!}</div></div></li>@endforeach</ul>
  @if($memo->status !== 'selesai' && auth()->user()->role !== 'viewer')
  <form method="post" action="{{ route('memos.reply', $memo) }}">@csrf
    <div class="field"><textarea name="body" data-mention required placeholder="Tulis balasan…"></textarea></div>
    <div class="row"><button class="btn sm">Balas</button><button class="btn ghost sm" name="close" value="1">Balas & tutup memo</button></div>
  </form>
  @endif
</div>
<div class="card"><dl class="dl">
  <dt>Prioritas</dt><dd>{{ ucfirst($memo->priority) }}</dd>
  <dt>Tenggat</dt><dd>{{ tgl($memo->due_date) }}</dd>
  <dt>Respons pertama</dt><dd>{{ $memo->first_response_at ? $memo->first_response_at->locale('id')->translatedFormat('d M H:i').' ('.$memo->responseHours().' jam)' : '–' }}</dd>
  <dt>Tepat waktu</dt><dd>{{ $memo->onTime() === null ? '–' : ($memo->onTime() ? 'Ya' : 'Tidak') }}</dd>
  <dt>Proyek</dt><dd>@if($memo->project)<a href="{{ route('projects.show', $memo->project) }}">{{ $memo->project->code }}</a>@else – @endif</dd>
  <dt>Kegiatan</dt><dd>@if($memo->task)<a href="{{ route('tasks.show', $memo->task) }}">{{ $memo->task->wbs }} {{ $memo->task->name }}</a>@else – @endif</dd>
  <dt>Dokumen</dt><dd>@if($memo->document)<a href="{{ route('tasks.show', $memo->document->task_id) }}#doc-{{ $memo->document->id }}">{{ $memo->document->name }}</a>@else – @endif</dd>
</dl></div>
</div>
@endsection
