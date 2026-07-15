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
    public $payload; 
    public $senderDeviceId; // 👈 إضافة هذا الحقل لحل مشكلة التكرار

    public function __construct($userId, $updateType, $payload = [])
    {
        $this->userId = $userId;
        $this->updateType = $updateType;
        $this->payload = $payload;
        // 👈 الحصول عليه تلقائياً من طلب الموبايل الأصلي (X-Device-ID)
        $this->senderDeviceId = request()->header('X-Device-ID'); 
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