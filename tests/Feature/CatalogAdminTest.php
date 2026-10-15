<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::query()->where('email', config('claimly.employee_email'))->firstOrFail());
    }

    public function test_admins_can_create_update_and_delete_categories_and_products(): void
    {
        $brand = Brand::query()->where('name', 'Aeris Beauté')->firstOrFail();

        $categoryId = $this->postJson('/api/categories', [
            'brand_id' => $brand->id,
            'name' => 'Travel Kit',
            'sort_order' => 8,
        ])->assertCreated()
            ->assertJsonPath('name', 'Travel Kit')
            ->json('id');

        $this->patchJson('/api/categories/'.$categoryId, [
            'brand_id' => $brand->id,
            'name' => 'Travel Kits',
            'sort_order' => 8,
        ])->assertOk()->assertJsonPath('name', 'Travel Kits');

        $productId = $this->postJson('/api/products', [
            'brand_id' => $brand->id,
            'category_id' => $categoryId,
            'name' => 'Travel Bottle',
            'retail_price' => 100000,
            'image_url' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=700&q=80',
            'is_active' => true,
            'sort_order' => 1,
        ])->assertCreated()
            ->assertJsonPath('category_name', 'Travel Kits')
            ->json('id');

        $names = collect($this->getJson('/api/catalog')->json('brands'))
            ->flatMap(fn (array $brand) => collect($brand['products'])->pluck('name'));
        $this->assertTrue($names->contains('Travel Bottle'));

        $this->patchJson('/api/products/'.$productId, [
            'brand_id' => $brand->id,
            'category_id' => $categoryId,
            'name' => 'Travel Bottle',
            'retail_price' => 100000,
            'image_url' => 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?auto=format&fit=crop&w=700&q=80',
            'is_active' => false,
            'sort_order' => 1,
        ])->assertOk()->assertJsonPath('is_active', false);

        $hidden = collect($this->getJson('/api/catalog')->json('brands'))
            ->flatMap(fn (array $brand) => collect($brand['products'])->pluck('name'));
        $this->assertFalse($hidden->contains('Travel Bottle'));

        $this->deleteJson('/api/categories/'.$categoryId)
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');

        $this->deleteJson('/api/products/'.$productId)->assertOk();
        $this->deleteJson('/api/categories/'.$categoryId)->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $productId]);
        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_products_can_be_saved_without_an_image_or_with_an_attachment(): void
    {
        Storage::fake('public');
        $brand = Brand::query()->where('name', 'Aeris Beauté')->firstOrFail();
        $categoryId = Category::query()->where('brand_id', $brand->id)->value('id');

        $this->postJson('/api/products', [
            'brand_id' => $brand->id,
            'category_id' => $categoryId,
            'name' => 'Bottle Without Photo',
            'retail_price' => 50000,
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('image_url', null);

        $created = $this->post('/api/products', [
            'brand_id' => $brand->id,
            'category_id' => $categoryId,
            'name' => 'Bottle With Photo',
            'retail_price' => 60000,
            'is_active' => 1,
            'image' => UploadedFile::fake()->createWithContent(
                'bottle.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
            ),
        ], ['Accept' => 'application/json']);

        $created->assertCreated();
        $url = $created->json('image_url');
        $this->assertIsString($url);
        $this->assertStringStartsWith('/storage/products/', $url);
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));

        $this->post('/api/products/'.$created->json('id'), [
            '_method' => 'PATCH',
            'brand_id' => $brand->id,
            'category_id' => $categoryId,
            'name' => 'Bottle With Photo',
            'retail_price' => 60000,
            'is_active' => 1,
            'remove_image' => 1,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('image_url', null);

        Storage::disk('public')->assertMissing(substr($url, strlen('/storage/')));
    }

    public function test_products_used_in_a_claim_cannot_be_deleted(): void
    {
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();

        $this->postJson('/api/cart/items', ['product_id' => $product->id])->assertOk();
        $this->postJson('/api/claims')->assertCreated();

        $this->deleteJson('/api/products/'.$product->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('product');

        $this->assertNotNull(Category::query()->find($product->category_id));
    }
}
