<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use App\Models\Personnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssessmentAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('authorizations.manage');
    }

    public function rules(): array
    {
        $common = [
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        // Mode ubah: hanya masa berlaku, status, dan catatan.
        if ($this->route('authorization')) {
            return $common;
        }

        return $common + [
            'assessor_id' => ['bail', 'required', 'exists:personnel,id', function ($attribute, $value, $fail) {
                $user = Personnel::with('user')->find($value)?->user;
                if (! $user || ! $user->hasAnyRole(RoleName::canAssess())) {
                    $fail('Penilai harus berole Kapolres, Wakapolres, Kabag, Kasat, Kasi, atau Kanit.');
                }
            }],
            'assessee_ids' => ['required', 'array', 'min:1'],
            'assessee_ids.*' => ['integer', 'distinct', 'exists:personnel,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (in_array((int) $this->input('assessor_id'), array_map('intval', (array) $this->input('assessee_ids')), true)) {
                $validator->errors()->add('assessee_ids', 'Penilai tidak boleh menilai dirinya sendiri.');
            }
        }];
    }
}
