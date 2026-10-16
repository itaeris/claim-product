<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\CatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::query()
            ->with(['categories' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Brand $brand) => [
                ...$this->present($brand),
                'categories' => $brand->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json($brands);
    }

    public function updateTerms(Request $request, Brand $brand, CatalogQuery $catalog): JsonResponse
    {
        $data = $request->validate([
            'discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'shipping_fee' => ['required', 'integer', 'min:0'],
            'company_name' => ['required', 'string', 'max:160'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
            'bank_account_name' => ['nullable', 'string', 'max:160'],
        ], [
            'discount_percent.required' => 'Diskon wajib diisi.',
            'discount_percent.integer' => 'Diskon harus berupa angka.',
            'discount_percent.min' => 'Diskon minimal 0%.',
            'discount_percent.max' => 'Diskon maksimal 100%.',
            'shipping_fee.required' => 'Ongkir wajib diisi.',
            'shipping_fee.integer' => 'Ongkir harus berupa angka Rupiah.',
            'shipping_fee.min' => 'Ongkir tidak boleh minus.',
            'company_name.required' => 'Nama PT wajib diisi.',
            'contact_email.email' => 'Format email brand tidak valid.',
        ]);

        $brand->update($data);
        $catalog->forget();

        return response()->json($this->present($brand));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'short' => $brand->short,
            'discount_percent' => $brand->discount_percent,
            'shipping_fee' => $brand->shipping_fee,
            'company_name' => $brand->company_name,
            'company_address' => $brand->company_address,
            'contact_phone' => $brand->contact_phone,
            'contact_email' => $brand->contact_email,
            'website' => $brand->website,
            'bank_name' => $brand->bank_name,
            'bank_account_number' => $brand->bank_account_number,
            'bank_account_name' => $brand->bank_account_name,
            'currency' => 'IDR',
        ];
    }
}
