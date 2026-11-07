{{-- $task, $doc? --}}
<form method="post" action="{{ route('attachments.store', $task) }}" enctype="multipart/form-data">
  @csrf
  @if(!empty($doc))<input type="hidden" name="task_document_id" value="{{ $doc->id }}">@endif
  <div class="drop">
    <input type="file" name="files[]" multiple accept="{{ collect(\App\Models\Attachment::ALLOWED)->map(fn($e)=>'.'.$e)->implode(',') }}">
    <div class="help">Seret file ke sini atau pilih beberapa sekaligus. PDF, Excel, Word, PowerPoint, JPG/PNG, ZIP, email (.msg/.eml), maks. 25 MB per file.</div>
    <div class="drop-list help"></div>
  </div>
  <div class="row" style="margin-top:8px">
    <input type="text" name="description" placeholder="Keterangan (opsional)" class="grow" style="flex:1">
    @if(!empty($doc) && $doc->spec?->check_expiry)<input type="date" name="expiry_date" title="Masa berlaku dokumen" value="{{ $doc->expiry_date?->toDateString() }}" style="width:auto">@endif
    <button class="btn sm">Unggah</button>
  </div>
</form>
