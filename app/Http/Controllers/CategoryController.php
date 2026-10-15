<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CatalogQuery;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->select('categories.*')
            ->with('brand:id,name')
            ->withCount('products')
            ->join('brands', 'brands.id', '=', 'categories.brand_id')
            ->orderBy('brands.sort_order')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.name')
            ->get()
            ->map(fn (Category $category) => $this->present($category))
            ->values()
            ->all();

        return response()->json($categories);
    }

    public function store(CategoryRequest $request, CatalogQuery $catalog): JsonResponse
    {
        $category = Category::query()->create([
            'brand_id' => $request->integer('brand_id'),
            'name' => $request->string('name')->toString(),
            'sort_order' => $request->integer('sort_order'),
        ]);
        $catalog->forget();

        return response()->json($this->present($category->load('brand')->loadCount('products')), 201);
    }

    public function update(CategoryRequest $request, Category $category, CatalogQuery $catalog): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $category): void {
                $category->update([
                    'brand_id' => $request->integer('brand_id'),
                    'name' => $request->string('name')->toString(),
                    'sort_order' => $request->integer('sort_order'),
                ]);
                $category->products()->update(['brand_id' => $category->brand_id]);
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'brand_id' => 'Produk di kategori ini bentrok dengan brand tujuan.',
            ]);
        }

        $catalog->forget();

        return response()->json($this->present($category->load('brand')->loadCount('products')));
    }

    public function destroy(Category $category, CatalogQuery $catalog): JsonResponse
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori masih memiliki produk.',
            ]);
        }

        $category->delete();
        $catalog->forget();

        return response()->json(['message' => 'Kategori dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Category $category): array
    {
        return [
            'id' => $category->id,
            'brand_id' => $category->brand_id,
            'brand_name' => $category->brand?->name,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'products_count' => $category->products_count ?? 0,
        ];
    }
}
