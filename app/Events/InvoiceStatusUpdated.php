<?php

namespace App\Events;

use App\Support\InvoiceRealtimeStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public static function dispatchForOrder(string $orderId): void
    {
        $payload = InvoiceRealtimeStatus::payloadForOrder($orderId);

        if (! $payload) {
            return;
        }

        event(new self($payload));
    }

    public function broadcastOn(): Channel
    {
        return new Channel(InvoiceRealtimeStatus::channelName((string) $this->payload['order_id']));
    }

    public function broadcastAs(): string
    {
        return 'InvoiceStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
