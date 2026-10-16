<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'team' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(['user', 'admin'])],
            'employment_type' => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'company' => ['required', 'string', 'max:160'],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai.',
            'team.required' => 'Tim wajib diisi.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role harus user atau admin.',
            'employment_type.required' => 'Status karyawan wajib dipilih.',
            'employment_type.in' => 'Status karyawan tidak valid.',
            'company.required' => 'Perusahaan wajib dipilih.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
