{{-- Rincian Riwayat Pelanggaran: klasifikasi, bentuk pelanggaran, putusan sidang, tanggal putusan. --}}
@if ($note->type === \App\Enums\NoteType::Violation && ($note->violation_category || $note->verdict))
    <dl class="row small mb-2 mt-2 g-0 rounded p-2" style="background:#f4f6fa;">
        <dt class="col-5 col-sm-4 fw-semibold">Klasifikasi</dt>
        <dd class="col-7 col-sm-8 mb-1">{{ $note->violation_category?->label() ?? '–' }}</dd>

        <dt class="col-5 col-sm-4 fw-semibold">Bentuk pelanggaran</dt>
        <dd class="col-7 col-sm-8 mb-1 text-break">{{ $note->title }}</dd>

        <dt class="col-5 col-sm-4 fw-semibold">Putusan sidang</dt>
        <dd class="col-7 col-sm-8 mb-1 text-break">{!! $note->verdict ? nl2br(e($note->verdict)) : '–' !!}</dd>

        <dt class="col-5 col-sm-4 fw-semibold">Tanggal putusan</dt>
        <dd class="col-7 col-sm-8 mb-0">{{ $note->verdict_date?->format('d/m/Y') ?? '–' }}</dd>
    </dl>
@endif
