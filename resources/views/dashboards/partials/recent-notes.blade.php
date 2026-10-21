@can('reports.view')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold">
                <i class="bi bi-journal-text me-1"></i>
                Catatan Kinerja Terbaru
            </span>

            <span class="badge text-bg-success">
                <i class="bi bi-hand-thumbs-up me-1"></i>
                {{ $noteCounts['positive'] ?? 0 }} apresiasi
            </span>

            <span class="badge text-bg-danger">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ $noteCounts['negative'] ?? 0 }} teguran
            </span>

            <span class="badge text-bg-dark">
                <i class="bi bi-file-earmark-x me-1"></i>
                {{ $noteCounts['violation'] ?? 0 }} pelanggaran
            </span>

            <span class="small text-muted">30 hari terakhir</span>

            <a href="{{ route('notes.index') }}" class="small ms-auto">Lihat semua</a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Anggota</th>
                        <th>Jenis</th>
                        <th>Catatan</th>
                        <th>Dicatat oleh</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($recentNotes as $n)
                        <tr>
                            <td class="text-nowrap">
                                {{ $n->incident_date->format('d/m/Y') }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $n->subject->user->name }}</div>
                                <div class="small text-muted">
                                    {{ $n->subject->nrp }}@if ($n->unit) · {{ $n->unit->name }}@endif
                                </div>
                            </td>

                            <td>
                                <span class="badge text-bg-{{ $n->type->color() }}">
                                    <i class="bi {{ $n->type->icon() }} me-1"></i>
                                    {{ $n->type->shortLabel() }}
                                </span>

                                @include('notes._level', ['note' => $n])
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $n->title }}</div>
                                <div class="small text-muted">
                                    {{ \Illuminate\Support\Str::limit($n->note, 90) }}
                                </div>

                                @if ($n->image_path)
                                    <a href="{{ route('notes.image', $n) }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="small">
                                        <i class="bi bi-image me-1"></i>
                                        Lihat foto
                                    </a>
                                @endif
                            </td>

                            <td>
                                {{ $n->authorName() }}

                                @if ($n->authorPosition())
                                    <div class="small text-muted">{{ $n->authorPosition() }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada catatan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endcan
