@extends('layouts.app')
@section('title', 'Kotak masuk')
@section('crumb', 'Kotak masuk')
@section('content')
<div class="page-head"><div><h1>Kotak masuk</h1><p>Notifikasi saat Anda atau bagian Anda di-@mention, menerima memo, dokumen perlu verifikasi/ditolak, atau ditunjuk sebagai PIC.</p></div>
<div class="sp"></div><form method="post" action="{{ route('notices.read-all') }}">@csrf<button class="btn ghost sm">Tandai semua dibaca</button></form></div>
<div class="card"><ul class="feed">
@forelse($notices as $n)
<li style="{{ $n->read_at ? 'opacity:.65' : '' }}"><span class="avatar">{{ $n->actor?->initials() ?? '⚙' }}</span>
  <div style="flex:1"><div class="meta">{!! badge($n->read_at ? 'muted' : 'berjalan', $n->typeLabel()) !!} · {{ $n->created_at->locale('id')->diffForHumans() }}</div>
  <div class="body"><a href="{{ route('notices.open', $n) }}"><b>{{ $n->title }}</b></a>@if($n->body)<br><small>{{ \Illuminate\Support\Str::limit($n->body, 220) }}</small>@endif</div></div></li>
@empty <li class="empty">Belum ada notifikasi.</li> @endforelse
</ul></div>
@endsection
