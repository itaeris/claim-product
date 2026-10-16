<?php

namespace App\Http\Controllers;

use App\Models\ClaimItem;
use App\Models\LimitRequest;
use App\Models\Product;
use App\Services\ClaimNotifier;
use App\Services\PurchaseLimit;
use App\Support\IndonesianDate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LimitController extends Controller
{
    public function __construct(private PurchaseLimit $limits) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = now()->year;
        $requests = $user->limitRequests()->with('product.brand')->latest()->get();
        $productIds = ClaimItem::query()
            ->join('claims', 'claims.id', '=', 'claim_items.claim_id')
            ->where('claims.user_id', $user->id)
            ->where('claims.status', '!=', 'dibatalkan')
            ->distinct()
            ->pluck('claim_items.product_id')
            ->merge($requests->where('year', $year)->pluck('product_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $summary = $this->limits->summary($user, $productIds, $year);
        $products = Product::query()->with('brand')->whereIn('id', $productIds)->get()->keyBy('id');

        return response()->json([
            'year' => $year,
            'per_sku' => $this->limits->perSku(),
            'products' => collect($summary)
                ->map(fn (array $row, int $productId) => [
                    'product_id' => $productId,
                    'name' => $products->get($productId)?->name,
                    'brand' => $products->get($productId)?->brand?->name,
                    'image_url' => $products->get($productId)?->image_url,
                    ...$row,
                ])
                ->filter(fn (array $row) => $row['name'] !== null && $row['used'] > 0)
                ->sortByDesc('used')
                ->values()
                ->all(),
            'requests' => $requests->map(fn (LimitRequest $limit) => $this->present($limit))->values()->all(),
        ]);
    }

    public function store(Request $request, ClaimNotifier $notifier): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'extra_qty' => ['required', 'integer', 'min:1', 'max:20'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'supervisor_name' => ['required', 'string', 'max:120'],
            'supervisor_email' => ['required', 'email', 'max:255'],
        ], [
            'product_id.required' => 'Produk wajib dipilih.',
            'product_id.exists' => 'Produk tidak ditemukan.',
            'extra_qty.required' => 'Jumlah tambahan wajib diisi.',
            'extra_qty.min' => 'Tambahan minimal 1 pcs.',
            'extra_qty.max' => 'Tambahan maksimal 20 pcs per pengajuan.',
            'reason.required' => 'Alasan wajib diisi.',
            'reason.min' => 'Alasan minimal 10 karakter.',
            'supervisor_name.required' => 'Nama atasan wajib diisi.',
            'supervisor_email.required' => 'Email atasan wajib diisi.',
            'supervisor_email.email' => 'Format email atasan tidak valid.',
        ]);

        $user = $request->user();

        if ($user->limitRequests()->where('product_id', $data['product_id'])->where('status', 'menunggu')->exists()) {
            throw ValidationException::withMessages([
                'product_id' => 'Masih ada pengajuan limit produk ini yang menunggu HR.',
            ]);
        }

        $limit = $user->limitRequests()->create([
            ...$data,
            'year' => now()->year,
            'status' => 'menunggu',
        ]);
        $limit->load('product.brand');

        $notifier->ops(
            'Pengajuan penambahan limit',
            "{$user->name} meminta tambahan {$limit->extra_qty} pcs untuk {$limit->product->name}. Atasan: {$limit->supervisor_name} ({$limit->supervisor_email}). Alasan: {$limit->reason}",
            $user,
            '/kelola/limit',
        );

        return response()->json($this->present($limit), 201);
    }

    public function adminIndex(): JsonResponse
    {
        $requests = LimitRequest::query()
            ->with(['product.brand', 'user:id,name,email,team'])
            ->orderByRaw("case when status = 'menunggu' then 0 else 1 end")
            ->latest()
            ->get()
            ->map(fn (LimitRequest $limit) => $this->present($limit, true))
            ->values()
            ->all();

        return response()->json($requests);
    }

    public function review(Request $request, LimitRequest $limitRequest, ClaimNotifier $notifier): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'status.required' => 'Keputusan wajib dipilih.',
            'status.in' => 'Keputusan tidak valid.',
        ]);

        if ($limitRequest->status !== 'menunggu') {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan ini sudah diputuskan.',
            ]);
        }

        $limitRequest->update([
            'status' => $data['status'],
            'review_note' => $data['note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        $limitRequest->load(['product.brand', 'user']);

        $approved = $data['status'] === 'disetujui';
        $note = filled($data['note'] ?? null) ? " Catatan HR: {$data['note']}" : '';
        $notifier->employee(
            $limitRequest->user,
            $approved ? 'Limit pembelian ditambah' : 'Pengajuan limit ditolak',
            $approved
                ? "Kuota {$limitRequest->product->name} tahun {$limitRequest->year} bertambah {$limitRequest->extra_qty} pcs.{$note}"
                : "Pengajuan tambahan limit {$limitRequest->product->name} ditolak.{$note}",
        );

        return response()->json($this->present($limitRequest, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(LimitRequest $limit, bool $withUser = false): array
    {
        $payload = [
            'id' => $limit->id,
            'product_id' => $limit->product_id,
            'product_name' => $limit->product?->name,
            'brand' => $limit->product?->brand?->name,
            'image_url' => $limit->product?->image_url,
            'year' => $limit->year,
            'extra_qty' => $limit->extra_qty,
            'reason' => $limit->reason,
            'supervisor_name' => $limit->supervisor_name,
            'supervisor_email' => $limit->supervisor_email,
            'status' => $limit->status,
            'status_label' => LimitRequest::LABELS[$limit->status] ?? $limit->status,
            'review_note' => $limit->review_note,
            'created_label' => IndonesianDate::date($limit->created_at),
        ];

        if ($withUser) {
            $payload['user'] = [
                'name' => $limit->user?->name,
                'email' => $limit->user?->email,
                'team' => $limit->user?->team,
            ];
        }

        return $payload;
    }
}
