<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_users_can_claim_and_follow_their_own_progress(): void
    {
        $admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $employee = User::query()->where('email', 'dina@claimly.test')->firstOrFail();
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();

        $this->actingAs($employee)
            ->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertOk();

        $this->postJson('/api/claims')->assertCreated();

        $this->getJson('/api/claims')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'diajukan')
            ->assertJsonPath('0.claim_total', 48300)
            ->assertJsonPath('0.items.0.name', 'Cloud Control Puff');

        $claimId = $this->getJson('/api/claims')->json('0.id');

        $this->patchJson('/api/claims/'.$claimId, ['status' => 'selesai'])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->getJson('/api/discount')->assertForbidden();
        $this->patchJson('/api/products/'.$product->id.'/price', ['retail_price' => 1000])->assertForbidden();

        $this->actingAs($admin)->getJson('/api/claims')->assertOk()->assertJsonCount(0);
        $this->getJson('/api/claims/all')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.user.name', 'Dina K.');

        $this->patchJson('/api/claims/'.$claimId, ['status' => 'diproses'])
            ->assertOk()
            ->assertJsonPath('status', 'diproses');

        $this->actingAs($employee)
            ->getJson('/api/claims')
            ->assertOk()
            ->assertJsonPath('0.status', 'diproses');
    }

    public function test_admin_manages_users_prices_and_the_rupiah_discount(): void
    {
        $admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Bima Putra',
                'email' => 'bima@claimly.test',
                'team' => 'Finance',
                'role' => 'user',
                'password' => 'password1',
            ])
            ->assertCreated()
            ->assertJsonPath('role', 'user')
            ->assertJsonPath('initials', 'BP');

        $this->patchJson('/api/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'team' => $admin->team,
            'role' => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors('role');

        $this->deleteJson('/api/users/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('user');

        $this->patchJson('/api/products/'.$product->id.'/price', ['retail_price' => 100000])
            ->assertOk()
            ->assertJsonPath('retail_price', 100000)
            ->assertJsonPath('claim_price', 70000)
            ->assertJsonPath('currency', 'IDR');

        $this->patchJson('/api/discount', ['discount_percent' => 50])
            ->assertOk()
            ->assertJsonPath('discount_percent', 50)
            ->assertJsonPath('currency', 'IDR');

        $catalog = $this->getJson('/api/catalog')->assertOk()->assertJsonPath('period.discount_percent', 50);
        $cloud = collect($catalog->json('brands.0.products'))->firstWhere('name', 'Cloud Control Puff');

        $this->assertSame(50000, $cloud['claim_price']);
        $this->assertDatabaseHas('users', [
            'email' => 'bima@claimly.test',
            'is_admin' => false,
        ]);
    }
}
