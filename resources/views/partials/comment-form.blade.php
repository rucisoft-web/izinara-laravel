{{-- $action, $parties, $users, $placeholder? --}}
@if(auth()->user()->role !== 'viewer')
<form method="post" action="{{ $action }}" class="comment-form">
  @csrf
  <div class="field"><textarea name="body" data-mention rows="3" required placeholder="{{ $placeholder ?? 'Tulis komentar. Ketik @ untuk menyebut orang atau bagian, mis. @purchasing atau @budi.purchasing' }}"></textarea></div>
  <div class="row">
    <select name="kind" style="width:auto">
      @foreach(\App\Models\TaskComment::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
    </select>
    <select name="mention_parties[]" style="width:auto" title="Beri perhatian (attention) ke bagian">
      <option value="">Attention ke bagian…</option>
      @foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->shortName() }}</option>@endforeach
    </select>
    <select name="mention_users[]" style="width:auto" title="Beri perhatian ke orang">
      <option value="">Attention ke orang…</option>
      @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}{{ $u->party ? ' – '.$u->party->code : '' }}</option>@endforeach
    </select>
    <div class="grow"></div>
    <button class="btn sm">Kirim</button>
  </div>
</form>
@endif
