<?php

namespace App\Events;

use App\Enums\Symbol;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderbookUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Symbol $symbol)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('orderbook.' . $this->symbol->value),
        ];
    }
}
