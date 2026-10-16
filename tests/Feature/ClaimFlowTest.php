<?php

namespace Tests\Feature;

use App\Models\Claim;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ClaimUpdate;
use App\Notifications\OpsUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-16 10:00:00');
        $this->seed();
        Storage::fake('local');
        Notification::fake();
        Http::fake();

        $this->admin = User::query()->where('email', config('claimly.employee_email'))->firstOrFail();
        $this->employee = User::query()->where('email', 'dina@claimly.test')->firstOrFail();
    }

    public function test_catalog_uses_the_discount_of_each_brand(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/catalog')
            ->assertOk()
            ->assertJsonPath('employee.name', 'Aeris S.')
            ->assertJsonPath('period.name', 'Periode Oktober 2026')
            ->assertJsonPath('period.ends_label', 'Berakhir 31 Oktober 2026')
            ->assertJsonPath('max_discount_percent', 40)
            ->assertJsonPath('sku_limit', 5)
            ->assertJsonPath('brands.0.name', 'Aeris Beauté')
            ->assertJsonPath('brands.0.discount_percent', 40)
            ->assertJsonPath('brands.0.company_name', 'PT Citra Pasifik Kosmetik')
            ->assertJsonPath('brands.1.discount_percent', 30)
            ->assertJsonPath('brands.2.company_name', 'PT Kinerja Inovasi Nusantara')
            ->assertJsonCount(9, 'brands.0.products')
            ->assertJsonPath('brands.0.products.0.claim_price', 41400);
    }

    public function test_cart_is_split_into_one_invoice_per_brand_with_shipping(): void
    {
        $puff = $this->product('Cloud Control Puff');
        $legging = $this->product('Euphoric Compression Legging');

        $this->actingAs($this->employee)->postJson('/api/cart/items', ['product_id' => $puff->id])->assertOk();
        $this->postJson('/api/cart/items', ['product_id' => $puff->id])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('claim_total', 82800);
        $this->postJson('/api/cart/items', ['product_id' => $legging->id])
            ->assertOk()
            ->assertJsonCount(2, 'groups')
            ->assertJsonPath('groups.0.brand_name', 'Aeris Beauté')
            ->assertJsonPath('groups.0.discount_percent', 40)
            ->assertJsonPath('groups.1.brand_name', 'KIN + ALLY')
            ->assertJsonPath('groups.1.claim_total', 349300)
            ->assertJsonPath('delivery_fee_total', 40000);

        $this->postJson('/api/claims', ['fulfillment' => 'delivery', 'address' => ''])
            ->assertCreated()
            ->assertJsonCount(2, 'claims')
            ->assertJsonPath('claims.0.invoice_number', 'INV/AB/202610/0001')
            ->assertJsonPath('claims.0.total_due', 102800)
            ->assertJsonPath('claims.1.invoice_number', 'INV/KA/202610/0001')
            ->assertJsonPath('claims.1.total_due', 369300)
            ->assertJsonPath('cart.count', 0);

        $this->assertDatabaseHas('claims', [
            'invoice_number' => 'INV/AB/202610/0001',
            'number' => 'CLM-202610-0001',
            'status' => 'cek_stok',
            'fulfillment' => 'delivery',
            'shipping_address' => 'Kantor',
            'shipping_fee' => 20000,
            'retail_total' => 138000,
            'claim_total' => 82800,
            'discount_percent' => 40,
        ]);
        $this->assertDatabaseHas('claim_items', [
            'product_name' => 'Cloud Control Puff',
            'quantity' => 2,
            'unit_claim_price' => 41400,
        ]);

        Notification::assertSentTo($this->employee, ClaimUpdate::class);
        Notification::assertSentTo($this->admin, OpsUpdate::class);
        $this->postJson('/api/claims')->assertStatus(422)->assertJsonValidationErrors('cart');
    }

    public function test_pickup_orders_have_no_shipping_fee(): void
    {
        $this->actingAs($this->employee)
            ->postJson('/api/cart/items', ['product_id' => $this->product('Cloud Control Puff')->id])
            ->assertOk();

        $this->postJson('/api/claims', ['fulfillment' => 'pickup'])
            ->assertCreated()
            ->assertJsonPath('claims.0.total_due', 41400);

        $this->assertDatabaseHas('claims', ['fulfillment' => 'pickup', 'shipping_fee' => 0, 'shipping_address' => null]);
    }

    public function test_each_sku_is_limited_to_five_pieces_a_year_until_hr_approves_more(): void
    {
        $puff = $this->product('Cloud Control Puff');

        $this->actingAs($this->employee)
            ->patchJson('/api/cart/items/'.$puff->id, ['qty' => 6])
            ->assertStatus(422)
            ->assertJsonValidationErrors('limit');

        $this->patchJson('/api/cart/items/'.$puff->id, ['qty' => 5])->assertOk();
        $this->postJson('/api/claims')->assertCreated();

        $this->postJson('/api/cart/items', ['product_id' => $puff->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('limit');

        $this->getJson('/api/limits')
            ->assertOk()
            ->assertJsonPath('per_sku', 5)
            ->assertJsonPath('products.0.name', 'Cloud Control Puff')
            ->assertJsonPath('products.0.used', 5)
            ->assertJsonPath('products.0.remaining', 0);

        $requestId = $this->postJson('/api/limit-requests', [
            'product_id' => $puff->id,
            'extra_qty' => 2,
            'reason' => 'Untuk hadiah tim saat acara kantor.',
            'supervisor_name' => 'Rina',
            'supervisor_email' => 'rina@claimly.test',
        ])->assertCreated()->assertJsonPath('status', 'menunggu')->json('id');

        $this->getJson('/api/limit-requests')->assertForbidden();

        $this->actingAs($this->admin)
            ->patchJson('/api/limit-requests/'.$requestId, ['status' => 'disetujui', 'note' => 'OK atasan'])
            ->assertOk()
            ->assertJsonPath('status', 'disetujui');

        $this->actingAs($this->employee)
            ->patchJson('/api/cart/items/'.$puff->id, ['qty' => 2])
            ->assertOk();
        $this->patchJson('/api/cart/items/'.$puff->id, ['qty' => 3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('limit');
    }

    public function test_payment_flow_handles_underpayment_overpayment_and_delivery(): void
    {
        $claim = $this->submitted('delivery');

        $this->actingAs($this->employee)
            ->postJson("/api/claims/{$claim->id}/payments", $this->payment(10000))
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'stok_tersedia'])
            ->assertOk()
            ->assertJsonPath('status', 'menunggu_pembayaran')
            ->assertJsonPath('invoice_url', '/invoices/'.$claim->id)
            ->assertJsonPath('bank.company_name', 'PT Citra Pasifik Kosmetik');

        $this->get('/invoices/'.$claim->id)->assertOk()->assertSee('INV/AB/202610/0001')->assertSee('Rp 61.400');

        $this->actingAs($this->employee)
            ->post("/api/claims/{$claim->id}/payments", $this->payment(50000), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('status', 'verifikasi_pembayaran');

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'verifikasi', 'amount' => 50000])
            ->assertOk()
            ->assertJsonPath('status', 'kurang_bayar')
            ->assertJsonPath('remaining_due', 11400);

        $this->actingAs($this->employee)
            ->post("/api/claims/{$claim->id}/payments", $this->payment(20000), ['Accept' => 'application/json'])
            ->assertCreated();

        $paymentId = $claim->payments()->latest('id')->value('id');
        $this->get("/api/payments/{$paymentId}/proof")->assertOk();

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'verifikasi', 'amount' => 20000])
            ->assertOk()
            ->assertJsonPath('status', 'disiapkan')
            ->assertJsonPath('paid_total', 70000)
            ->assertJsonPath('refund_total', 8600);

        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'refund'])
            ->assertOk()
            ->assertJsonPath('refunded', true);

        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'kirim', 'tracking_number' => 'JNE123'])
            ->assertOk()
            ->assertJsonPath('status', 'dikirim')
            ->assertJsonPath('tracking_number', 'JNE123');

        $this->actingAs($this->employee)
            ->postJson("/api/claims/{$claim->id}/received")
            ->assertOk()
            ->assertJsonPath('status', 'selesai');

        $this->assertNotNull($claim->fresh()->completed_at);
    }

    public function test_pickup_orders_become_ready_and_rejected_proofs_can_be_reuploaded(): void
    {
        $claim = $this->submitted('pickup');

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'stok_kosong'])
            ->assertOk()
            ->assertJsonPath('status', 'stok_kosong');
        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'stok_tersedia'])->assertOk();

        $this->actingAs($this->employee)
            ->post("/api/claims/{$claim->id}/payments", $this->payment(41400), ['Accept' => 'application/json'])
            ->assertCreated();

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'tolak_bukti', 'note' => 'Bukti buram'])
            ->assertOk()
            ->assertJsonPath('status', 'menunggu_pembayaran')
            ->assertJsonPath('payments.0.status', 'ditolak');

        $this->actingAs($this->employee)
            ->post("/api/claims/{$claim->id}/payments", $this->payment(41400), ['Accept' => 'application/json'])
            ->assertCreated();

        $this->actingAs($this->admin)
            ->postJson("/api/claims/{$claim->id}/actions", ['action' => 'verifikasi', 'amount' => 41400])
            ->assertOk()
            ->assertJsonPath('status', 'disiapkan')
            ->assertJsonPath('refund_total', 0);

        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'kirim'])
            ->assertOk()
            ->assertJsonPath('status', 'siap_diambil');

        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('status', 'selesai');
    }

    public function test_lark_webhook_receives_ops_updates_when_configured(): void
    {
        config(['services.lark.webhook_url' => 'https://open.larksuite.com/open-apis/bot/v2/hook/test']);

        $this->submitted('pickup');

        Http::assertSent(fn ($request) => $request->url() === 'https://open.larksuite.com/open-apis/bot/v2/hook/test'
            && str_contains($request['content']['text'], 'Pesanan baru dari Dina K.'));
    }

    public function test_cancelled_orders_free_up_the_limit_and_others_cannot_touch_invoices(): void
    {
        $claim = $this->submitted('pickup');

        $this->actingAs($this->admin)->get('/invoices/'.$claim->id)->assertNotFound();

        $this->postJson("/api/claims/{$claim->id}/actions", ['action' => 'batalkan', 'note' => 'Diskontinyu'])
            ->assertOk()
            ->assertJsonPath('status', 'dibatalkan');

        $this->actingAs($this->employee)
            ->patchJson('/api/cart/items/'.$this->product('Cloud Control Puff')->id, ['qty' => 5])
            ->assertOk();

        $other = User::factory()->create();
        $this->actingAs($other)->postJson("/api/claims/{$claim->id}/received")->assertForbidden();
        $this->actingAs($other)->get('/invoices/'.$claim->id)->assertForbidden();
    }

    public function test_unknown_products_cannot_be_added(): void
    {
        $this->actingAs($this->employee)
            ->postJson('/api/cart/items', ['product_id' => 99999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    private function product(string $name): Product
    {
        return Product::query()->where('name', $name)->firstOrFail();
    }

    private function submitted(string $fulfillment): Claim
    {
        $this->actingAs($this->employee)
            ->postJson('/api/cart/items', ['product_id' => $this->product('Cloud Control Puff')->id])
            ->assertOk();
        $this->postJson('/api/claims', ['fulfillment' => $fulfillment, 'address' => 'Jl. Melati 1, Jakarta'])->assertCreated();

        return Claim::query()->latest('id')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(int $amount): array
    {
        return [
            'amount' => $amount,
            'proof' => UploadedFile::fake()->createWithContent('bukti.pdf', '%PDF-1.4 bukti transfer'),
        ];
    }
}
