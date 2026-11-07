@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', $t['judul'].' · Panduan')
@section('crumb')<a href="{{ route('help.index') }}">Panduan</a> › {{ $t['judul'] }}@endsection
@section('content')
<div class="{{ auth()->check() ? '' : 'pub-wrap' }}">
<div class="help-layout">
  <aside class="help-toc card">
    @foreach($cfg['urutan'] as $grp => $keys)
      <div class="grp">{{ $grp }}</div>
      @foreach($keys as $k)<a href="{{ route('help.show', $k) }}" class="{{ $k === $key ? 'on' : '' }}">{{ $cfg['topik'][$k]['judul'] }}</a>@endforeach
    @endforeach
  </aside>
  <article class="card help-article">
    <h1>{{ $t['judul'] }}</h1>
    @include('help._topic', ['t' => $t])
    @auth
      @php $first = collect($t['routes'])->first(fn ($r) => \Illuminate\Support\Facades\Route::has($r) && ! str_contains(\Illuminate\Support\Facades\Route::getRoutes()->getByName($r)->uri(), '{')); @endphp
      @if($first)<p><a class="btn" href="{{ route($first) }}">Buka layar ini »</a></p>@endif
    @endauth
  </article>
</div>
</div>
@endsection
