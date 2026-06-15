<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Publisher event ke Redis (dipakai sebagai message broker, pola QUEUE = at-least-once).
 * Producer: RPUSH ke NOTIFICATIONS_QUEUE. Consumer (debt-notify-worker / P4): BLPOP.
 */
class Broker
{
    public static function publish(array $payload): void
    {
        if (empty($payload['occurred_at'])) {
            $payload['occurred_at'] = now()->toIso8601String();
        }

        try {
            Redis::rpush(
                env('NOTIFICATIONS_QUEUE', 'queue:notifications'),
                json_encode($payload)
            );
        } catch (\Throwable $e) {
            // Jangan gagalkan request bisnis hanya karena broker error.
            Log::error('Broker publish failed: ' . $e->getMessage());
        }
    }
}
