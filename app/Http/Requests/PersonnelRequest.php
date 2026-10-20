<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('personnel.manage');
    }

    public function rules(): array
    {
        $personnel = $this->route('personnel');
        $isCreate = $personnel === null;

        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash:ascii', 'max:50', Rule::unique('users', 'username')->ignore($personnel?->user_id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($personnel?->user_id)],
            'password' => [$isCreate ? 'required' : 'nullable', 'string', 'min:8', 'max:100'],
            // Role hanya ditetapkan saat membuat akun; perubahan role lewat modul Akun.
            'role' => $isCreate ? ['required', Rule::enum(RoleName::class)] : ['prohibited'],

            'nrp' => ['required', 'string', 'max:20', Rule::unique('personnel', 'nrp')->ignore($personnel?->id)],
            'rank' => ['nullable', Rule::in(config('assessment.ranks'))],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'place_of_birth' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'join_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],

            // Penempatan awal (hanya saat membuat). Perubahan selanjutnya lewat riwayat penempatan.
            'organizational_unit_id' => $isCreate
                ? ['nullable', 'required_with:position_id', 'exists:organizational_units,id']
                : ['prohibited'],
            'position_id' => $isCreate
                ? ['nullable', 'required_with:organizational_unit_id', 'exists:positions,id']
                : ['prohibited'],
        ];
    }
}
