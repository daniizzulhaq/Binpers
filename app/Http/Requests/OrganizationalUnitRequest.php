<?php

namespace App\Http\Requests;

use App\Enums\UnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('organization.manage');
    }

    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'code' => ['required', 'alpha_dash:ascii', 'max:30', Rule::unique('organizational_units', 'code')->ignore($unit?->id)],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(UnitType::class)],
            'parent_id' => ['nullable', 'exists:organizational_units,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $unit = $this->route('unit');
            $parent = $this->input('parent_id');

            if ($unit && $parent && in_array((int) $parent, OrganizationalUnit::descendantIdsOf($unit->id), true)) {
                $validator->errors()->add('parent_id', 'Induk tidak boleh berupa unit itu sendiri atau turunannya.');
            }
        }];
    }
}
