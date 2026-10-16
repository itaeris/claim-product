<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\ClaimPayment;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimWorkflow
{
    public function __construct(private ClaimNotifier $notifier) {}

    public function confirmStock(Claim $claim, User $admin): Claim
    {
        $this->expect($claim, ['cek_stok', 'stok_kosong']);
        $claim->update(['status' => 'menunggu_pembayaran', 'invoice_issued_at' => now()]);
        $brand = $claim->brand;
        $account = $brand?->bank_account_number
            ? "{$brand->bank_name} {$brand->bank_account_number} a.n. {$brand->bank_account_name}"
            : 'rekening yang tertera di invoice';

        $this->notifier->employee(
            $claim->user,
            'Invoice terbit',
            "Stok untuk {$claim->invoice_number} tersedia. Transfer ".Rupiah::format($claim->total_due)
                ." ke {$brand?->company_name} ({$account}), lalu unggah bukti transfer di halaman Progres.",
            $claim,
        );
        $this->notifier->ops(
            'Invoice terkirim',
            "Invoice {$claim->invoice_number} untuk {$claim->user->name} (".Rupiah::format($claim->total_due).') sudah dikirim. CC Finance dan Warehouse.',
            $admin,
        );

        return $claim;
    }

    public function markOutOfStock(Claim $claim, User $admin): Claim
    {
        $this->expect($claim, ['cek_stok']);
        $claim->update(['status' => 'stok_kosong']);

        $this->notifier->employee(
            $claim->user,
            'Stok belum tersedia',
            "Barang di {$claim->invoice_number} belum tersedia. Kami kabari lagi begitu stok ada.",
            $claim,
        );

        return $claim;
    }

    public function submitPayment(Claim $claim, UploadedFile $proof, int $amount): ClaimPayment
    {
        $this->expect($claim, Claim::AWAITING_PAYMENT);

        $payment = DB::transaction(function () use ($claim, $proof, $amount) {
            $payment = $claim->payments()->create([
                'declared_amount' => $amount,
                'proof_path' => $proof->store('payment-proofs/'.$claim->id, 'local'),
                'proof_name' => mb_substr($proof->getClientOriginalName(), 0, 180),
                'status' => 'menunggu',
            ]);
            $claim->update(['status' => 'verifikasi_pembayaran']);

            return $payment;
        });

        $this->notifier->employee(
            $claim->user,
            'Bukti transfer diterima',
            "Finance akan memverifikasi pembayaran {$claim->invoice_number}.",
            $claim,
        );
        $this->notifier->ops(
            'Bukti transfer masuk',
            "{$claim->user->name} mengunggah bukti transfer ".Rupiah::format($amount)
                ." untuk {$claim->invoice_number} (sisa tagihan ".Rupiah::format($claim->remainingDue()).'). Mohon verifikasi.',
            $claim->user,
        );

        return $payment;
    }

    public function verifyPayment(Claim $claim, int $receivedAmount, User $admin): Claim
    {
        $this->expect($claim, ['verifikasi_pembayaran']);
        $payment = $this->pendingPayment($claim);
        $paid = $claim->paid_total + $receivedAmount;

        DB::transaction(function () use ($claim, $payment, $receivedAmount, $paid, $admin) {
            $payment->update([
                'verified_amount' => $receivedAmount,
                'status' => 'diterima',
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $claim->update($paid < $claim->total_due
                ? ['status' => 'kurang_bayar', 'paid_total' => $paid]
                : ['status' => 'disiapkan', 'paid_total' => $paid, 'refund_total' => $paid - $claim->total_due]);
        });

        if ($claim->status === 'kurang_bayar') {
            $shortfall = Rupiah::format($claim->remainingDue());
            $this->notifier->employee(
                $claim->user,
                'Pembayaran kurang',
                "Pembayaran {$claim->invoice_number} kurang {$shortfall}. Transfer sisanya, lalu unggah bukti transfer baru.",
                $claim,
            );
            $this->notifier->ops(
                'Kurang bayar',
                "{$claim->invoice_number} milik {$claim->user->name} kurang {$shortfall}. Karyawan sudah diminta melunasi.",
                $admin,
            );

            return $claim;
        }

        $refund = $claim->refund_total > 0
            ? ' Kelebihan bayar '.Rupiah::format($claim->refund_total).' akan dikembalikan Finance.'
            : '';
        $destination = $claim->isDelivery() ? 'kirim ke '.$claim->shipping_address : 'diambil di gudang';

        $this->notifier->employee(
            $claim->user,
            'Pembayaran sesuai',
            "Pembayaran {$claim->invoice_number} sudah diverifikasi. Gudang sedang menyiapkan barang.{$refund}",
            $claim,
        );
        $this->notifier->ops(
            'Siapkan barang',
            "Pembayaran {$claim->invoice_number} sesuai. Warehouse mohon siapkan barang ({$destination}).{$refund}",
            $admin,
        );

        return $claim;
    }

    public function rejectPayment(Claim $claim, string $note, User $admin): Claim
    {
        $this->expect($claim, ['verifikasi_pembayaran']);
        $payment = $this->pendingPayment($claim);

        DB::transaction(function () use ($claim, $payment, $note, $admin) {
            $payment->update([
                'status' => 'ditolak',
                'note' => $note,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);
            $claim->update(['status' => $claim->paid_total > 0 ? 'kurang_bayar' : 'menunggu_pembayaran']);
        });

        $this->notifier->employee(
            $claim->user,
            'Bukti transfer ditolak',
            "Bukti transfer {$claim->invoice_number} ditolak: {$note}. Unggah ulang bukti yang benar.",
            $claim,
        );

        return $claim;
    }

    public function dispatch(Claim $claim, ?string $trackingNumber, User $admin): Claim
    {
        $this->expect($claim, ['disiapkan']);

        if ($claim->isDelivery()) {
            $claim->update(['status' => 'dikirim', 'tracking_number' => $trackingNumber ?: null]);
            $tracking = $claim->tracking_number ? " Nomor resi: {$claim->tracking_number}." : '';

            $this->notifier->employee(
                $claim->user,
                'Pesanan dikirim',
                "{$claim->invoice_number} sedang dikirim ke {$claim->shipping_address}.{$tracking}",
                $claim,
            );

            return $claim;
        }

        $claim->update(['status' => 'siap_diambil']);

        $this->notifier->employee(
            $claim->user,
            'Siap diambil',
            "{$claim->invoice_number} siap diambil di ".config('claimly.warehouse_address').'.',
            $claim,
        );

        return $claim;
    }

    public function complete(Claim $claim, User $actor, bool $byEmployee = false): Claim
    {
        $this->expect($claim, ['dikirim', 'siap_diambil']);
        $claim->update(['status' => 'selesai', 'completed_at' => now()]);

        if ($byEmployee) {
            $this->notifier->ops(
                'Barang diterima',
                "{$claim->user->name} mengonfirmasi {$claim->invoice_number} sudah diterima.",
                $actor,
            );
        } else {
            $this->notifier->employee(
                $claim->user,
                'Pesanan selesai',
                "{$claim->invoice_number} sudah diterima dan ditandai selesai. Terima kasih!",
                $claim,
            );
        }

        return $claim;
    }

    public function refund(Claim $claim, User $admin): Claim
    {
        if ($claim->refund_total <= 0 || $claim->refunded_at !== null) {
            throw ValidationException::withMessages([
                'status' => 'Tidak ada kelebihan bayar yang perlu dikembalikan.',
            ]);
        }

        $claim->update(['refunded_at' => now()]);

        $this->notifier->employee(
            $claim->user,
            'Kelebihan bayar dikembalikan',
            'Finance sudah mengembalikan '.Rupiah::format($claim->refund_total)." untuk {$claim->invoice_number}.",
            $claim,
        );

        return $claim;
    }

    public function cancel(Claim $claim, string $note, User $admin): Claim
    {
        $this->expect($claim, ['cek_stok', 'stok_kosong', 'menunggu_pembayaran']);

        if ($claim->paid_total > 0) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan yang sudah dibayar tidak bisa dibatalkan.',
            ]);
        }

        $claim->update(['status' => 'dibatalkan']);
        $reason = $note !== '' ? " Alasan: {$note}." : '';

        $this->notifier->employee(
            $claim->user,
            'Pesanan dibatalkan',
            "{$claim->invoice_number} dibatalkan.{$reason}",
            $claim,
        );

        return $claim;
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function expect(Claim $claim, array $statuses): void
    {
        if (! in_array($claim->status, $statuses, true)) {
            throw ValidationException::withMessages([
                'status' => "Pesanan sedang di tahap {$claim->statusLabel()}, tindakan ini tidak bisa dilakukan.",
            ]);
        }
    }

    private function pendingPayment(Claim $claim): ClaimPayment
    {
        $payment = $claim->payments()->where('status', 'menunggu')->latest('id')->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'status' => 'Belum ada bukti transfer yang perlu diverifikasi.',
            ]);
        }

        return $payment;
    }
}
