<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::query()->where('email', config('claimly.employee_email'))->firstOrFail());
    }

    public function test_catalog_returns_brands_products_and_the_employee(): void
    {
        $this->getJson('/api/catalog')
            ->assertOk()
            ->assertJsonPath('employee.name', 'Aeris S.')
            ->assertJsonPath('employee.team', 'Marketing Team')
            ->assertJsonPath('period.name', 'Periode Oktober 2026')
            ->assertJsonPath('period.ends_label', 'Berakhir 31 Oktober 2026')
            ->assertJsonPath('period.discount_percent', 30)
            ->assertJsonPath('brands.0.name', 'Aeris Beauté')
            ->assertJsonPath('brands.0.categories.0', 'Puffs')
            ->assertJsonPath('brands.1.name', 'From This Island')
            ->assertJsonPath('brands.2.name', 'KIN + ALLY')
            ->assertJsonCount(9, 'brands.0.products')
            ->assertJsonPath('brands.0.products.0.claim_price', 48300);
    }

    public function test_cart_items_are_priced_and_submitted_as_a_claim(): void
    {
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();
        $user = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();

        $this->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('retail_total', 138000)
            ->assertJsonPath('saving_total', 41400)
            ->assertJsonPath('claim_total', 96600);

        $this->patchJson('/api/cart/items/'.$product->id, ['qty' => 1])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('claim_total', 48300);

        $this->postJson('/api/claims')
            ->assertCreated()
            ->assertJsonPath('message', 'Klaim berhasil diajukan!')
            ->assertJsonPath('claim.number', 'CLM-202610-0001')
            ->assertJsonPath('claim.claim_total', 48300)
            ->assertJsonPath('cart.count', 0);

        $this->assertDatabaseHas('claims', [
            'user_id' => $user->id,
            'number' => 'CLM-202610-0001',
            'status' => 'diajukan',
            'retail_total' => 69000,
            'saving_total' => 20700,
            'claim_total' => 48300,
        ]);
        $this->assertDatabaseHas('claim_items', [
            'product_id' => $product->id,
            'product_name' => 'Cloud Control Puff',
            'quantity' => 1,
            'unit_retail_price' => 69000,
            'unit_claim_price' => 48300,
        ]);

        $this->getJson('/api/cart')->assertOk()->assertJsonPath('count', 0);
        $this->postJson('/api/claims')->assertStatus(422)->assertJsonValidationErrors('cart');
    }

    public function test_unknown_products_cannot_be_added(): void
    {
        $this->postJson('/api/cart/items', ['product_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }
}
