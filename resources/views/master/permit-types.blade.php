@extends('layouts.app')
@section('title', 'Jenis izin')
@section('crumb', 'Jenis izin & langkah standar')
@section('content')
<div class="page-head"><div><h1>Jenis izin & langkah standar</h1><p>{{ $types->count() }} jenis izin dengan {{ $types->sum('steps_count') }} langkah standar. Langkah standar menjadi kegiatan saat proyek disusun otomatis.</p></div></div>
<div class="card flush"><table class="tbl">
<thead><tr><th>#</th><th>Jenis izin</th><th>Instansi / sistem</th><th>Bagian</th><th>Cakupan</th><th class="num">Langkah</th><th class="num">Hari (std)</th><th class="num">Masa berlaku</th><th>Dasar hukum</th></tr></thead><tbody>
@foreach($types as $t)
<tr><td>{{ $t->sequence }}</td><td><a href="{{ route('permit-types.show', $t) }}"><b>{{ $t->name }}</b></a><br><small><code>{{ $t->code }}</code>{{ $t->prerequisites ? ' · prasyarat: '.implode(', ', $t->prerequisites) : '' }}</small></td>
  <td><small>{{ $t->authority }}<br>{{ $t->system }}</small></td><td><small>{{ $t->party->shortName() }}</small></td><td><small>{{ $t->scopeLabel() }}</small></td>
  <td class="num">{{ $t->steps_count }}</td><td class="num">{{ $t->standardDays() }}</td><td class="num">{{ $t->validity_months ? $t->validity_months.' bln' : '–' }}</td>
  <td style="max-width:260px">@include('partials.regs', ['regs' => $t->regulations])</td></tr>
@endforeach
</tbody></table></div>
@endsection
