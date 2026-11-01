@extends('layouts.app')
@section('title', 'Proyek baru')
@section('crumb')<a href="{{ route('projects.index') }}">Proyek</a> / Baru @endsection
@section('content')
<div class="page-head"><div><h1>Proyek perizinan baru</h1><p>Isi kebutuhan izin, pilih item & PIC, lalu salin WBS dari template agar tidak mengetik ulang.</p></div></div>
<form method="post" action="{{ route('projects.store') }}">
@csrf
<div class="grid g-side">
<div>
  <div class="card">
    <h2>1 · Kebutuhan izin</h2>
    <div class="grid g2">
      <div class="field"><label>Nama proyek</label><input type="text" name="name" required value="{{ old('name', request('nama')) }}" placeholder="mis. Impor ribeye chilled Australia – PO 4500-0500"></div>
      <div class="field"><label>Pemicu kebutuhan</label><select name="trigger">@foreach(\App\Models\Project::TRIGGERS as $k=>$v)<option value="{{ $k }}" @selected(old('trigger', request('pemicu'))===$k)>{{ $v }}</option>@endforeach</select></div>
    </div>
    <div class="field"><label>Uraian kebutuhan</label><textarea name="need_description" placeholder="Mengapa izin diperlukan, rencana impor/penjualan, catatan khusus">{{ old('need_description') }}</textarea></div>
    <div class="grid g3">
      <div class="field"><label>Kelompok izin</label><select name="template_id"><option value="">(otomatis dari item)</option>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
      <div class="field"><label>Eksportir / unit usaha asal</label><select name="supplier_id"><option value="">–</option>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
      <div class="field"><label>No. PO / referensi</label><input type="text" name="po_ref" value="{{ old('po_ref') }}"></div>
      <div class="field"><label>Tanggal mulai</label><input type="date" name="start_date" required value="{{ old('start_date', today()->toDateString()) }}"></div>
      <div class="field"><label>ETA (bila pengapalan)</label><input type="date" name="eta" value="{{ old('eta') }}"><div class="help">Karantina & PIB dijadwalkan mengikuti ETA.</div></div>
      <div class="field"><label>Tenggat semua izin terbit</label><input type="date" name="target_date" required value="{{ old('target_date', request('tenggat', today()->addDays(60)->toDateString())) }}"></div>
      <div class="field"><label>Prioritas</label><select name="priority">@foreach(\App\Models\Project::PRIORITIES as $k=>$v)<option value="{{ $k }}" @selected($k==='normal')>{{ $v }}</option>@endforeach</select></div>
      <div class="field"><label>Manajer proyek</label><select name="manager_id">@foreach($managers as $m)<option value="{{ $m->id }}" @selected($m->id===auth()->id())>{{ $m->name }}</option>@endforeach</select></div>
    </div>
  </div>

  <div class="card">
    <div class="card-h"><h2>2 · Item code</h2><div class="sp"></div><input id="item-filter" type="search" placeholder="Saring item…" style="width:220px"></div>
    <div style="max-height:300px;overflow:auto">
    <table class="tbl small-table"><thead><tr><th></th><th>Item code</th><th>Nama</th><th>HS</th><th>Kelompok</th><th>Qty (kg)</th></tr></thead><tbody>
    @foreach($items as $i)
      <tr data-item="{{ strtolower($i->code.' '.$i->name.' '.$i->hs_code.' '.$i->template?->name) }}">
        <td><input type="checkbox" name="items[]" value="{{ $i->id }}"></td><td><code>{{ $i->code }}</code></td><td>{{ $i->name }}</td><td>{{ $i->hs_code }}</td><td><small>{{ $i->template?->name }}</small></td>
        <td><input type="number" name="qty[{{ $i->id }}]" min="0" step="1" style="width:90px"></td>
      </tr>
    @endforeach
    </tbody></table></div>
    <div class="help">Izin yang dibutuhkan dibaca dari kelompok izin tiap item. Izin yang sudah ada di register dan masih berlaku pada ETA/tenggat dipakai ulang.</div>
  </div>

  <div class="card">
    <h2>3 · Struktur kerja (WBS)</h2>
    <label class="check"><input type="radio" name="source" value="wbs" {{ old('source', 'wbs')==='wbs' ? 'checked' : '' }}> <span><b>Salin dari template WBS</b> — paket izin, kegiatan, durasi, dependensi, milestone, peraturan & dokumen wajib disalin otomatis.</span></label>
    <div data-source="wbs" class="field" style="margin:8px 0 12px 24px">
      <select name="wbs_template_id"><option value="">Pilih template…</option>
        @foreach($wbsTemplates->groupBy(fn($t)=>$t->groupLabel()) as $g => $list)<optgroup label="{{ $g }}">@foreach($list as $t)<option value="{{ $t->id }}" @selected($selectedWbs==$t->id)>{{ $t->name }} ({{ $t->taskCount() }} kegiatan, ±{{ $t->totalDays() }} hari)</option>@endforeach</optgroup>@endforeach
      </select>
      <label class="check" style="margin-top:6px"><input type="checkbox" name="skip_covered" value="1" checked> Lewati paket izin yang sudah dimiliki dan masih berlaku</label>
    </div>
    <label class="check"><input type="radio" name="source" value="otomatis" {{ old('source')==='otomatis' ? 'checked' : '' }}> <span><b>Susun otomatis dari item code</b> — memakai langkah standar setiap jenis izin di kelompok item.</span></label>
    <label class="check" style="margin-top:8px"><input type="radio" name="source" value="proyek" {{ old('source')==='proyek' ? 'checked' : '' }}> <span><b>Salin dari proyek lain</b> — pakai struktur proyek sejenis yang pernah dikerjakan.</span></label>
    <div data-source="proyek" class="field" style="margin:8px 0 0 24px"><select name="source_project_id"><option value="">Pilih proyek…</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->code }} · {{ $p->name }}</option>@endforeach</select></div>
  </div>
</div>
<div>
  <div class="card">
    <h2>4 · PIC per bagian</h2>
    <p class="help" style="margin-top:-6px">Kegiatan otomatis diberikan ke PIC bagian yang mengerjakannya. Bisa diubah nanti.</p>
    @foreach($parties as $party)
      <div class="field"><label>{{ $party->shortName() }}</label>
        <select name="pic[{{ $party->id }}]"><option value="">–</option>@foreach($party->users->where('active', true) as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
    @endforeach
  </div>
  <button class="btn" style="width:100%;justify-content:center">Buat proyek & susun jadwal</button>
</div>
</div>
</form>
@endsection
