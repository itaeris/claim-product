<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\CatalogQuery;
use App\Services\ClaimPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(private ClaimPricing $pricing) {}

    public function index(): JsonResponse
    {
        $products = Product::query()
            ->with(['brand:id,name,discount_percent', 'category:id,name'])
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('brands.sort_order')
            ->orderBy('categories.sort_order')
            ->orderBy('products.sort_order')
            ->orderBy('products.name')
            ->select('products.*')
            ->get()
            ->map(fn (Product $product) => $this->present($product))
            ->values()
            ->all();

        return response()->json($products);
    }

    public function store(ProductRequest $request, CatalogQuery $catalog): JsonResponse
    {
        $product = Product::query()->create($this->attributes($request));
        $catalog->forget();

        return response()->json($this->present($product->load(['brand', 'category'])), 201);
    }

    public function update(ProductRequest $request, Product $product, CatalogQuery $catalog): JsonResponse
    {
        $product->update($this->attributes($request, $product));
        $catalog->forget();

        return response()->json($this->present($product->load(['brand', 'category'])));
    }

    public function updatePrice(Request $request, Product $product, CatalogQuery $catalog): JsonResponse
    {
        $data = $request->validate([
            'retail_price' => ['required', 'integer', 'min:0'],
        ], [
            'retail_price.required' => 'Harga wajib diisi.',
            'retail_price.integer' => 'Harga harus berupa angka Rupiah.',
            'retail_price.min' => 'Harga tidak boleh minus.',
        ]);

        $product->update(['retail_price' => $data['retail_price']]);
        $catalog->forget();

        return response()->json($this->present($product->load(['brand', 'category'])));
    }

    public function destroy(Product $product, CatalogQuery $catalog): JsonResponse
    {
        if ($product->claimItems()->exists()) {
            throw ValidationException::withMessages([
                'product' => 'Produk sudah ada di klaim. Nonaktifkan produk ini saja.',
            ]);
        }

        $product->delete();
        $catalog->forget();

        return response()->json(['message' => 'Produk dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ProductRequest $request, ?Product $product = null): array
    {
        return [
            'brand_id' => $request->integer('brand_id'),
            'category_id' => $request->integer('category_id'),
            'name' => $request->string('name')->toString(),
            'retail_price' => $request->integer('retail_price'),
            'image_url' => $this->imageUrl($request, $product),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->exists('sort_order')
                ? $request->integer('sort_order')
                : ($product?->sort_order ?? $this->nextSortOrder($request->integer('brand_id'))),
        ];
    }

    private function nextSortOrder(int $brandId): int
    {
        $max = Product::query()->where('brand_id', $brandId)->max('sort_order');

        return $max === null ? 0 : ((int) $max + 1);
    }

    private function imageUrl(ProductRequest $request, ?Product $product): ?string
    {
        if ($request->hasFile('image')) {
            $this->deleteStoredImage($product?->image_url);
            $path = $request->file('image')->store('products', 'public');

            return '/storage/'.$path;
        }

        if ($product !== null && $request->boolean('remove_image')) {
            $this->deleteStoredImage($product->image_url);

            return null;
        }

        if ($request->filled('image_url')) {
            return $request->string('image_url')->toString();
        }

        return $product?->image_url;
    }

    private function deleteStoredImage(?string $url): void
    {
        if ($url === null || ! str_starts_with($url, '/storage/products/')) {
            return;
        }

        Storage::disk('public')->delete(substr($url, strlen('/storage/')));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Product $product): array
    {
        return [
            'id' => $product->id,
            'brand_id' => $product->brand_id,
            'brand_name' => $product->brand?->name,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'name' => $product->name,
            'retail_price' => $product->retail_price,
            'claim_price' => $this->pricing->claimUnitPrice($product->retail_price, $product->brand?->discount_percent ?? 0),
            'currency' => 'IDR',
            'image_url' => $product->image_url,
            'is_active' => $product->is_active,
            'sort_order' => $product->sort_order,
        ];
    }
}
