<?php

namespace App\Http\Requests;

use App\Enums\NoteLevel;
use App\Enums\NoteType;
use App\Enums\RoleName;
use App\Models\AssessmentCriterion;
use App\Models\AssessmentPeriod;
use App\Services\DailyScoreService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PerformanceNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Kewenangan atas anggota diperiksa di controller; kepemilikan di policy.
        return NoteType::allowedFor($this->user()) !== [];
    }

    public function rules(): array
    {
        $isCreate = $this->route('note') === null;
        $allowed = array_map(fn (NoteType $t) => $t->value, NoteType::allowedFor($this->user()));
        $needsLevel = in_array($this->input('type'), [NoteType::Positive->value, NoteType::Negative->value], true);
        // Penilai wajib memilih kriteria; HRD boleh mengosongkan (dihitung "Umum").
        $needsCriterion = $needsLevel && ! $this->isHrd();

        return [
            'subject_id' => $isCreate ? ['required', 'integer', 'exists:personnel,id'] : ['prohibited'],
            'type' => ['required', Rule::in($allowed)],
            // Tingkat wajib untuk Apresiasi dan Teguran.
            'level' => [$needsLevel ? 'required' : 'nullable', Rule::enum(NoteLevel::class)],
            'assessment_criterion_id' => [$needsCriterion ? 'required' : 'nullable', 'integer', 'exists:assessment_criteria,id'],
            'title' => ['required', 'string', 'max:150'],
            'note' => ['required', 'string', 'min:10', 'max:2000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],

            // Foto opsional.
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('assessment.note_image_max_kb', 4096)],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $date = $this->input('incident_date');

            if (! $date || ! strtotime($date) || $validator->errors()->isNotEmpty()) {
                return;
            }

            if (AssessmentPeriod::recapLockedForDate($date)) {
                $validator->errors()->add(
                    'incident_date',
                    'Rekap bulan untuk tanggal ini sudah dikunci HRD. Catatan tidak dapat ditambahkan.'
                );

                return;
            }

            $criterionId = $this->integer('assessment_criterion_id');

            if ($criterionId) {
                $period = AssessmentCriterion::find($criterionId)?->period;

                if (! $period || $date < $period->start_date->toDateString() || $date > $period->end_date->toDateString()) {
                    $validator->errors()->add('assessment_criterion_id', 'Kriteria ini tidak berlaku untuk tanggal kejadian tersebut.');

                    return;
                }
            }

            if ($this->isHrd() || $this->input('type') === NoteType::Violation->value) {
                return;
            }

            // Penilai: hanya pada periode yang sedang dibuka, dan selama rekapnya belum dikirim.
            if (! AssessmentPeriod::forDate($date)?->isOpen()) {
                $validator->errors()->add('incident_date', 'Tanggal kejadian harus berada di periode penilaian yang sedang dibuka.');

                return;
            }

            $subjectId = $this->route('note')?->subject_id ?? $this->integer('subject_id');

            if (app(DailyScoreService::class)->recapSubmitted($this->user()->personnel?->id, $subjectId, $date)) {
                $validator->errors()->add(
                    'incident_date',
                    'Rekap penilaian anggota ini untuk bulan tersebut sudah dikirim. Penilaian harian baru dapat ditambahkan bila rekap dikembalikan untuk revisi.'
                );
            }
        }];
    }

    private function isHrd(): bool
    {
        return $this->user()->hasRole(RoleName::Hrd->value);
    }

    public function attributes(): array
    {
        return [
            'subject_id' => 'anggota',
            'type' => 'jenis catatan',
            'level' => 'tingkat',
            'assessment_criterion_id' => 'kriteria',
            'title' => 'judul',
            'note' => 'uraian',
            'incident_date' => 'tanggal kejadian',
            'image' => 'foto',
        ];
    }
}
