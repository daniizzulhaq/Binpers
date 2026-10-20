<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssessmentScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Kewenangan penilai diperiksa di AssessmentService.
    }

    public function rules(): array
    {
        $period = $this->route('period');
        $isSubmit = $this->input('action') === 'submit';

        $rules = [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'general_notes' => ['nullable', 'string', 'max:2000'],
            'member_notes' => ['nullable', 'string', 'max:1000'],
            'scores' => ['required', 'array'],
        ];

        // Hanya kriteria milik periode ini yang diberi aturan; skor wajib berada dalam skalanya.
        foreach ($period->criteria as $c) {
            $rules["scores.{$c->id}"] = [$isSubmit ? 'required' : 'nullable', 'numeric', "between:{$c->min_score},{$c->max_score}"];
            $rules["notes.{$c->id}"] = ['nullable', 'string', 'max:1000'];
            $rules["evidence.{$c->id}"] = ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:'.config('assessment.evidence_max_kb')];
            $rules["remove_evidence.{$c->id}"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ($this->route('period')->criteria as $c) {
                if ($this->hasFile("evidence.{$c->id}") && blank($this->input("scores.{$c->id}"))) {
                    $validator->errors()->add("evidence.{$c->id}", "Isi skor {$c->name} terlebih dahulu sebelum mengunggah bukti.");
                }
            }
        }];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach ($this->route('period')->criteria as $c) {
            $attributes["scores.{$c->id}"] = "skor {$c->name}";
            $attributes["notes.{$c->id}"] = "catatan {$c->name}";
            $attributes["evidence.{$c->id}"] = "bukti {$c->name}";
        }

        return $attributes;
    }
}
