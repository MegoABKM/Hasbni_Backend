<?php

namespace App\Observers;

use App\Services\FcmService;
use Illuminate\Database\Eloquent\Model;

class SyncObserver
{
    public function saved(Model $model)
    {
        $this->triggerSilentSync($model);
    }

    public function deleted(Model $model)
    {
        $this->triggerSilentSync($model);
    }

    private function triggerSilentSync(Model $model)
    {
        $userId = $model->user_id ?? $model->getAttribute('user_id');
        if (!$userId) return;

        $senderDeviceId = request()->header('X-Device-ID');

        // ðŸš€ FIX: afterResponse ensures the API returns to the phone instantly
        // without waiting for Firebase to reply, keeping the App lightning fast.
        dispatch(function () use ($userId, $senderDeviceId) {
            app(FcmService::class)->sendSilentUpdate($userId, $senderDeviceId);
        })->afterResponse();
    }
}
