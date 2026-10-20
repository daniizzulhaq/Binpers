<div class="bp-card mb-3 overflow-hidden">
    <div class="d-flex align-items-center px-3 pt-3">
        <div class="bp-title mb-0">Catatan Kinerja untuk Saya</div>
        <a href="{{ route('notes.mine') }}" class="small ms-auto">Lihat semua</a>
    </div>

    <div class="list-group list-group-flush mt-2">
        @forelse ($myNotes as $n)
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="mb-1">
                            <span class="badge text-bg-{{ $n->type->color() }}">
                                <x-icon :name="$n->type->svg()" :size="12" :stroke="2.2" class="me-1" />
                                {{ $n->type->label() }}
                            </span>

                            @include('notes._level', ['note' => $n])
                        </div>

                        <div class="fw-semibold text-break">{{ $n->title }}</div>
                    </div>

                    <div class="small text-muted text-nowrap">
                        {{ $n->incident_date->format('d/m/Y') }}
                    </div>
                </div>

                <div class="small mt-1 text-break">
                    {{ \Illuminate\Support\Str::limit($n->note, 120) }}
                </div>

                @include('notes._image', ['note' => $n, 'height' => 160])

                <div class="small text-muted mt-1">
                    <x-icon name="user" :size="13" class="me-1" />
                    {{ $n->authorName() }}

                    @if ($n->authorPosition())
                        ({{ $n->authorPosition() }})
                    @endif
                </div>
            </div>
        @empty
            <div class="list-group-item text-center text-muted py-4">
                Belum ada catatan.
            </div>
        @endforelse
    </div>
</div>
