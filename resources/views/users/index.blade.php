@extends('layouts.app')
@section('title', 'Pengguna & bagian')
@section('crumb', 'Pengguna & bagian')
@section('content')
@php $admin = auth()->user()->isAdmin(); @endphp
<div class="page-head"><div><h1>Pengguna & bagian</h1><p>Peran: <b>Administrator</b> (semua), <b>Manajer perizinan</b> (membuat proyek, menetapkan PIC, menggeser jadwal, verifikasi), <b>PIC</b> (mengerjakan kegiatan bagiannya), <b>Pemantau</b> (baca saja, mis. direksi). Kapasitas = hari kerja efektif per minggu untuk perhitungan beban.</p></div></div>
<div class="grid g2">
@foreach($parties as $p)
<div class="card"><div class="card-h"><h3>{{ $p->name }}</h3><div class="sp"></div><span class="tag"><code>@{{ $p->code }}</code></span></div>
<p class="help" style="margin-top:-6px">{{ $p->responsibility }}</p>
<table class="tbl small-table"><tbody>
@foreach($users->where('party_id', $p->id) as $u)<tr><td><span class="avatar">{{ $u->initials() }}</span> {{ $u->name }}<br><small><code>@{{ $u->username }}</code> · {{ $u->job_title }}</small></td><td><small>{{ $u->roleLabel() }}</small></td><td class="num"><small>{{ $u->open_tasks_count }} tugas · {{ $u->weekly_capacity }} h/mgg</small></td><td>{!! $u->active ? '' : badge('batal','Nonaktif') !!}</td></tr>@endforeach
</tbody></table></div>
@endforeach
</div>
<div class="card flush"><div class="card-h"><h2>Semua pengguna</h2></div><table class="tbl"><thead><tr><th>Nama</th><th>Login</th><th>Bagian</th><th>Peran</th><th class="num">Kapasitas</th><th>Status</th>@if($admin)<th></th>@endif</tr></thead><tbody>
@foreach($users as $u)
<tr><td>{{ $u->name }}<br><small>{{ $u->job_title }}</small></td><td><code>{{ $u->username }}</code><br><small>{{ $u->email }}</small></td><td>{{ $u->party?->shortName() ?? '–' }}</td><td>{{ $u->roleLabel() }}</td><td class="num">{{ $u->weekly_capacity }} h</td><td>{!! $u->active ? badge('aktif','Aktif') : badge('batal','Nonaktif') !!}</td>
@if($admin)<td><details><summary class="btn ghost sm">Ubah</summary><form method="post" action="{{ route('users.update', $u) }}" style="min-width:280px;margin-top:6px">@csrf @method('PUT')
  <div class="field"><label>Nama</label><input type="text" name="name" value="{{ $u->name }}"></div><div class="field"><label>Email</label><input type="email" name="email" value="{{ $u->email }}"></div>
  <div class="field"><label>Bagian</label><select name="party_id"><option value="">–</option>@foreach($parties as $p)<option value="{{ $p->id }}" @selected($u->party_id===$p->id)>{{ $p->shortName() }}</option>@endforeach</select></div>
  <div class="field"><label>Peran</label><select name="role">@foreach(\App\Models\User::ROLES as $k=>$v)<option value="{{ $k }}" @selected($u->role===$k)>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Jabatan</label><input type="text" name="job_title" value="{{ $u->job_title }}"></div><div class="field"><label>Telepon</label><input type="text" name="phone" value="{{ $u->phone }}"></div>
  <div class="field"><label>Kapasitas (hari/minggu)</label><input type="number" name="weekly_capacity" value="{{ $u->weekly_capacity }}" min="1" max="7"></div>
  <div class="field"><label>Kata sandi baru</label><input type="password" name="password" placeholder="(kosongkan bila tetap)"></div>
  <label class="check"><input type="checkbox" name="active" value="1" @checked($u->active)> Aktif</label><button class="btn sm" style="margin-top:6px">Simpan</button></form></details></td>@endif</tr>
@endforeach
</tbody></table></div>
@if($admin)
<details class="box"><summary>＋ Tambah pengguna</summary><div><form method="post" action="{{ route('users.store') }}">@csrf<div class="grid g3">
  <div class="field"><label>Nama</label><input type="text" name="name" required></div><div class="field"><label>Login</label><input type="text" name="username" required></div>
  <div class="field"><label>Email</label><input type="email" name="email" required></div><div class="field"><label>Kata sandi</label><input type="password" name="password" required></div>
  <div class="field"><label>Bagian</label><select name="party_id"><option value="">–</option>@foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->shortName() }}</option>@endforeach</select></div>
  <div class="field"><label>Peran</label><select name="role">@foreach(\App\Models\User::ROLES as $k=>$v)<option value="{{ $k }}" @selected($k==='pic')>{{ $v }}</option>@endforeach</select></div>
  <div class="field"><label>Jabatan</label><input type="text" name="job_title"></div><div class="field"><label>Telepon</label><input type="text" name="phone"></div>
  <div class="field"><label>Kapasitas (hari/minggu)</label><input type="number" name="weekly_capacity" value="5" min="1" max="7"></div>
</div><button class="btn sm">Tambah</button></form></div></details>
@endif
@endsection
