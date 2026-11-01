<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Izinara') · Izinara</title>
<meta name="description" content="Izinara: aplikasi internal PT Indoguna Utama untuk pengurusan izin impor dan izin edar pangan.">
<meta name="robots" content="noindex">
<link rel="icon" href="{{ asset('favicon.png') }}">
<link rel="stylesheet" href="{{ asset('css/izinara.css') }}?v=3">
</head>
<body class="pub">
<header class="pub-top">
  <a class="pub-brand" href="{{ route('landing') }}"><img src="{{ asset('img/izinara.png') }}" alt=""> Izinara</a>
  <nav>
    <a href="{{ route('landing') }}#fitur">Isi aplikasi</a>
    <a href="{{ route('landing') }}#peraturan">Peraturan</a>
    <a href="{{ route('landing') }}#alur">Alur kerja</a>
    <a href="{{ route('help.index') }}">Panduan</a>
  </nav>
  <div class="sp"></div>
  @auth<a class="btn sm" href="{{ route('dashboard') }}">Buka aplikasi</a>@else<a class="btn sm" href="{{ route('login') }}">Masuk</a>@endauth
</header>
@yield('content')
<footer class="pub-foot">
  <div>Izinara · aplikasi internal PT Indoguna Utama</div>
  <div class="sp"></div>
  <a href="{{ route('help.index') }}">Panduan pengguna</a> · <a href="{{ route('login') }}">Masuk</a>
</footer>
</body>
</html>
