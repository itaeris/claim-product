<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Notification::fake();
        Http::fake();
    }

    public function test_users_only_see_their_own_orders_and_cannot_run_admin_actions(): void
    {
        $admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $employee = User::query()->where('email', 'dina@claimly.test')->firstOrFail();
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();
        $brand = Brand::query()->where('short', 'AB')->firstOrFail();

        $this->actingAs($employee)
            ->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertOk();

        $this->postJson('/api/claims')->assertCreated();

        $this->getJson('/api/claims')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'cek_stok')
            ->assertJsonPath('0.claim_total', 41400)
            ->assertJsonPath('0.brand.name', 'Aeris Beauté')
            ->assertJsonPath('0.items.0.name', 'Cloud Control Puff');

        $claimId = $this->getJson('/api/claims')->json('0.id');

        $this->postJson("/api/claims/{$claimId}/actions", ['action' => 'stok_tersedia'])->assertForbidden();
        $this->getJson('/api/users')->assertForbidden();
        $this->patchJson("/api/brands/{$brand->id}/terms", ['discount_percent' => 90])->assertForbidden();
        $this->patchJson('/api/products/'.$product->id.'/price', ['retail_price' => 1000])->assertForbidden();

        $this->actingAs($admin)->getJson('/api/claims')->assertOk()->assertJsonCount(0);
        $this->getJson('/api/claims/all')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.user.name', 'Dina K.');

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonStructure(['unread', 'items']);
    }

    public function test_admins_can_also_buy_products_as_a_user(): void
    {
        $admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $product = Product::query()->where('name', 'Poppy Racerback Bra — Black')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/cart/items', ['product_id' => $product->id])
            ->assertOk();

        $this->postJson('/api/claims', ['fulfillment' => 'pickup'])
            ->assertCreated()
            ->assertJsonPath('claims.0.brand', 'KIN + ALLY')
            ->assertJsonPath('claims.0.total_due', 209300);

        $this->getJson('/api/claims')->assertOk()->assertJsonCount(1);
    }

    public function test_admin_manages_users_prices_and_brand_terms(): void
    {
        $admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $product = Product::query()->where('name', 'Cloud Control Puff')->firstOrFail();
        $brand = Brand::query()->where('short', 'AB')->firstOrFail();

        $this->actingAs($admin)
            ->postJson('/api/users', [
                'name' => 'Bima Putra',
                'email' => 'bima@claimly.test',
                'team' => 'Finance',
                'role' => 'user',
                'employment_type' => 'intern',
                'company' => 'PT Inovasi Alam Nusantara',
                'password' => 'password1',
            ])
            ->assertCreated()
            ->assertJsonPath('role', 'user')
            ->assertJsonPath('employment_label', 'Intern')
            ->assertJsonPath('initials', 'BP');

        $this->patchJson('/api/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'team' => $admin->team,
            'employment_type' => 'tetap',
            'company' => $admin->company,
            'role' => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors('role');

        $this->deleteJson('/api/users/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('user');

        $this->patchJson('/api/products/'.$product->id.'/price', ['retail_price' => 100000])
            ->assertOk()
            ->assertJsonPath('retail_price', 100000)
            ->assertJsonPath('claim_price', 60000)
            ->assertJsonPath('currency', 'IDR');

        $this->patchJson("/api/brands/{$brand->id}/terms", [
            'discount_percent' => 50,
            'shipping_fee' => 15000,
            'company_name' => 'PT Citra Pasifik Kosmetik',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'PT Citra Pasifik Kosmetik',
        ])
            ->assertOk()
            ->assertJsonPath('discount_percent', 50)
            ->assertJsonPath('shipping_fee', 15000)
            ->assertJsonPath('bank_account_number', '1234567890');

        $catalog = $this->getJson('/api/catalog')->assertOk()->assertJsonPath('brands.0.discount_percent', 50);
        $cloud = collect($catalog->json('brands.0.products'))->firstWhere('name', 'Cloud Control Puff');

        $this->assertSame(50000, $cloud['claim_price']);
        $this->assertDatabaseHas('users', [
            'email' => 'bima@claimly.test',
            'is_admin' => false,
            'employment_type' => 'intern',
        ]);
    }
}
