<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PersonnelAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('personnel.manage');
    }

    public function rules(): array
    {
        return [
            'organizational_unit_id' => ['required', 'exists:organizational_units,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'start_date' => ['required', 'date'],
            'decree_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $current = $this->route('personnel')->currentAssignment;

            if ($current && $this->filled('start_date') && $this->date('start_date')->lte($current->start_date)) {
                $validator->errors()->add('start_date', 'Tanggal mulai harus setelah tanggal mulai penempatan saat ini ('.$current->start_date->format('d/m/Y').').');
            }
        }];
    }
}
