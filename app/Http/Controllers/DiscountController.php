<?php

namespace App\Http\Controllers;

use App\Models\ClaimPeriod;
use App\Services\CatalogQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->present(ClaimPeriod::current()));
    }

    public function update(Request $request, CatalogQuery $catalog): JsonResponse
    {
        $data = $request->validate([
            'discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ], [
            'discount_percent.required' => 'Diskon wajib diisi.',
            'discount_percent.integer' => 'Diskon harus berupa angka.',
            'discount_percent.min' => 'Diskon minimal 0%.',
            'discount_percent.max' => 'Diskon maksimal 100%.',
        ]);

        $period = ClaimPeriod::current();
        $period->update(['discount_percent' => $data['discount_percent']]);
        $catalog->forget();

        return response()->json($this->present($period));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(ClaimPeriod $period): array
    {
        return [
            'id' => $period->id,
            'name' => $period->name,
            'discount_percent' => $period->discount_percent,
            'starts_on' => $period->starts_on->toDateString(),
            'ends_on' => $period->ends_on->toDateString(),
            'currency' => 'IDR',
        ];
    }
}
