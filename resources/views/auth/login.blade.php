<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk · Izinara</title>
<link rel="icon" href="{{ asset('favicon.png') }}"><link rel="stylesheet" href="{{ asset('css/izinara.css') }}?v=2"></head>
<body>
<div class="login-wrap">
  <form class="login" method="post" action="{{ url('/login') }}">
    @csrf
    <div class="brand"><img src="{{ asset('img/izinara.png') }}" alt=""><div><b>Izinara</b><small class="muted">Manajemen proyek perizinan impor pangan</small></div></div>
    @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="alert bad">{{ $errors->first() }}</div>@endif
    <div class="field"><label>Nama pengguna atau email</label><input type="text" name="login" value="{{ old('login') }}" required autofocus></div>
    <div class="field"><label>Kata sandi</label><input type="password" name="password" required></div>
    <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
    <button class="btn" style="width:100%;justify-content:center;margin-top:12px">Masuk</button>
    @if(\App\Models\User::where('username','sari.manajer')->exists())
    <div class="demo-accounts"><b>Akun demo</b> (kata sandi <code>izinara</code>): <code>sari.manajer</code> manajer · <code>rina.ml</code> BPOM · <code>hasan.halal</code> halal · <code>dewi.izin</code> perizinan · <code>budi.purchasing</code> purchasing · <code>andi.impor</code> impor · <code>tono.direksi</code> pemantau. Admin: <code>admin</code>.</div>
    @endif
    <div class="pub-links"><a href="{{ route('landing') }}">← Tentang Izinara</a> · <a href="{{ route('help.index') }}">Panduan</a></div>
  </form>
</div>
</body>
</html>
