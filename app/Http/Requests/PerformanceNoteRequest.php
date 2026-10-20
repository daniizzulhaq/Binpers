<?php

namespace App\Http\Requests;

use App\Enums\NoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

        return [
            // Anggota tidak bisa diganti setelah catatan dibuat.
            'subject_id' => $isCreate ? ['required', 'integer', 'exists:personnel,id'] : ['prohibited'],
            'type' => ['required', Rule::in($allowed)],
            'title' => ['required', 'string', 'max:150'],
            'note' => ['required', 'string', 'min:10', 'max:2000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],

            // Foto opsional.
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('assessment.note_image_max_kb', 4096)],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject_id' => 'anggota',
            'type' => 'jenis catatan',
            'title' => 'judul',
            'note' => 'uraian',
            'incident_date' => 'tanggal kejadian',
            'image' => 'foto',
        ];
    }
}
