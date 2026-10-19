<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\User;
use App\Notifications\ClaimUpdate;
use App\Notifications\OpsUpdate;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ClaimNotifier
{
    public function __construct(private LarkWebhook $lark) {}

    public function employee(User $user, string $title, string $body, ?Claim $claim = null): void
    {
        $invoiceUrl = $claim?->invoice_issued_at !== null ? '/invoices/'.$claim->id : null;

        $this->safely(fn () => $user->notify(new ClaimUpdate($title, $body, '/employee/progres', $invoiceUrl)));
    }

    public function ops(string $title, string $body, ?User $actor = null, string $url = '/admin/pesanan'): void
    {
        $admins = User::query()
            ->where('is_admin', true)
            ->when($actor !== null, fn ($query) => $query->whereKeyNot($actor->id))
            ->get();

        if ($admins->isNotEmpty()) {
            $this->safely(fn () => Notification::send($admins, new OpsUpdate($title, $body, $url)));
        }

        $this->lark->send("[Product Claim] {$title}\n{$body}\n".url($url));
    }

    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
