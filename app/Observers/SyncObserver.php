<?php

namespace App\Observers;

use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;

class SyncObserver
{
    public function saved(Model $model): void
    {
        $this->triggerSilentSync($model);
    }

    public function deleted(Model $model): void
    {
        $this->triggerSilentSync($model);
    }

    private function triggerSilentSync(Model $model): void
    {
        $userId = $model->user_id ?? $model->getAttribute('user_id');

        if (! $userId) {
            return;
        }

        $senderDeviceId = request()->header('X-Device-ID');

        dispatch(function () use ($userId, $senderDeviceId): void {
            app(FcmService::class)->sendSilentUpdate($userId, $senderDeviceId);
        })->afterResponse();
    }
}
