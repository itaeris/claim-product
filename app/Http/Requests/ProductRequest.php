<?php

namespace App\Http\Requests;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $category = Category::query()->find($value);

                    if ($category !== null && $category->brand_id !== (int) $this->input('brand_id')) {
                        $fail('Kategori tidak termasuk brand yang dipilih.');
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('products', 'name')
                    ->where(fn ($query) => $query->where('brand_id', $this->input('brand_id')))
                    ->ignore($this->route('product')),
            ],
            'retail_price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['required', 'boolean'],
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
            'category_id.required' => 'Kategori wajib dipilih.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.unique' => 'Produk ini sudah ada di brand tersebut.',
            'retail_price.required' => 'Harga retail wajib diisi.',
            'retail_price.integer' => 'Harga retail harus berupa angka.',
            'image.image' => 'File yang dilampirkan harus berupa gambar.',
            'image.mimes' => 'Gambar harus JPG, PNG, atau WebP.',
            'image.max' => 'Gambar maksimal 5 MB.',
        ];
    }
}
