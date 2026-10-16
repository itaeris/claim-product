<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Services\CartPresenter;
use App\Services\CartStore;
use App\Services\ClaimPresenter;
use App\Services\ClaimWorkflow;
use App\Services\SubmitClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function __construct(private ClaimPresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $claims = Claim::query()
            ->where('user_id', $request->user()->id)
            ->with(ClaimPresenter::RELATIONS)
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(fn (Claim $claim) => $this->presenter->present($claim))
            ->values()
            ->all();

        return response()->json($claims);
    }

    public function adminIndex(): JsonResponse
    {
        $claims = Claim::query()
            ->with([...ClaimPresenter::RELATIONS, 'user:id,name,email,team'])
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->map(fn (Claim $claim) => $this->presenter->present($claim, true))
            ->values()
            ->all();

        return response()->json($claims);
    }

    public function store(Request $request, SubmitClaim $claims, CartStore $cart, CartPresenter $cartPresenter): JsonResponse
    {
        $data = $request->validate([
            'fulfillment' => ['nullable', Rule::in(Claim::FULFILLMENTS)],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'fulfillment.in' => 'Pilih ambil di gudang atau kirim.',
            'address.max' => 'Alamat maksimal 500 karakter.',
        ]);

        $user = $request->user();
        $created = $claims->handle($user, $data['fulfillment'] ?? 'pickup', $data['address'] ?? null);
        $count = $created->count();

        return response()->json([
            'message' => $count > 1
                ? "Pesanan diajukan dan otomatis dipisah menjadi {$count} invoice sesuai brand."
                : 'Pesanan berhasil diajukan!',
            'claims' => $created->map(fn (Claim $claim) => [
                'number' => $claim->number,
                'invoice_number' => $claim->invoice_number,
                'brand' => $claim->brand->name,
                'total_due' => $claim->total_due,
            ])->values()->all(),
            'cart' => $cartPresenter->present($user, $cart->items($user)),
        ], 201);
    }

    public function action(Request $request, Claim $claim, ClaimWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['stok_tersedia', 'stok_kosong', 'verifikasi', 'tolak_bukti', 'kirim', 'selesai', 'refund', 'batalkan'])],
            'amount' => ['required_if:action,verifikasi', 'nullable', 'integer', 'min:0'],
            'note' => ['required_if:action,tolak_bukti', 'nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
        ], [
            'action.required' => 'Tindakan wajib dipilih.',
            'action.in' => 'Tindakan tidak dikenal.',
            'amount.required_if' => 'Nominal yang diterima wajib diisi.',
            'amount.integer' => 'Nominal harus berupa angka Rupiah.',
            'note.required_if' => 'Alasan penolakan wajib diisi.',
        ]);

        $admin = $request->user();
        $claim->loadMissing(['user', 'brand']);

        match ($data['action']) {
            'stok_tersedia' => $workflow->confirmStock($claim, $admin),
            'stok_kosong' => $workflow->markOutOfStock($claim, $admin),
            'verifikasi' => $workflow->verifyPayment($claim, (int) $data['amount'], $admin),
            'tolak_bukti' => $workflow->rejectPayment($claim, (string) $data['note'], $admin),
            'kirim' => $workflow->dispatch($claim, $data['tracking_number'] ?? null, $admin),
            'selesai' => $workflow->complete($claim, $admin),
            'refund' => $workflow->refund($claim, $admin),
            'batalkan' => $workflow->cancel($claim, (string) ($data['note'] ?? ''), $admin),
        };

        return response()->json($this->presenter->present(
            $claim->fresh([...ClaimPresenter::RELATIONS, 'user:id,name,email,team']),
            true,
        ));
    }

    public function received(Request $request, Claim $claim, ClaimWorkflow $workflow): JsonResponse
    {
        abort_unless((int) $claim->user_id === (int) $request->user()->id, 403);

        $workflow->complete($claim->loadMissing(['user', 'brand']), $request->user(), true);

        return response()->json($this->presenter->present($claim->fresh(ClaimPresenter::RELATIONS)));
    }
}
