{{-- Isi satu topik bantuan: dipakai panel Bantuan (ringkas) dan halaman /panduan --}}
<div class="help-topic">
  <p class="help-lead">{{ $t['ringkas'] }}</p>

  @if(!empty($t['konsep']))
  <h4>Konsep</h4>
  @foreach($t['konsep'] as $p)<p>{{ $p }}</p>@endforeach
  @endif

  @if(!empty($t['langkah']))
  <h4>Cara input / langkah</h4>
  <ol class="help-steps">@foreach($t['langkah'] as $s)<li>{{ $s }}</li>@endforeach</ol>
  @endif

  @if(!empty($t['isian']))
  <h4>Penjelasan isian</h4>
  <dl class="help-fields">@foreach($t['isian'] as $f => $d)<dt>{{ $f }}</dt><dd>{{ $d }}</dd>@endforeach</dl>
  @endif

  @if(!empty($t['gambar']))
  <h4>Tampilan layar</h4>
  @foreach($t['gambar'] as $file => $cap)
    @if(file_exists(public_path('img/bantuan/'.$file)))
    <figure class="help-shot"><a href="{{ asset('img/bantuan/'.$file) }}" target="_blank" rel="noopener"><img src="{{ asset('img/bantuan/'.$file) }}" alt="{{ $cap }}" loading="lazy"></a><figcaption>{{ $cap }}</figcaption></figure>
    @endif
  @endforeach
  @endif
</div>
