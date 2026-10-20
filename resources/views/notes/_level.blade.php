@if ($note->level)
    <span class="badge rounded-pill border {{ $note->points >= 0 ? 'text-success border-success' : 'border-warning' }}"
          style="{{ $note->points < 0 ? 'color:#b45309;' : '' }}">
        {{ $note->levelLabel() }} · {{ $note->points > 0 ? '+' : '' }}{{ $note->points }}
    </span>
@endif
