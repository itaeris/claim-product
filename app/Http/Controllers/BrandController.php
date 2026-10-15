<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Brand::query()
            ->with(['categories' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'short' => $brand->short,
                'categories' => $brand->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return response()->json($brands);
    }
}
