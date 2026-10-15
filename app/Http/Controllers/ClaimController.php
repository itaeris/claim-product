<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\User;
use App\Services\CartPresenter;
use App\Services\CartStore;
use App\Services\SubmitClaim;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $claims = Claim::query()
            ->where('user_id', $request->user()->id)
            ->with('items')
            ->latest('submitted_at')
            ->get()
            ->map(fn (Claim $claim) => $this->present($claim))
            ->values()
            ->all();

        return response()->json($claims);
    }

    public function adminIndex(): JsonResponse
    {
        $claims = Claim::query()
            ->with(['items', 'user:id,name,email,team'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (Claim $claim) => $this->present($claim, true))
            ->values()
            ->all();

        return response()->json($claims);
    }

    public function updateStatus(Request $request, Claim $claim): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Claim::STATUSES)],
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status klaim tidak valid.',
        ]);

        $claim->update(['status' => $data['status']]);

        return response()->json($this->present($claim->load(['items', 'user:id,name,email,team']), true));
    }

    public function store(SubmitClaim $claims, CartStore $cart, CartPresenter $presenter): JsonResponse
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $claim = $claims->handle($user);

        return response()->json([
            'message' => 'Klaim berhasil diajukan!',
            'claim' => [
                'number' => $claim->number,
                'claim_total' => $claim->claim_total,
            ],
            'cart' => $presenter->present($cart->items($user)),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Claim $claim, bool $withUser = false): array
    {
        $submittedAt = $claim->submitted_at?->timezone('Asia/Jakarta');
        $payload = [
            'id' => $claim->id,
            'number' => $claim->number,
            'status' => $claim->status,
            'retail_total' => $claim->retail_total,
            'saving_total' => $claim->saving_total,
            'claim_total' => $claim->claim_total,
            'submitted_label' => $submittedAt instanceof CarbonInterface ? $this->formatDate($submittedAt) : null,
            'items' => $claim->items->map(fn (ClaimItem $item) => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_retail_price' => $item->unit_retail_price,
                'unit_claim_price' => $item->unit_claim_price,
            ])->values()->all(),
        ];

        if ($withUser) {
            $payload['user'] = [
                'name' => $claim->user?->name,
                'email' => $claim->user?->email,
                'team' => $claim->user?->team,
            ];
        }

        return $payload;
    }

    private function formatDate(CarbonInterface $date): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $date->day.' '.$months[$date->month].' '.$date->year;
    }
}
