<?php

declare(strict_types=1);

namespace App\Saas\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class SystemHealth extends Page
{
    protected string $view = 'filament.pages.system-health';

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->can('View:SystemHealth');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-heart';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('System Health');
    }

    public function getTitle(): string
    {
        return __('System Health');
    }

    /**
     * @return array<int, array{label: string, status: string, value: string, detail: string}>
     */
    public function checks(): array
    {
        return Cache::remember('saas:system-health:v1', 30, fn (): array => [
            $this->databaseCheck(),
            $this->queueCheck(),
            $this->reverbCheck(),
            $this->fcmCheck(),
            $this->diskCheck(),
        ]);
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function databaseCheck(): array
    {
        $startedAt = hrtime(true);

        try {
            DB::select('SELECT 1');
            $milliseconds = (hrtime(true) - $startedAt) / 1_000_000;

            return $this->check(__('MySQL Database'), 'healthy', number_format($milliseconds, 1).' ms', __('Connection successful'));
        } catch (Throwable $exception) {
            return $this->check(__('MySQL Database'), 'failed', __('Unavailable'), $exception->getMessage());
        }
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function queueCheck(): array
    {
        try {
            $connection = (string) config('queue.default');
            $backlog = $connection === 'database' && DB::getSchemaBuilder()->hasTable('jobs')
                ? DB::table('jobs')->count()
                : 0;

            if ($connection === 'redis') {
                Redis::connection()->ping();
                $backlog = (int) Redis::connection()->llen('queues:'.config('queue.connections.redis.queue', 'default'));
            }

            return $this->check(
                __('Queue Backlog'),
                $backlog > 100 ? 'warning' : 'healthy',
                number_format($backlog),
                __('Driver: :driver', ['driver' => $connection]),
            );
        } catch (Throwable $exception) {
            return $this->check(__('Queue Backlog'), 'failed', __('Unavailable'), $exception->getMessage());
        }
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function reverbCheck(): array
    {
        $host = (string) config('broadcasting.connections.reverb.options.host', config('reverb.servers.reverb.hostname', '127.0.0.1'));
        $host = $host !== '' ? $host : '127.0.0.1';
        $port = (int) config('broadcasting.connections.reverb.options.port', 8080);
        $startedAt = hrtime(true);

        try {
            $socket = @fsockopen($host, $port, $errorCode, $errorMessage, 1.5);
        } catch (Throwable $exception) {
            return $this->check(__('Laravel Reverb'), 'failed', __('Offline'), $exception->getMessage());
        }

        if ($socket === false) {
            return $this->check(__('Laravel Reverb'), 'failed', __('Offline'), "{$errorCode}: {$errorMessage}");
        }

        fclose($socket);
        $milliseconds = (hrtime(true) - $startedAt) / 1_000_000;

        return $this->check(__('Laravel Reverb'), 'healthy', number_format($milliseconds, 1).' ms', __('TCP connection successful'));
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function fcmCheck(): array
    {
        try {
            $response = Http::timeout(2)->get('https://fcm.googleapis.com');
            $reachable = $response->status() < 500;

            return $this->check(
                __('Firebase Cloud Messaging'),
                $reachable ? 'healthy' : 'failed',
                $reachable ? __('Reachable') : __('Unavailable'),
                __('HTTP status: :status', ['status' => $response->status()]),
            );
        } catch (Throwable $exception) {
            return $this->check(__('Firebase Cloud Messaging'), 'failed', __('Unavailable'), $exception->getMessage());
        }
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function diskCheck(): array
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return $this->check(__('Disk Storage'), 'failed', __('Unavailable'), $path);
        }

        $usedPercent = (($total - $free) / $total) * 100;

        return $this->check(
            __('Disk Storage'),
            $usedPercent >= 90 ? 'failed' : ($usedPercent >= 80 ? 'warning' : 'healthy'),
            number_format($usedPercent, 1).'%',
            __(':free GB free', ['free' => number_format($free / 1_073_741_824, 1)]),
        );
    }

    /** @return array{label: string, status: string, value: string, detail: string} */
    private function check(string $label, string $status, string $value, string $detail): array
    {
        return compact('label', 'status', 'value', 'detail');
    }
}
