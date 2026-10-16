<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimPayment;
use App\Services\ClaimPresenter;
use App\Services\ClaimWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function store(Request $request, Claim $claim, ClaimWorkflow $workflow, ClaimPresenter $presenter): JsonResponse
    {
        abort_unless((int) $claim->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'amount.required' => 'Nominal transfer wajib diisi.',
            'amount.integer' => 'Nominal transfer harus berupa angka Rupiah.',
            'amount.min' => 'Nominal transfer tidak valid.',
            'proof.required' => 'Bukti transfer wajib dilampirkan.',
            'proof.mimes' => 'Bukti transfer harus JPG, PNG, WebP, atau PDF.',
            'proof.max' => 'Bukti transfer maksimal 5 MB.',
        ]);

        $workflow->submitPayment($claim->loadMissing(['user', 'brand']), $request->file('proof'), (int) $data['amount']);

        return response()->json($presenter->present($claim->fresh(ClaimPresenter::RELATIONS)), 201);
    }

    public function proof(Request $request, ClaimPayment $payment): StreamedResponse
    {
        $user = $request->user();
        $claim = $payment->claim;

        abort_unless($user->is_admin || (int) $claim->user_id === (int) $user->id, 403);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, $payment->proof_name);
    }
}
