@extends(auth()->user()->layout())

@php
    $isHrd = auth()->user()->hasRole('hrd');
    $formTitle = $note ? 'Ubah Catatan' : 'Tulis Catatan';
@endphp

@section('title', $formTitle)
@section('page-title', $formTitle)

@section('content')
    <a href="{{ route('notes.index') }}" class="btn btn-link px-0 mb-2">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm w-100" style="max-width: 760px;">
        <div class="card-body p-3 p-md-4">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $note ? route('notes.update', $note) : route('notes.store') }}">
                @csrf
                @if ($note)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label" for="subject_id">{{ $isHrd ? 'Personel' : 'Anggota' }}</label>
                    @if ($note)
                        <div class="form-control-plaintext fw-semibold text-break">
                            {{ $note->subject->user->name }} · {{ $note->subject->nrp }}
                        </div>
                    @elseif ($subjects->isEmpty())
                        <div class="alert alert-warning mb-0">
                            @if ($isHrd)
                                Belum ada personel aktif yang dapat dipilih.
                            @else
                                Belum ada anggota yang ditetapkan kepada Anda. Hubungi HRD untuk mengatur kewenangan penilai.
                            @endif
                        </div>
                    @else
                        <select name="subject_id" id="subject_id" required
                                class="form-select form-select-lg @error('subject_id') is-invalid @enderror">
                            <option value="">— Pilih {{ $isHrd ? 'personel' : 'anggota' }} —</option>
                            @foreach ($subjects as $s)
                                <option value="{{ $s->id }}" @selected((int) old('subject_id', $selected) === $s->id)>
                                    {{ $s->user->name }} · {{ $s->nrp }}@if ($s->currentAssignment?->unit) · {{ $s->currentAssignment->unit->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Jenis catatan</label>
                    @php
                        $count = count($types);
                        $col = match (true) {
                            $count >= 3 => 'col-12 col-sm-4',
                            $count === 2 => 'col-6',
                            default => 'col-12',
                        };
                        $chosen = old('type', $note?->type?->value ?? ($count === 1 ? $types[0]->value : null));
                    @endphp
                    <div class="row g-2">
                        @foreach ($types as $t)
                            <div class="{{ $col }}">
                                <input type="radio" class="btn-check" name="type" id="type-{{ $t->value }}"
                                       value="{{ $t->value }}" required
                                       @checked($chosen === $t->value)>
                                <label class="btn btn-outline-{{ $t->color() }} w-100 py-3 px-1" for="type-{{ $t->value }}">
                                    <i class="bi {{ $t->icon() }} d-block fs-5 mb-1"></i>{{ $t->label() }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @if ($isHrd)
                        <div class="form-text">Riwayat Pelanggaran hanya dapat dicatat oleh HRD.</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label" for="incident_date">Tanggal kejadian</label>
                    <input type="date" id="incident_date" name="incident_date" required
                           max="{{ now()->format('Y-m-d') }}"
                           value="{{ old('incident_date', $note?->incident_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="form-control @error('incident_date') is-invalid @enderror">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="title">Judul singkat</label>
                    <input type="text" id="title" name="title" maxlength="150" required
                           value="{{ old('title', $note?->title) }}"
                           placeholder="Contoh: Membantu warga saat banjir"
                           class="form-control @error('title') is-invalid @enderror">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="note">Uraian</label>
                    <textarea id="note" name="note" rows="5" maxlength="2000" required
                              placeholder="Apa yang terjadi, kapan, dan di mana."
                              class="form-control @error('note') is-invalid @enderror">{{ old('note', $note?->note) }}</textarea>
                    <div class="form-text">Minimal 10 karakter. Catatan ini akan terlihat oleh anggota yang bersangkutan beserta nama Anda sebagai pencatat. Gunakan bahasa yang objektif dan sopan.</div>
                </div>

                {{-- Foto (opsional) --}}
                <div class="mb-3">
                    <label class="form-label" for="image">
                        Foto / gambar <span class="text-muted fw-normal">(opsional)</span>
                    </label>

                    @if ($note?->image_path)
                        <div class="mb-2">
                            @include('notes._image', ['note' => $note, 'height' => 180])
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                                <label class="form-check-label" for="remove_image">Hapus foto ini</label>
                            </div>
                        </div>
                    @endif

                    <input type="file" id="image" name="image"
                           accept="image/jpeg,image/png,image/webp"
                           class="form-control @error('image') is-invalid @enderror">
                    <div class="form-text">
                        JPG, PNG, atau WebP. Foto dari kamera diperkecil otomatis sebelum diunggah.
                        @if ($note?->image_path) Memilih foto baru akan mengganti foto yang ada. @endif
                    </div>
                    <img id="imagePreview" alt="Pratinjau foto" class="img-fluid rounded border mt-2 d-none"
                         style="max-height:220px;object-fit:cover;">
                </div>

                @if ($note)
                    <div class="form-text mb-3">
                        Catatan hanya dapat diubah atau dihapus dalam {{ config('assessment.note_edit_hours', 24) }} jam setelah dibuat.
                    </div>
                @endif

                <div class="d-grid gap-2 d-sm-flex">
                    <button class="btn btn-primary" @disabled(! $note && $subjects->isEmpty())>
                        <i class="bi bi-save me-1"></i>Simpan Catatan
                    </button>
                    <a href="{{ route('notes.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const input = document.getElementById('image');
            const preview = document.getElementById('imagePreview');
            if (!input || !preview) return;

            const MAX_SIDE = 1600;
            const QUALITY = 0.82;

            input.addEventListener('change', async function () {
                const file = input.files[0];
                if (!file) {
                    preview.classList.add('d-none');
                    return;
                }

                // Perkecil di browser agar foto kamera HP tidak melebihi batas unggah.
                try {
                    if ('createImageBitmap' in window) {
                        const bmp = await createImageBitmap(file);
                        const scale = Math.min(1, MAX_SIDE / Math.max(bmp.width, bmp.height));
                        const canvas = document.createElement('canvas');
                        canvas.width = Math.round(bmp.width * scale);
                        canvas.height = Math.round(bmp.height * scale);

                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        ctx.drawImage(bmp, 0, 0, canvas.width, canvas.height);

                        const blob = await new Promise(function (resolve) {
                            canvas.toBlob(resolve, 'image/jpeg', QUALITY);
                        });

                        if (blob && blob.size < file.size) {
                            const resized = new File(
                                [blob],
                                file.name.replace(/\.\w+$/, '') + '.jpg',
                                { type: 'image/jpeg' }
                            );
                            const dt = new DataTransfer();
                            dt.items.add(resized);
                            input.files = dt.files;
                        }
                    }
                } catch (e) {
                    // Gagal memperkecil: pakai berkas asli.
                }

                preview.src = URL.createObjectURL(input.files[0]);
                preview.classList.remove('d-none');
            });
        })();
    </script>
@endpush
