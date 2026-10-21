<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Rekap bulanan: skor dihitung otomatis dari penilaian harian, penilai hanya menambah catatan. */
class AssessmentScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kewenangan penilai diperiksa di AssessmentService.
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'member_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'general_notes' => 'catatan internal',
            'member_notes' => 'catatan untuk anggota',
        ];
    }
}
