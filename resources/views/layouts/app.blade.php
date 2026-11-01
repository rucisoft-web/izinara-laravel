<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Izinara') · Izinara</title>
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('vendor/frappe-gantt.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/izinara.css') }}?v=3">
@stack('head')
</head>
<body>
@php
    $me = auth()->user();
    $unread = $me->notices()->whereNull('read_at')->count();
    $memoOpen = \App\Models\Memo::where('status', 'terbuka')->where(fn ($q) => $q->where('to_user_id', $me->id)->orWhere('to_party_id', $me->party_id))->count();
    $myOpen = \App\Models\Task::where('assignee_id', $me->id)->where('kind', 'task')->whereIn('status', ['belum', 'berjalan', 'menunggu', 'terhambat'])
        ->whereHas('project', fn ($q) => $q->whereIn('status', ['berjalan', 'tertahan', 'rencana']))->count();
    $nav = fn ($r) => request()->routeIs($r) ? 'on' : '';
@endphp
<div class="app">
  <aside class="side">
    <a class="brand" href="{{ route('dashboard') }}"><img src="{{ asset('img/izinara.png') }}" alt=""><div>Izinara<small>Proyek perizinan impor · Indoguna</small></div></a>
    <nav>
      <a href="{{ route('dashboard') }}" class="{{ $nav('dashboard') }}">▦ Dashboard</a>
      <a href="{{ route('tasks.mine') }}" class="{{ $nav('tasks.mine') }}">☑ Tugas saya @if($myOpen)<span class="cnt">{{ $myOpen }}</span>@endif</a>
      <a href="{{ route('notices.index') }}" class="{{ $nav('notices.*') }}">🔔 Kotak masuk @if($unread)<span class="cnt">{{ $unread }}</span>@endif</a>
      <a href="{{ route('memos.index') }}" class="{{ $nav('memos.*') }}">✉ Memo antar bagian @if($memoOpen)<span class="cnt">{{ $memoOpen }}</span>@endif</a>
      <div class="grp">Proyek perizinan</div>
      <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index','projects.show','projects.edit','tasks.show') ? 'on' : '' }}">▤ Daftar proyek</a>
      @if(auth()->user()->isManager())<a href="{{ route('projects.create') }}" class="{{ $nav('projects.create') }}">＋ Proyek baru</a>@endif
      <a href="{{ route('gantt.portfolio') }}" class="{{ $nav('gantt.*') }}">▬ Gantt portofolio</a>
      <a href="{{ route('workload') }}" class="{{ $nav('workload') }}">◫ Beban kerja PIC</a>
      <a href="{{ route('forecast') }}" class="{{ $nav('forecast') }}">↗ Proyeksi</a>
      <a href="{{ route('licenses.index') }}" class="{{ $nav('licenses.*') }}">▣ Register izin</a>
      <div class="grp">Template & referensi</div>
      <a href="{{ route('wbs-templates.index') }}" class="{{ $nav('wbs-templates.*') }}">⧉ Template WBS</a>
      <a href="{{ route('permit-types.index') }}" class="{{ $nav('permit-types.*') }}">≡ Jenis izin & langkah</a>
      <a href="{{ route('templates.index') }}" class="{{ $nav('templates.*') }}">◇ Kelompok izin</a>
      <a href="{{ route('regulations.index') }}" class="{{ $nav('regulations.*') }}">§ Peraturan</a>
      <a href="{{ route('items.index') }}" class="{{ $nav('items.*') }}">⌗ Item code</a>
      <a href="{{ route('suppliers.index') }}" class="{{ $nav('suppliers.*') }}">⚑ Pemasok</a>
      <a href="{{ route('users.index') }}" class="{{ $nav('users.*') }}">☺ Pengguna & bagian</a>
    </nav>
  </aside>
  <div class="main">
    <header class="top">
      <div class="crumb">@yield('crumb', 'Izinara')</div>
      <div class="sp"></div>
      <button type="button" class="btn ghost sm help-btn" data-help-open title="Bantuan untuk layar ini (tekan ?)">？ Bantuan</button>
      <a class="bell" href="{{ route('notices.index') }}" title="Kotak masuk">🔔 @if($unread)<span class="n">{{ $unread }}</span>@endif</a>
      <div class="me"><span class="avatar">{{ $me->initials() }}</span><div>{{ $me->name }}<br><small>{{ $me->roleLabel() }}{{ $me->party ? ' · '.$me->party->shortName() : '' }}</small></div></div>
      <form method="post" action="{{ route('logout') }}">@csrf<button class="btn ghost sm">Keluar</button></form>
    </header>
    <main class="content">
      @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
      @if($errors->any())<div class="alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
      @yield('content')
    </main>
  </div>
  @include('help._panel')
</div>
@php $izPeople = \App\Models\User::where('active', true)->get(['username', 'name'])->map(fn ($u) => ['h' => $u->username, 'n' => $u->name])->concat(\App\Models\Party::all()->map(fn ($p) => ['h' => $p->code, 'n' => 'Bagian '.$p->shortName()]))->values(); @endphp
<script>window.IZ={csrf:document.querySelector('meta[name=csrf-token]').content,mentionUrl:null,people:{!! json_encode($izPeople) !!}};</script>
<script src="{{ asset('vendor/chart.umd.js') }}"></script>
<script src="{{ asset('vendor/frappe-gantt.min.js') }}"></script>
<script src="{{ asset('js/izinara.js') }}?v=3"></script>
@stack('scripts')
</body>
</html>
