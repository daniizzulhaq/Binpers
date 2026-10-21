@if ($note->type->hasPoints())
    <span class="badge rounded-pill text-bg-light border text-break">{{ $note->criterionName() }}</span>
@endif
@if ($note->level)
    <span class="badge rounded-pill border {{ $note->points >= 0 ? 'text-success border-success' : 'text-danger border-danger' }}">
        {{ $note->levelLabel() }} · {{ $note->points > 0 ? '+' : '' }}{{ $note->points }}
    </span>
@endif
