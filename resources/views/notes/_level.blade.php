@if ($note->type->hasPoints())
    <span class="badge rounded-pill text-bg-light border text-break">{{ $note->criterionName() }}</span>
@endif
@if ($note->level)
    <span class="badge rounded-pill border {{ $note->points >= 0 ? 'text-success border-success' : 'text-danger border-danger' }}">
        {{ $note->levelLabel() }} · {{ $note->points > 0 ? '+' : '' }}{{ $note->points }}
    </span>
@endif
@if ($note->status !== \App\Enums\NoteStatus::Approved)
    <span class="badge rounded-pill text-bg-{{ $note->status->color() }}">{{ $note->status->label() }}</span>
    @if ($note->status === \App\Enums\NoteStatus::Rejected && $note->review_notes)
        <div class="small text-danger mt-1 text-break">Alasan ditolak: {{ $note->review_notes }}</div>
    @endif
@endif
