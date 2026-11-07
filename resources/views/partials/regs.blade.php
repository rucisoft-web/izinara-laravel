{{-- $regs: koleksi Regulation (pivot article opsional) --}}
@forelse($regs as $r)
  <a class="reg {{ in_array($r->status, ['perlu_konfirmasi','dicabut']) ? 'warn' : '' }}" href="{{ route('regulations.index') }}#reg-{{ $r->id }}" title="{{ $r->label() }} — {{ $r->statusLabel() }}{{ $r->note ? ' · '.$r->note : '' }}">§ {{ $r->name }}{{ ($r->pivot->article ?? null) ? ' '.$r->pivot->article : '' }}</a>
@empty
  <span class="muted">–</span>
@endforelse
