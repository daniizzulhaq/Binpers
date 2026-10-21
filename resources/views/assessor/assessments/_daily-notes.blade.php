{{-- Daftar penilaian harian dalam satu kriteria. --}}
@if ($items->isNotEmpty())
    <ul class="list-unstyled small mb-0 mt-2 border-top pt-2">
        @foreach ($items as $n)
            <li class="d-flex align-items-start gap-2 py-1">
                <span class="badge text-bg-{{ $n->type->color() }} flex-shrink-0">{{ $n->type->shortLabel() }}</span>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-break">{{ $n->title }}</div>
                    <div class="text-muted">
                        {{ $n->incident_date->format('d/m/Y') }} · {{ $n->levelLabel() }}
                        · {{ $n->points > 0 ? '+' : '' }}{{ $n->points }} poin
                    </div>
                </div>
                @can('update', $n)
                    <a href="{{ route('notes.edit', $n) }}" class="text-decoration-none flex-shrink-0">Ubah</a>
                @endcan
            </li>
        @endforeach
    </ul>
@endif
