@extends('layouts.app')
@section('title', 'Kelompok izin')
@section('crumb', 'Kelompok izin')
@section('content')
<div class="page-head"><div><h1>Kelompok izin</h1><p>Setiap item code masuk ke satu kelompok. Kelompok menentukan paket izin yang wajib (W) dan opsional (O). Halal, karantina dan persetujuan impor adalah persyaratan tambahan di dalam kelompok; kelompok <b>Sertifikasi halal</b> dipakai untuk proyek yang khusus mengurus sertifikat halal.</p></div></div>
<div class="card flush"><div class="scroll-x"><table class="tbl small-table">
<thead><tr><th>Kelompok</th><th class="num">Item</th>@foreach($types as $t)<th title="{{ $t->name }}" style="writing-mode:vertical-rl;transform:rotate(180deg);height:150px">{{ \Illuminate\Support\Str::limit($t->name, 28) }}</th>@endforeach</tr></thead><tbody>
@foreach($templates as $tpl)
<tr><td><b>{{ $tpl->name }}</b><br><small>{{ $tpl->groupLabel() }}</small></td><td class="num">{{ $tpl->items_count }}</td>
@foreach($types as $t)@php $l = $tpl->lines->firstWhere('permit_type_id', $t->id); @endphp<td style="text-align:center">{!! $l ? ($l->mandatory ? '<b style="color:var(--brand)">W</b>' : '<span class="muted">O</span>') : '' !!}</td>@endforeach</tr>
@endforeach
</tbody></table></div></div>
@endsection
