<?php

namespace App\Http\Requests;

use App\Enums\NoteLevel;
use App\Enums\NoteType;
use App\Models\AssessmentPeriod;
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

        return [
            'subject_id' => $isCreate ? ['required', 'integer', 'exists:personnel,id'] : ['prohibited'],
            'type' => ['required', Rule::in($allowed)],
            // Tingkat wajib untuk Apresiasi dan Teguran.
            'level' => [$needsLevel ? 'required' : 'nullable', Rule::enum(NoteLevel::class)],
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

            if ($date && strtotime($date) && AssessmentPeriod::recapLockedForDate($date)) {
                $validator->errors()->add(
                    'incident_date',
                    'Rekap bulan untuk tanggal ini sudah dikunci HRD. Catatan tidak dapat ditambahkan.'
                );
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'subject_id' => 'anggota',
            'type' => 'jenis catatan',
            'level' => 'tingkat',
            'title' => 'judul',
            'note' => 'uraian',
            'incident_date' => 'tanggal kejadian',
            'image' => 'foto',
        ];
    }
}
