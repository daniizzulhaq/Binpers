<?php

namespace App\Http\Requests;

use App\Models\AssessmentPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssessmentPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('periods.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('assessment_periods', 'name')->ignore($this->route('period')?->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                return;
            }

            $overlap = AssessmentPeriod::query()
                ->when($this->route('period'), fn ($q, $p) => $q->whereKeyNot($p->id))
                ->whereDate('start_date', '<=', $this->input('end_date'))
                ->whereDate('end_date', '>=', $this->input('start_date'))
                ->exists();

            if ($overlap) {
                $validator->errors()->add(
                    'start_date',
                    'Rentang tanggal bertabrakan dengan periode lain. Gunakan satu periode untuk setiap bulan.'
                );
            }
        }];
    }
}
