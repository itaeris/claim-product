<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'qty' => ['required', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qty.required' => 'Jumlah produk wajib diisi.',
            'qty.min' => 'Jumlah produk tidak valid.',
            'qty.max' => 'Maksimal 99 produk per item.',
        ];
    }
}
