@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Panduan')
@section('crumb', 'Panduan pengguna')
@section('content')
<div class="{{ auth()->check() ? '' : 'pub-wrap' }}">
<div class="page-head"><div><h1>Panduan pengguna Izinara</h1><p>Penjelasan konsep dan cara mengisi data di setiap layar, lengkap dengan tangkapan layar dari data demo. Tombol <b>？ Bantuan</b> di kanan atas setiap layar membuka topik yang sesuai.</p></div></div>
<div class="help-grid">
@foreach($cfg['urutan'] as $grp => $keys)
  <div class="card">
    <h3>{{ $grp }}</h3>
    @foreach($keys as $k)@php $t = $cfg['topik'][$k]; $img = collect($t['gambar'] ?? [])->keys()->first(fn ($f) => file_exists(public_path('img/bantuan/'.$f))); @endphp
      <a class="help-card" href="{{ route('help.show', $k) }}">
        @if($img)<img src="{{ asset('img/bantuan/'.$img) }}" alt="" loading="lazy">@endif
        <div><b>{{ $t['judul'] }}</b><small>{{ $t['ringkas'] }}</small></div>
      </a>
    @endforeach
  </div>
@endforeach
</div>
</div>
@endsection
