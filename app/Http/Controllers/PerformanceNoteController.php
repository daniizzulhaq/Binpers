<?php

namespace App\Http\Controllers;

use App\Enums\NoteType;
use App\Enums\RoleName;
use App\Http\Requests\PerformanceNoteRequest;
use App\Models\AssessmentAuthorization;
use App\Models\PerformanceNote;
use App\Models\Personnel;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Enums\NoteLevel;
use App\Models\NotePointSetting;
use Throwable;

class PerformanceNoteController extends Controller
{
    public function __construct(private AssessmentService $assessments)
    {
    }

    /**
     * Pemegang reports.view (HRD, Kapolres, Wakapolres) melihat semua catatan;
     * penilai lain hanya melihat catatan yang ia buat sendiri.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $seeAll = $user->can('reports.view');
        $mineOnly = ! $seeAll || $request->boolean('mine');

        $base = PerformanceNote::query()
            ->when($mineOnly, fn ($q) => $q->where('author_user_id', $user->id))
            ->when(NoteType::tryFrom((string) $request->input('type')), fn ($q, $t) => $q->where('type', $t->value))
            ->when($request->date('from'), fn ($q, $d) => $q->whereDate('incident_date', '>=', $d))
            ->when($request->date('to'), fn ($q, $d) => $q->whereDate('incident_date', '<=', $d))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)
                    ->orWhereHas('subject', fn ($s) => $s->where('nrp', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term))));
            });

        $counts = (clone $base)->toBase()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $notes = $base
            ->with(['authorUser', 'author.currentAssignment.position', 'subject.user', 'unit'])
            ->latest('incident_date')->latest('id')
            ->paginate(15)->withQueryString();

        return view('notes.index', [
            'notes' => $notes,
            'counts' => $counts,
            'types' => NoteType::cases(),
            'writeTypes' => NoteType::allowedFor($user),
            'seeAll' => $seeAll,
            'mineOnly' => $mineOnly,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $types = NoteType::allowedFor($user);
        abort_if($types === [], 403);

        return view('notes.form', [
            'note' => null,
            'subjects' => $user->hasRole(RoleName::Hrd->value)
                ? $this->allSubjects($user)
                : $this->subjectsFor($this->personnel($request)),
            'selected' => $request->integer('subject_id') ?: null,
            'types' => $types,
        ]);
    }

    public function store(PerformanceNoteRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $type = NoteType::from($data['type']);
        $level = $type->hasPoints() && ! empty($data['level']) ? NoteLevel::from($data['level']) : null;

        $subject = Personnel::with(['user', 'currentAssignment'])->findOrFail($data['subject_id']);

        if ($user->hasRole(RoleName::Hrd->value)) {
            // HRD: ketiga jenis, untuk personel aktif mana pun selain dirinya.
            abort_if($subject->user_id === $user->id, 403, 'Tidak dapat mencatat untuk akun sendiri.');
            abort_unless($subject->user?->is_active, 403, 'Akun anggota tidak aktif.');

            $authorPersonnelId = $user->personnel?->id;
        } else {
            // Penilai: hanya Apresiasi/Teguran, untuk anggota yang boleh dinilainya.
            abort_if($type->hrdOnly(), 403, 'Hanya HRD yang dapat mencatat pelanggaran.');

            $me = $this->personnel($request);

            abort_unless(
                $this->assessments->isAuthorized($me, $subject),
                403,
                'Anda tidak berwenang mencatat anggota ini.'
            );

            $authorPersonnelId = $me->id;
        }

        // Foto opsional; disimpan di disk privat.
        $imagePath = $request->file('image')?->store('note-images', 'local');

        try {
            PerformanceNote::create([
                'author_id' => $authorPersonnelId,
                'author_user_id' => $user->id,
                'subject_id' => $subject->id,
                'unit_id' => $subject->currentAssignment?->organizational_unit_id,
                'type' => $type,
                'level' => $level,
                // Poin disimpan saat dibuat; perubahan pengaturan HRD tidak mengubah catatan lama.
                'points' => NotePointSetting::signedPoints($type, $level),
                'title' => $data['title'],
                'note' => $data['note'],
                'image_path' => $imagePath,
                'incident_date' => $data['incident_date'],
            ]);
        } catch (Throwable $e) {
            if ($imagePath) {
                Storage::disk('local')->delete($imagePath);
            }

            throw $e;
        }

        return redirect()->route('notes.index')->with('success', 'Catatan tersimpan.');
    }


    public function edit(Request $request, PerformanceNote $note): View
    {
        Gate::authorize('update', $note);

        $note->load('subject.user');

        return view('notes.form', [
            'note' => $note,
            'subjects' => collect(),
            'selected' => null,
            'types' => NoteType::allowedFor($request->user()),
        ]);
    }

   public function update(PerformanceNoteRequest $request, PerformanceNote $note): RedirectResponse
    {
        Gate::authorize('update', $note);

        $attributes = Arr::except($request->validated(), ['image', 'remove_image']);

        $type = NoteType::from($attributes['type']);
        $level = $type->hasPoints() && ! empty($attributes['level']) ? NoteLevel::from($attributes['level']) : null;
        $attributes['level'] = $level?->value;

        // Poin dihitung ulang hanya bila jenis atau tingkat berubah.
        if ($note->type !== $type || $note->level !== $level) {
            $attributes['points'] = NotePointSetting::signedPoints($type, $level);
        }

        $oldPath = $note->image_path;
        $newPath = null;

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('note-images', 'local');
            $attributes['image_path'] = $newPath;
        } elseif ($request->boolean('remove_image')) {
            $attributes['image_path'] = null;
        }

        try {
            $note->update($attributes);
        } catch (Throwable $e) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $e;
        }

        // Berkas lama dihapus hanya setelah pembaruan berhasil.
        if ($oldPath && array_key_exists('image_path', $attributes)) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()->route('notes.index')->with('success', 'Catatan diperbarui.');
    }

    public function destroy(PerformanceNote $note): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $path = $note->image_path;
        $note->delete();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return redirect()->route('notes.index')->with('success', 'Catatan dihapus.');
    }

    /** Anggota yang kewenangannya aktif untuk penilai ini. */
    private function subjectsFor(Personnel $me)
    {
        return app(\App\Services\AssessorScopeService::class)
            ->assesseeQuery($me)
            ->with(['user', 'currentAssignment.unit'])
            ->orderBy('nrp')
            ->get();
    }

    /** Semua personel aktif, kecuali akun HRD yang sedang login. */
    private function allSubjects(User $user)
    {
        return Personnel::with(['user', 'currentAssignment.unit'])
            ->where('user_id', '!=', $user->id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->orderBy('nrp')
            ->get();
    }

    private function personnel(Request $request): Personnel
    {
        $personnel = $request->user()->personnel;
        abort_if($personnel === null, 403, 'Data personel Anda belum dibuat. Hubungi HRD.');

        return $personnel;
    }
}
