<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShopDataUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $updateType;
    public $payload; // 👈 هذا سيحمل مصفوفة البيانات بالكامل

    public function __construct($userId, $updateType, $payload = [])
    {
        $this->userId = $userId;
        $this->updateType = $updateType;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('shop.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'shop.updated';
    }
}