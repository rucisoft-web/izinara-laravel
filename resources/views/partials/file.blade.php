<div class="file">
  @if($a->isImage())<img class="thumb" src="{{ route('attachments.show', $a) }}" alt="">@else<span class="ic {{ $a->icon() }}">{{ $a->icon() }}</span>@endif
  <div class="nm"><a href="{{ route('attachments.show', $a) }}" target="_blank">{{ $a->original_name }}</a><br>
    <small>{{ $a->humanSize() }} · {{ $a->uploader?->name }} · {{ $a->created_at->locale('id')->diffForHumans() }}{{ $a->description ? ' · '.$a->description : '' }}</small></div>
  <a class="btn ghost sm" href="{{ route('attachments.download', $a) }}">Unduh</a>
  @if(!empty($canEdit))
  <form method="post" action="{{ route('attachments.destroy', $a) }}" data-confirm="Hapus lampiran ini?">@csrf @method('DELETE')<button class="btn warn sm">✕</button></form>
  @endif
</div>
