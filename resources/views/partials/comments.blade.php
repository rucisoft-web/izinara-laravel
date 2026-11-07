{{-- $comments --}}
@if($comments->isEmpty())
  <div class="empty" style="padding:14px">Belum ada diskusi.</div>
@else
<ul class="feed">
@foreach($comments as $c)
  <li class="comment {{ $c->kind === 'perlu_tindakan' ? 'action' : '' }} {{ $c->resolved_at ? 'resolved' : '' }}">
    <span class="avatar">{{ $c->user?->initials() }}</span>
    <div style="flex:1;min-width:0">
      <div class="meta"><b style="color:var(--ink)">{{ $c->user?->name }}</b> · {{ $c->user?->party?->shortName() }} · {{ $c->created_at->locale('id')->diffForHumans() }}
        @if($c->kind !== 'komentar') {!! badge($c->kind === 'perlu_tindakan' ? ($c->resolved_at ? 'selesai' : 'menunggu') : 'info', \App\Models\TaskComment::KINDS[$c->kind].($c->resolved_at ? ' · selesai' : '')) !!} @endif
      </div>
      <div class="body">{!! $c->bodyHtml() !!}</div>
      @if($c->mentions->isNotEmpty())<div class="meta">Attention: @foreach($c->mentions as $m)<span class="mention">{{ $m->label() }}</span> @endforeach</div>@endif
      @if(!empty($showContext))
        @if($c->document)<a class="ctx" href="{{ route('tasks.show', $c->task_id) }}#doc-{{ $c->task_document_id }}">📄 {{ $c->document->name }}</a>
        @elseif($c->task)<a class="ctx" href="{{ route('tasks.show', $c->task) }}#diskusi">{{ $c->task->wbs }} {{ $c->task->name }}</a>@endif
      @endif
      @if($c->kind === 'perlu_tindakan' && auth()->user()->role !== 'viewer')
        <form method="post" action="{{ route('comments.resolve', $c) }}" class="inline-form">@csrf<button class="btn ghost sm" style="margin-top:4px">{{ $c->resolved_at ? 'Buka lagi' : 'Tandai sudah ditindaklanjuti' }}</button></form>
      @endif
    </div>
  </li>
@endforeach
</ul>
@endif
