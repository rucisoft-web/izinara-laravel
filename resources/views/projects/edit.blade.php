@extends('layouts.app')
@section('title', 'Ubah '.$project->code)
@section('crumb')<a href="{{ route('projects.index') }}">Proyek</a> / <a href="{{ route('projects.show', $project) }}">{{ $project->code }}</a> / Ubah @endsection
@section('content')
<h1>Ubah {{ $project->code }}</h1>
<form method="post" action="{{ route('projects.update', $project) }}" class="card" style="max-width:900px">
  @csrf @method('PUT')
  <div class="field"><label>Nama proyek</label><input type="text" name="name" value="{{ $project->name }}" required></div>
  <div class="field"><label>Uraian kebutuhan</label><textarea name="need_description">{{ $project->need_description }}</textarea></div>
  <div class="grid g3">
    <div class="field"><label>Status</label><select name="status">@foreach(\App\Models\Project::STATUSES as $k=>$v)<option value="{{ $k }}" @selected($project->status===$k)>{{ $v }}</option>@endforeach</select></div>
    <div class="field"><label>Prioritas</label><select name="priority">@foreach(\App\Models\Project::PRIORITIES as $k=>$v)<option value="{{ $k }}" @selected($project->priority===$k)>{{ $v }}</option>@endforeach</select></div>
    <div class="field"><label>Manajer</label><select name="manager_id">@foreach($managers as $m)<option value="{{ $m->id }}" @selected($project->manager_id===$m->id)>{{ $m->name }}</option>@endforeach</select></div>
    <div class="field"><label>Kelompok izin</label><select name="template_id"><option value="">–</option>@foreach($templates as $t)<option value="{{ $t->id }}" @selected($project->template_id===$t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="field"><label>Pemasok</label><select name="supplier_id"><option value="">–</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected($project->supplier_id===$s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="field"><label>No. PO</label><input type="text" name="po_ref" value="{{ $project->po_ref }}"></div>
    <div class="field"><label>Mulai</label><input type="date" name="start_date" value="{{ $project->start_date->toDateString() }}"></div>
    <div class="field"><label>ETA</label><input type="date" name="eta" value="{{ $project->eta?->toDateString() }}"></div>
    <div class="field"><label>Tenggat</label><input type="date" name="target_date" value="{{ $project->target_date->toDateString() }}"></div>
  </div>
  <button class="btn">Simpan</button>
</form>
@endsection
