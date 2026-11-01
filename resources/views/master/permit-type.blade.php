@extends('layouts.app')
@section('title', $type->name)
@section('crumb')<a href="{{ route('permit-types.index') }}">Jenis izin</a> / {{ $type->code }} @endsection
@section('content')
<div class="page-head"><div><h1>{{ $type->name }}</h1><p>{{ $type->authority }} · {{ $type->system }} · {{ $type->party->shortName() }} · {{ $type->scopeLabel() }} · {{ $type->standardDays() }} hari standar{{ $type->validity_months ? ' · berlaku '.$type->validity_months.' bulan' : '' }}</p></div></div>
<div class="grid g2">
  <div class="card"><h2>Keterangan</h2><p style="white-space:pre-line;margin-top:0">{{ $type->description }}</p></div>
  <div class="card"><h2>Dasar hukum</h2><p style="margin-top:0">{{ $type->legal_basis }}</p>@include('partials.regs', ['regs' => $type->regulations])</div>
</div>
<h2>Langkah standar</h2>
@foreach($type->steps as $i => $s)
<div class="card">
  <div class="card-h"><h3>{{ $i+1 }}. {{ $s->name }}</h3><div class="sp"></div><span class="tag">{{ $s->party->shortName() }}</span><span class="tag">{{ $s->duration_days }} hari</span></div>
  <p style="white-space:pre-line;margin:0 0 6px">{{ $s->instructions }}</p>
  @if($s->deliverable)<p style="margin:0 0 6px"><b>Hasil:</b> {{ $s->deliverable }}</p>@endif
  <div>@include('partials.regs', ['regs' => $s->regulations])</div>
  @if($s->docSpecs->isNotEmpty())<table class="tbl small-table" style="margin-top:8px"><thead><tr><th>Dokumen wajib</th><th>Kriteria verifikasi</th><th>Verifikator</th></tr></thead><tbody>
    @foreach($s->docSpecs as $d)<tr><td>{{ $d->name }}{{ $d->check_expiry ? ' ⏱' : '' }}</td><td style="white-space:pre-line"><small>{{ $d->criteria }}</small></td><td><small>{{ $d->verifier?->shortName() }}</small></td></tr>@endforeach</tbody></table>@endif
  @if(auth()->user()->isManager())
  <details class="box" style="margin-top:8px"><summary>Ubah langkah</summary><div>
    <form method="post" action="{{ route('steps.update', $s) }}">@csrf @method('PUT')
      <div class="grid g3"><div class="field"><label>Nama</label><input type="text" name="name" value="{{ $s->name }}"></div>
        <div class="field"><label>Bagian</label><select name="party_id">@foreach($parties as $p)<option value="{{ $p->id }}" @selected($s->party_id===$p->id)>{{ $p->shortName() }}</option>@endforeach</select></div>
        <div class="field"><label>Durasi (hari)</label><input type="number" name="duration_days" value="{{ $s->duration_days }}" min="1"></div></div>
      <div class="field"><label>Petunjuk</label><textarea name="instructions">{{ $s->instructions }}</textarea></div>
      <div class="field"><label>Dokumen hasil</label><input type="text" name="deliverable" value="{{ $s->deliverable }}"></div>
      <label>Referensi peraturan (dengan pasal)</label>
      <div style="max-height:200px;overflow:auto;margin-bottom:8px">@foreach($regulations as $r)@php $sel = $s->regulations->firstWhere('id',$r->id); @endphp
        <div class="row"><label class="check" style="flex:1"><input type="checkbox" name="regulations[]" value="{{ $r->id }}" @checked($sel)> {{ $r->name }} – {{ \Illuminate\Support\Str::limit($r->title, 55) }}</label><input type="text" name="article[{{ $r->id }}]" value="{{ $sel?->pivot->article }}" placeholder="Pasal" style="width:100px"></div>@endforeach</div>
      <button class="btn sm">Simpan langkah</button>
    </form></div></details>
  @endif
</div>
@endforeach
@endsection
