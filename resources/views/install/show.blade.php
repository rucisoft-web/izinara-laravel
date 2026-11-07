<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Instalasi · Izinara</title>
<link rel="icon" href="{{ asset('favicon.png') }}"><link rel="stylesheet" href="{{ asset('css/izinara.css') }}?v=3"></head>
<body>
<div class="login-wrap">
  <form class="login install" method="post" action="{{ url('/install') }}">
    @csrf
    <div class="brand"><img src="{{ asset('img/izinara.png') }}" alt=""><div><b>Instalasi Izinara</b><small class="muted">Sekali jalan: hubungkan database, buat tabel & akun admin</small></div></div>
    @if($errors->any())<div class="alert bad">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <h3>1. Pemeriksaan server</h3>
    <ul class="checks">@foreach($checks as $label => $ok)<li class="{{ $ok ? 'ok' : 'bad' }}">{{ $ok ? '✓' : '✕' }} {{ $label }}</li>@endforeach</ul>

    <h3>2. Database MySQL</h3>
    <p class="help">Buat database & penggunanya lebih dulu di cPanel → <b>MySQL® Databases</b> (beri hak ALL PRIVILEGES). Nama biasanya berawalan nama akun cPanel, mis. <code>akun_izinara</code>.</p>
    <div class="grid g2">
      <div class="field"><label>Host</label><input type="text" name="db_host" value="{{ old('db_host', 'localhost') }}" required></div>
      <div class="field"><label>Port</label><input type="number" name="db_port" value="{{ old('db_port', 3306) }}" required></div>
    </div>
    <div class="field"><label>Nama database</label><input type="text" name="db_database" value="{{ old('db_database') }}" required></div>
    <div class="grid g2">
      <div class="field"><label>Pengguna database</label><input type="text" name="db_username" value="{{ old('db_username') }}" required></div>
      <div class="field"><label>Kata sandi database</label><input type="password" name="db_password"></div>
    </div>

    <h3>3. Aplikasi & admin</h3>
    <div class="field"><label>Alamat aplikasi (URL)</label><input type="url" name="app_url" value="{{ old('app_url', $url) }}" required></div>
    <div class="field"><label>Email admin</label><input type="email" name="admin_email" value="{{ old('admin_email') }}" required></div>
    <div class="grid g2">
      <div class="field"><label>Kata sandi admin (min. 8)</label><input type="password" name="admin_password" required minlength="8"></div>
      <div class="field"><label>Ulangi kata sandi</label><input type="password" name="admin_password_confirmation" required minlength="8"></div>
    </div>
    <label class="check"><input type="checkbox" name="demo" value="1" @checked(old('demo', true))> <span>Muat data demo (18 proyek, lampiran contoh, akun demo dengan kata sandi <code>izinara</code>) — untuk latihan dan pengenalan aplikasi</span></label>

    <button class="btn" style="width:100%;justify-content:center;margin-top:16px" onclick="this.disabled=true;this.textContent='Memasang… (bisa 1–2 menit)';this.form.submit()">Pasang Izinara</button>
    <p class="help">Setelah terpasang, halaman ini otomatis ditutup.</p>
  </form>
</div>
</body>
</html>
