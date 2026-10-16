<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class LarkWebhook
{
    public function enabled(): bool
    {
        return filled(config('services.lark.webhook_url'));
    }

    public function send(string $text): void
    {
        if (! $this->enabled()) {
            return;
        }

        $payload = [
            'msg_type' => 'text',
            'content' => ['text' => $text],
        ];

        if (filled($secret = config('services.lark.secret'))) {
            $timestamp = (string) now()->timestamp;
            $payload['timestamp'] = $timestamp;
            $payload['sign'] = base64_encode(hash_hmac('sha256', '', $timestamp."\n".$secret, true));
        }

        try {
            Http::timeout(5)->asJson()->post(config('services.lark.webhook_url'), $payload)->throw();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
