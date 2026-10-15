<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $query->where('brand_id', $this->input('brand_id')))
                    ->ignore($this->route('category')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'brand_id.required' => 'Brand wajib dipilih.',
            'brand_id.exists' => 'Brand tidak ditemukan.',
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Kategori ini sudah ada di brand tersebut.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
        ];
    }
}
