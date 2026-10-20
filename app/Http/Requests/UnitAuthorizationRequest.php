<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use App\Models\Personnel;
use Illuminate\Foundation\Http\FormRequest;

class UnitAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('authorizations.manage');
    }

    public function rules(): array
    {
        return [
            'assessor_id' => ['bail', 'required', 'exists:personnel,id', function ($attribute, $value, $fail) {
                $user = Personnel::with('user')->find($value)?->user;
                if (! $user || ! $user->hasAnyRole(RoleName::canAssess())) {
                    $fail('Penilai harus berole Kapolres, Wakapolres, Kabag, Kasat, Kasi, atau Kanit.');
                }
            }],
            'unit_id' => ['required', 'exists:organizational_units,id'],
            'include_children' => ['boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
